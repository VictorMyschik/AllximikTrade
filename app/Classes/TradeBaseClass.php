<?php

namespace App\Classes;

use App\Helpers\MrDateTime;

abstract class TradeBaseClass implements TradingInterface
{
  protected string $pair;
  protected float $diff;
  protected int $quantityMax;
  protected float $quantityMin;
  protected array $calculatedOpenOrders;
  protected float $skipSum = 20;

  public const KIND_SELL = 'sell';
  public const KIND_BUY = 'buy';

  public function __construct(array $input)
  {
    $this->pair = $input['pair'];
    $this->diff = $input['diff'];
    $this->quantityMax = $input['maxTrade'];
    $this->quantityMin = $this->getPairsSettings()[$this->pair]['min_quantity'];
  }

  public function trade(): array
  {
    MrDateTime::Start();

    $fullOrderBook = $this->getOrderBook();
    if(!count($fullOrderBook)) {
      return ['Order book is empty'];
    }
    //$this->setSkipSum($input['orderBook']);

    $out = array();
    $out['Time'] = MrDateTime::now()->getFullTime();
    $out['Balance'] = $balance = $this->getBalance();


    $orderBookDiff = round($fullOrderBook[0]['PriceSell'] * 100 / (float)$fullOrderBook[0]['PriceBuy'] - 100, 2);
    $out['OrderBookDiff'] = $orderBookDiff;

    $fullOpenOrders = $this->getOpenOrder();

    $this->calculatedOpenOrders = $this->groupOpenOrders($fullOpenOrders);

    // If diff smaller than commission - cancel all orders
    if($orderBookDiff < $this->diff) {
      foreach($fullOpenOrders as $openOrder) {
        if($openOrder['pair'] == $this->pair) {
          $this->cancelOrder($openOrder['order_id']);
        }
      }
    }
    else {
      $needRestart = $this->correctHasOrders($fullOpenOrders, $fullOrderBook, $this->pair);
      if(!$needRestart) {
        $this->tradeByOrder($balance, $fullOpenOrders, $fullOrderBook, $this->pair);
      }
    }

    MrDateTime::StopItem(null);
    $work_time = MrDateTime::GetTimeResult();
    $out['WorkTime'] = reset($work_time);

    //$out['report'] = self::$report;

    return $out;
  }

  private function tradeByOrder(array $balance, array $fullOpenOrders, array $order_book, string $pairName): void
  {
    $currencyFirst = explode('_', $pairName)[0];
    $currencySecond = explode('_', $pairName)[1];
    $balanceValue = $balance[$currencyFirst] ?? 0;

    /// Sell MNX
    if($balanceValue > $this->quantityMin) {
      // Cancel open orders. Disable many orders, one only
      foreach($fullOpenOrders as $openOrder) {
        if($openOrder['type'] == 'sell') {
          $this->cancelOrder($openOrder['order_id']);

          return;
        }
      }

      // Create new order
      $newPrice = $this->getNewPrice($order_book, self::KIND_SELL, $pairName);
      $this->addOrder($newPrice, $pairName, self::KIND_SELL, $balanceValue);

      return;
    }

    /// Buy MNX
    $balanceValue = $balance[$currencySecond] ?? 0;
    if($balanceValue > 0.01) {
      $allowMaxTradeSum = $balanceValue > $this->quantityMax ? $this->quantityMax : $balanceValue;

      foreach($fullOpenOrders as $openOrder) {
        if($openOrder['type'] == self::KIND_BUY) {
          $this->cancelOrder($openOrder['order_id']);

          return;
        }
      }

      // Create new order
      $newPrice = $this->getNewPrice($order_book, self::KIND_BUY, $pairName);

      $quantity = $allowMaxTradeSum / $newPrice;

      if($quantity <= $this->quantityMin) {
        return;
      }

      $this->addOrder($newPrice, $pairName, self::KIND_BUY, $quantity);
    }
  }

  private function getNewPrice(array $orderBook, string $type, string $pairName): float
  {
    $precision = $this->getPricePrecision()[$pairName];
    $precisionDiff = pow(10, -$precision);

    // Get price skipping "small" amount row
    $orderBookItem = $orderBook[0];
    $sum = 0;
    foreach($orderBook as $item) {
      $sum += ($type == self::KIND_SELL) ? $item['SumSell'] : $item['SumBuy'];
      if($sum > $this->skipSum) {
        $orderBookItem = $item;
        break;
      }
    }

    if($type == self::KIND_SELL) {
      $old_price_sell = (float)$orderBookItem['PriceSell'];
      $newPrice = $old_price_sell - $precisionDiff;
    }
    else { // Buy
      $old_price_buy = (float)$orderBookItem['PriceBuy'];
      $newPrice = $old_price_buy + $precisionDiff;
    }

    return round($newPrice, $precision);
  }

  private function isActual(array $openOrder, array $orderBook): bool
  {
    $kind = $openOrder['type'];
    $price = $openOrder['price'] ?? $openOrder['rate'];

    $precision = $this->getPricePrecision()[$openOrder['pair']];
    $priceKeyName = ($kind == self::KIND_SELL) ? 'PriceSell' : 'PriceBuy';
    $sumKeyName = ($kind == self::KIND_SELL) ? 'SumSell' : 'SumBuy';

    $myOpenPrice = round($price, $precision);

    $orderBookItem = $orderBook[0];
    $sum = 0;
    foreach($orderBook as $item) {
      // exclude self order
      if($item[$priceKeyName] == $price)
        continue;

      $sum += $item[$sumKeyName];
      if($sum > $this->skipSum) {
        $orderBookItem = $item;
        break;
      }
    }

    $orderPrice = $orderBookItem[$priceKeyName];

    if($kind == self::KIND_SELL) {
      $precisionDiff = pow(10, -$precision);
      $orderPrice = $orderPrice - $precisionDiff;
    }

    if($kind == self::KIND_BUY) {
      $precisionDiff = pow(10, -$precision);
      $orderPrice = $orderPrice + $precisionDiff;
    }

    $orderPrice = round($orderPrice, $precision);

    if((string)$orderPrice != (string)$myOpenPrice) {
      return false; // need update order
    }

    return true;
  }

  private function correctHasOrders(array $fullOpenOrder, array $orderBook, string $pairName): bool
  {
    foreach($fullOpenOrder as $openOrder) {
      // Has open order
      if($pairName == $openOrder['pair']) {
        // Update order
        if(!$this->isActual($openOrder, $orderBook)) {
          $this->cancelOrder($openOrder['order_id']);

          return true;
        }
      }
    }

    return false;
  }

  /**
   * Group orders by pair names
   */
  private function groupOpenOrders(array $data): array
  {
    $openOrders = [];

    foreach($data as $item) {
      if(isset($openOrders[$item['pair']])) {
        $openOrders[$item['pair']] += round($item['amount'], 8);
      }
      else {
        $openOrders[$item['pair']] = round($item['amount'], 8);
      }
    }

    return $openOrders;
  }
}
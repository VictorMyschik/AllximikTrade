<?php

namespace App\Classes\Trade;

use App\Helpers\MrDateTime;

class SmartTradeClass
{
  public static array $report = []; // ошибки при работе
  protected float $quantityMin;
  protected float $quantityMax;
  protected array $calculatedOpenOrders;
  protected float $skipSum = 15;

  public function __construct()
  {
    self::$report = [];
  }

  /**
   * Книга ордеров вместе с историей торгов
   *
   * @param string $pair
   * @return array
   */
  public function GetOrderBook(string $pair): array
  {
    return MrExmoClass::GetOrderBook($pair, 50);
  }

  /**
   * Подготовка данных для торговли
   *
   * @param array $input
   * @return array
   */
  public function tradeData(array $input): array
  {
    MrDateTime::Start();

    $pair = $input['pair'];
    $diff = (float)$input['diff'];
    $this->quantityMax = $input['maxTrade'];
    // Мин количество валюты для создания ордера
    $this->quantityMin = MrExmoClass::getPairsSettings()[$pair]['min_quantity'];

    $out = array();
    $out['Time'] = MrDateTime::now()->getFullTime();

    $out['Balance'] = $balance = MrExmoClass::getBalance();

    /// Книга ордеров
    $fullOrderBook = $input['orderBook'];
    if(!count($fullOrderBook))
    {
      return $out;
    }

    $orderBookDiff = round($fullOrderBook[0]['PriceSell'] * 100 / (float)$fullOrderBook[0]['PriceBuy'] - 100, 2);
    $out['OrderBookDiff'] = $orderBookDiff;

    /// Открытые ордера (все)
    $fullOpenOrders = MrExmoClass::GetOpenOrder();
    $this->calculatedOpenOrders = $this->calculateOpenOrders($fullOpenOrders);

    // При маленькой разнице - отмена всех ордеров
    if($orderBookDiff < $diff)
    {
      foreach($fullOpenOrders as $openOrder)
      {
        if($openOrder['pair'] == $pair)
        {
          MrExmoClass::CancelOrder($openOrder['order_id']);
        }
      }
    }
    else
    {
      $needRestart = $this->correctHasOrders($fullOpenOrders, $fullOrderBook, $pair);
      if(!$needRestart)
      {
        $this->tradeByOrder($balance, $fullOpenOrders, $fullOrderBook, $pair);
      }
    }


    /// Статистика работы системы
    MrDateTime::StopItem(null);
    $work_time = MrDateTime::GetTimeResult();
    $out['WorkTime'] = reset($work_time);
    $out['report'] = self::$report;

    return $out;
  }

  /**
   * Проверка актуальности открытых ордеров
   *
   * @param array $fullOpenOrder // открытые ордера
   * @param array $orderBook // книга ордеров
   * @param string $pairName // пара с которой ведётся работа
   * @return bool
   */
  private function correctHasOrders(array $fullOpenOrder, array $orderBook, string $pairName): bool
  {
    /// работа с открытыми ордерами
    foreach($fullOpenOrder as $openOrder)
    {
      // Есть открытый ордер
      if($pairName == $openOrder['pair'])
      {
        // обновление ордера
        if(!$this->isActual($openOrder, $orderBook))
        {
          MrExmoClass::CancelOrder($openOrder['order_id']);

          return true;
        }
      }
    }

    return false;
  }

  /**
   * @param array $openOrder // открытый ордер
   * @param array $orderBook // книга ордера
   * @return bool
   */
  private function isActual(array $openOrder, array $orderBook): bool
  {
    $kind = $openOrder['type'];
    $precision = MrExmoClass::getPricePrecision()[$openOrder['pair']];
    $priceKeyName = ($kind == MrExmoClass::KIND_SELL) ? 'PriceSell' : 'PriceBuy';
    $sumKeyName = ($kind == MrExmoClass::KIND_SELL) ? 'SumSell' : 'SumBuy';
    $myOpenPrice = round($openOrder['price'], $precision);

    // Получение исходной цены пропуская "мелкие" строки
    $orderBookItem = $orderBook[0];
    $sum = 0;
    foreach($orderBook as $item)
    {
      // исключение своего ордера
      if($item[$priceKeyName] == $openOrder['price'])
        continue;

      $sum += $item[$sumKeyName];
      if($sum > $this->skipSum)
      {
        $orderBookItem = $item;
        break;
      }
    }

    $orderPrice = $orderBookItem[$priceKeyName];

    if($kind == MrExmoClass::KIND_SELL)
    {
      $precisionDiff = pow(10, -$precision);
      $orderPrice = $orderPrice - $precisionDiff;
    }

    if($kind == MrExmoClass::KIND_BUY)
    {
      $precisionDiff = pow(10, -$precision);
      $orderPrice = $orderPrice + $precisionDiff;
    }

    $orderPrice = round($orderPrice, $precision);

    if((string)$orderPrice != (string)$myOpenPrice)
    {
      return false; // нужно обновить ордер
    }

    return true;
  }

  /**
   * Вернёт массив сумм в открытых ордерах
   *
   * @param array $data
   * @return array
   */
  private function calculateOpenOrders(array $data): array
  {
    $openOrders = array();

    foreach($data as $item)
    {
      if(isset($openOrders[$item['pair']]))
      {
        $openOrders[$item['pair']] += round($item['amount'], 8);
      }
      else
      {
        $openOrders[$item['pair']] = round($item['amount'], 8);
      }
    }

    return $openOrders;
  }

  /**
   * Торговля
   *
   * @param array $balance
   * @param array $fullOpenOrders
   * @param array $order_book
   * @param string $pairName
   */
  private function tradeByOrder(array $balance, array $fullOpenOrders, array $order_book, string $pairName): void
  {
    $currencyFirst = explode('_', $pairName)[0];
    $currencySecond = explode('_', $pairName)[1];
    $balanceValue = $balance[$currencyFirst] ?? 0;

    /// Продажа MNX
    if($balanceValue > $this->quantityMin)
    {
      // если при этом уже есть имеющийся открытый ордер
      foreach($fullOpenOrders as $openOrder)
      {
        if($openOrder['type'] == 'sell')
        {
          MrExmoClass::CancelOrder($openOrder['order_id']);

          return;
        }
      }

      // Создание нового ордера
      $newPrice = $this->getNewPrice($order_book, MrExmoClass::KIND_SELL, $pairName);
      MrExmoClass::addOrder($newPrice, $pairName, MrExmoClass::KIND_SELL, $balanceValue);

      return;
    }

    /// Покупка MNX за USD
    $balanceValue = $balance[$currencySecond] ?? 0;
    if($balanceValue > 0.0001)
    {
      // Сумма, которую можно потратить. Не позволяем тратить все деньги.
      $allowMaxTradeSum = $balanceValue > $this->quantityMax ? $this->quantityMax : $balanceValue;

      foreach($fullOpenOrders as $openOrder)
      {
        if($openOrder['type'] == MrExmoClass::KIND_BUY)
        {
          MrExmoClass::CancelOrder($openOrder['order_id']);

          return;
        }
      }

      // Создание нового ордера
      $newPrice = $this->getNewPrice($order_book, MrExmoClass::KIND_BUY, $pairName);

      $quantity = $allowMaxTradeSum / $newPrice;

      if($quantity <= $this->quantityMin)
      {
        return;
      }

      MrExmoClass::addOrder($newPrice, $pairName, MrExmoClass::KIND_BUY, $quantity);
    }
  }

  /**
   * Получение новой цены для выставления ордера
   *
   * @param array $orderBook
   * @param string $type // покупка или продажа
   * @param $pairName
   * @return float
   */
  private function getNewPrice(array $orderBook, string $type, $pairName): float
  {
    $precision = MrExmoClass::getPricePrecision()[$pairName];
    $precisionDiff = pow(10, -$precision);

    // Получение исходной цены пропуская "мелкие" строки
    $orderBookItem = $orderBook[0];
    $sum = 0;
    foreach($orderBook as $item)
    {
      $sum += ($type == MrExmoClass::KIND_SELL) ? $item['SumSell'] : $item['SumBuy'];
      if($sum > $this->skipSum)
      {
        $orderBookItem = $item;
        break;
      }
    }

    if($type == MrExmoClass::KIND_SELL)
    {
      $old_price_sell = (float)$orderBookItem['PriceSell'];
      $newPrice = $old_price_sell - $precisionDiff;
    }
    else
    { // Покупка
      $old_price_buy = (float)$orderBookItem['PriceBuy'];
      $newPrice = $old_price_buy + $precisionDiff;
    }

    // Округление
    $newPrice = round($newPrice, $precision);

//dd($newPrice);
    return $newPrice;
  }
}
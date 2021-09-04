<?php

namespace App\Classes;

use App\Helpers\MrDateTime;
use App\Helpers\Stock\MrExmoHelper;

class SmartTradeClass
{
  public static $report = []; // ошибки при работе
  protected $quantityMin;
  protected $quantityMax;
  protected $calculatedOpenOrders;

  /**
   * Книга ордеров вместе с историей торгов
   *
   * @param string $pair
   * @return array
   */
  public function GetOrderBook(string $pair): array
  {
    return MrExmoClass::GetOrderBook($pair, 5);
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
    $this->quantityMin = MrExmoHelper::getPairsSettings()[$pair]['min_quantity'];

    $out = array();
    $out['Time'] = MrDateTime::now()->getFullTime();

    $out['Balance'] = $balance = MrExmoHelper::getBalance();

    /// Книга ордеров
    $fullOrderBook = $input['orderBook'];
    $orderBookDiff = 0;
    if(count($fullOrderBook))
    {
      $orderBookDiff = round($fullOrderBook[0]['PriceSell'] * 100 / (float)$fullOrderBook[0]['PriceBuy'] - 100, 2);
      $out['OrderBookDiff'] = $orderBookDiff;
    }

    /// Открытые ордера (все)
    $fullOpenOrders = MrExmoHelper::GetOpenOrder();
    $this->calculatedOpenOrders = $this->calculateOpenOrders($fullOpenOrders);

    // При маленькой разнице - отмена всех ордеров
    if($orderBookDiff < $diff)
    {
      foreach($fullOpenOrders as $openOrder)
      {
        if($openOrder['pair'] == $pair)
        {
          MrExmoHelper::CancelOrder($openOrder['OrderId']);
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
   * @param array $fullOpenOrder// открытые ордера
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
          MrExmoHelper::CancelOrder($openOrder['order_id']);

          return false;
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
    $precision = MrExmoHelper::getPricePrecision()[$openOrder['pair']];
    $myOpenPrice = round($openOrder['price'], $precision);
    $order_book_price_sell = round($orderBook[0]['PriceSell'], $precision);
    $order_book_price_sell_1 = round($orderBook[1]['PriceSell'], $precision);
    $order_book_price_buy = round($orderBook[0]['PriceBuy'], $precision);
    $order_book_price_buy_1 = round($orderBook[1]['PriceBuy'], $precision);

    $kind = $openOrder['type'];
    if($kind == MrExmoHelper::KIND_SELL)
    {
      if($order_book_price_sell < $myOpenPrice)
      {
        return false;
      }
    }

    if($kind == MrExmoHelper::KIND_BUY)
    {
      // цена в книге ордеров больше, чем в моём ордере
      if($order_book_price_buy > $myOpenPrice)
      {
        return false;
      }

      // Сумма открытых ордеров больше макс допустимого (на все деньги не торговать)
      if($this->calculatedOpenOrders[$openOrder['Pair']] > $this->quantityMax)
      {
        return false;
      }
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
        $openOrders[$item['pair']] += round($item['amount'], 5);
      }
      else
      {
        $openOrders[$item['pair']] = round($item['amount'], 5);
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
          MrExmoHelper::CancelOrder($openOrder['OrderId']);

          return;
        }
      }

      // Создание нового ордера
      $newPrice = $this->getNewPrice($order_book, MrExmoHelper::KIND_SELL, $pairName);
      MrExmoHelper::addOrder($newPrice, $pairName, MrExmoHelper::KIND_SELL, $balanceValue);

      return;
    }

    /// Покупка MNX за USD
    if($balanceValue = $balance[$currencySecond] ?? null)
    {
      // Сумма, которую можно потратить. Не позволяем тратить все деньги.
      $allowMaxTradeSum = $balanceValue > $this->quantityMax ? $this->quantityMax : $balanceValue;

      foreach($fullOpenOrders as $openOrder)
      {
        if($openOrder['type'] == MrExmoHelper::KIND_BUY)
        {
          MrExmoHelper::CancelOrder($openOrder['OrderId']);

          return;
        }
      }

      // Создание нового ордера
      $newPrice = $this->getNewPrice($order_book, MrExmoHelper::KIND_BUY, $pairName);

      $quantity = $allowMaxTradeSum / $newPrice;
      MrExmoHelper::addOrder($newPrice, $pairName, MrExmoHelper::KIND_BUY, $quantity);
    }
  }

  /**
   * Получение новой цены для выставления ордера
   *
   * @param array $order_book
   * @param string $type // покупка или продажа
   * @param $pair_name
   * @return float
   */
  private function getNewPrice(array $order_book, string $type, $pair_name): float
  {
    $precision = pow(10, -MrExmoHelper::getPricePrecision()[$pair_name]);

    if($type == MrExmoHelper::KIND_SELL)
    {
      $old_price_sell = (float)$order_book[0]['PriceSell'];
      $new_price = $old_price_sell - $precision;
    }
    else
    { // Покупка
      $old_price_buy = (float)$order_book[0]['PriceBuy'];
      $new_price = $old_price_buy + $precision;
    }

    return $new_price;
  }
}
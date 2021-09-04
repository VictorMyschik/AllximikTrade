<?php

namespace App\Classes;

use App\Helpers\MrCacheHelper;
use Carbon\Carbon;
use Mockery\Exception;

class MrExmoClass
{
  const EXMO_KEY = "K-eeb135483b96892d849464156b01fed9a31a7a85";
  const EXMO_SECRET = "S-403f4a9693f9424843d8ed4e3e6384353e229df6";

  const KIND_SELL = 'sell';
  const KIND_BUY = 'buy';

  /**
   * Список валютных пар для торговли
   *
   * @param string $delimiter
   * @return array
   */
  public static function GetUsdPairs(string $delimiter = '/'): array
  {
    $pairs = array();

    foreach(self::GetPairsSettings() as $key => $item)
    {
      $tmp = explode('_', (string)mb_convert_case($key, MB_CASE_UPPER, "UTF-8"));

      if($tmp[1] != 'USD')
      {
        continue;
      }

      $pairs[$key] = implode($delimiter, $tmp);
    }

    ksort($pairs);

    return $pairs;
  }

  public static function GetAllPairs(string $delimiter = '/'): array
  {
    $pairs = array();

    foreach(self::GetPairsSettings() as $key => $item)
    {
      $tmp = explode('_', (string)mb_convert_case($key, MB_CASE_UPPER, "UTF-8"));


      $pairs[$key] = implode($delimiter, $tmp);
    }

    ksort($pairs);

    return $pairs;
  }

  private static $precision;

  /**
   * Округление
   *
   * @param string $delimiter
   * @return array
   */
  public static function GetPricePrecision(string $delimiter = '/'): array
  {
    if(self::$precision)
    {
      return self::$precision;
    }
    else
    {
      self::$precision = MrCacheHelper::GetCachedData('exmo_price_precision', function() use ($delimiter) {
        $pairs = array();
        foreach(self::GetPairsSettings() as $key => $item)
        {
          $pairs[$key] = $item['price_precision'];
        }
        ksort($pairs);

        return $pairs;
      });
    }

    return self::$precision;
  }

  /**
   * Настройки валютных пар
   *
   * @return mixed
   */
  public static function GetPairsSettings(): array
  {
    //return MrCacheHelper::GetCachedData('exmo_pairs', function() {
    return self::api_query('pair_settings', array());
    //});
  }

  /**
   * API Exmo
   * Скачано с https://github.com/exmo-dev/exmo_api_lib/blob/master/php/exmo.php
   *
   * @param $api_name
   * @param array $req
   * @return mixed
   */
  protected static function api_query($api_name, array $req = array())
  {
    $mt = explode(' ', microtime());
    $NONCE = $mt[1] . substr($mt[0], 2, 6);
    // API settings
    $url = "https://api.exmo.com/v1.1/$api_name";
    $req['nonce'] = $NONCE;
    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac('sha512', $post_data, self::EXMO_SECRET);
    // generate the extra headers
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . self::EXMO_KEY,
    );
    // our curl handle (initialize if required)
    static $ch = null;

    if(is_null($ch))
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // run the query
    $res = curl_exec($ch);
    if($res === false)
    {
      throw new Exception('Could not get reply: ' . curl_error($ch));
    }
    $dec = @json_decode($res, true);
    if($dec === null)
    {
      throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
    }

    return $dec;
  }

  /**
   * Создание ордера
   *
   * @param float $price Цена сделки
   * @param string $pair_name
   * @param string $kind
   * @param float $quantity Количество
   * @return mixed
   */
  public static function addOrder(float $price, string $pair_name, string $kind, float $quantity)
  {
    $parameters = array(
      "pair"     => $pair_name, //"BTC_USD",
      "quantity" => round($quantity, 8),
      "price"    => round($price, self::GetPricePrecision()[$pair_name]),
      "type"     => $kind
    );

    return self::api_query('order_create', $parameters);
  }

  /**
   * Отмена ордера
   *
   * @param int $order_id
   */
  public static function cancelOrder(int $order_id)
  {
    self::api_query('order_cancel', ["order_id" => $order_id]);
  }

  /**
   * Получение баланса
   *
   * @return mixed
   */
  public static function GetBalance(): array
  {
    $response = self::api_query('user_info', array());

    $balance_out_array = array();

    if(isset($response['balances']))
    {
      foreach($response['balances'] as $crypto_name => $balance)
      {
        $balance_out_array[$crypto_name] = (float)$balance;
      }
    }

    return $balance_out_array;
  }

  /**
   * Книга ордеров (вместе с выполненными сделками)
   *
   * @param string $pair
   * @param int $limit
   * @return array
   */
  public static function GetOrderBook(string $pair, int $limit = 100): array
  {
    $result_book = array();
    $param = array('pair' => $pair, 'limit' => $limit);
    $book = self::parseOrderBook(self::api_query('order_book', $param), $pair);

    $param = array('pair' => $pair);
    $history = self::parseHistory(self::api_query('trades', $param), $pair);

    foreach($book as $key => $item)
    {
      $result_book[] = array_merge($item, $history[$key] ?? array());
    }

    return $result_book;
  }

  public static function parseHistory(array $data, string $pair): array
  {
    $out = array();

    foreach($data[$pair] as $row)
    {
      $item = array();
      $item['KindTraded'] = $row['type'] == 'buy' ? self::KIND_BUY : self::KIND_SELL;
      // Количество
      $item['QuantityTraded'] = round($row['quantity'], 4);
      $item['PriceTraded'] = round($row['price'], 5);
      // Сумма
      $item['SumTraded'] = round($row['amount'], 5);
      $item['TimeTraded'] = Carbon::createFromTimestamp($row['date'])->toDateTime()->format('H:i:s');;

      $out[] = $item;
    }

    return $out;
  }

  /**
   * Открытые ордера
   *
   * @return array
   */
  public static function getOpenOrder(): array
  {
    $list = self::api_query('user_open_orders', array());

    if(isset($list['result']))
    {
      return [];
    }
    else
    {
      $out = array();
      foreach($list as $row)
      {
        if(is_array($row))
        {
          foreach($row as $item)
          {
            $out[] = $item;
          }
        }
      }
    }

    return $out;
  }

  /**
   * Получение списка сделок для валютной пары (собственные сделки).
   * Пример: BTC_USD
   *
   * @param string $pairs
   * @return array
   */
  public static function GetMyCompletedTradeList(string $pairs): array
  {
    $parameters = array(
      "pair" => $pairs, "limit" => 15, "offset" => 0
    );

    return self::api_query('user_trades', $parameters);
  }

  public static function parseOrderBook(array $data, string $pair): array
  {
    $rows = [];

    // Количество
    foreach($data[$pair]['ask'] as $key => $item)
    {
      $row = array();
      $row['PriceSell'] = round($item[0], 8);
      $row['QuantitySell'] = round($item[1], 4);
      $row['SumSell'] = round($item[2], 4);

      $row['PriceBuy'] = round($data[$pair]['bid'][$key][0], 8);
      $row['QuantityBuy'] = round($data[$pair]['bid'][$key][1], 4);
      $row['SumBuy'] = round($data[$pair]['bid'][$key][2], 4);

      $rows[] = $row;
    }

    return $rows;
  }
}
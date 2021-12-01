<?php

namespace App\Classes\Trade;

use App\Helpers\MrCacheHelper;
use Carbon\Carbon;

class MrYobitClass
{
  const API_KEY = "CE74105A4C15A9F7D2AF98B4193E6F56";
  const API_SECRET = "98976bde58680100b539477b9f857ee7";

  const KIND_SELL = 'sell';
  const KIND_BUY = 'buy';

  /**
   * Список валютных пар для торговли
   */
  public static function GetPairsByName(string $name, string $delimiter = '/'): array
  {
    $pairs = array();

    foreach(self::GetPairsSettings() as $key => $item) {
      $tmp = explode('_', (string)mb_convert_case($key, MB_CASE_UPPER, "UTF-8"));

      if($name !== $tmp[1]) {
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

    foreach(self::GetPairsSettings() as $key => $item) {
      $tmp = explode('_', (string)mb_convert_case($key, MB_CASE_UPPER, "UTF-8"));


      $pairs[$key] = implode($delimiter, $tmp);
    }

    ksort($pairs);

    return $pairs;
  }

  private static array $precision = [];

  /**
   * Округление
   *
   * @param string $delimiter
   * @return array
   */
  public static function GetPricePrecision(string $delimiter = '/'): array
  {
    if(self::$precision) {
      return self::$precision;
    }
    else {
      self::$precision = MrCacheHelper::GetCachedData('yobit_price_precision', function() use ($delimiter) {
        $pairs = array();
        foreach(self::GetPairsSettings() as $key => $item) {
          $pairs[$key] = $item['decimal_places'];
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
    return MrCacheHelper::GetCachedData('yobit_pairs', function() {
      $url = "https://yobit.net/api/3/info";

      return self::api($url)['pairs'];
    });
  }

  /**
   * API Yobit
   * Скачано с https://github.com/exmo-dev/exmo_api_lib/blob/master/php/exmo.php
   *
   * @param $api_name
   * @param array $req
   * @return mixed
   */
  protected static function api_query($api_name, array $req = array()): mixed
  {
    $req['method'] = $api_name;
    $req['nonce'] = time()+rand(1,5);

    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac("sha512", $post_data, self::API_SECRET);
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . self::API_KEY,
    );

    $ch = null;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; SMART_API PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
    curl_setopt($ch, CURLOPT_URL, 'https://yobit.net/tapi/');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $res = curl_exec($ch);
    if($res === false) {
      $e = curl_error($ch);
      curl_close($ch);

      return null;
    }
    curl_close($ch);
    $result = json_decode($res, true);

    return $result;
  }

  /**
   * Создание ордера
   */
  public static function addOrder(float $price, string $pairName, string $kind, float $quantity): mixed
  {
    $tmp_num = explode('.', $quantity);
    $tmp1 = $tmp_num[1] ?? 0;
    $precisionDiff = pow(10, -strlen($tmp1));
    $finalQuantity = $quantity - $precisionDiff;
    $parameters = array(
      "pair"   => $pairName,  //"BTC_USD",
      "amount" => $finalQuantity,
      "rate"   => $price,
      "type"   => $kind
    );

    return self::api_query('Trade', $parameters);
  }

  /**
   * Отмена ордера
   */
  public static function cancelOrder(int $order_id)
  {
    self::api_query('CancelOrder', ["order_id" => $order_id]);
  }

  /**
   * Получение баланса
   */
  public static function getBalance(): array
  {
    $response = self::api_query('getInfo', []);

    $balance_out_array = array();
    if(isset($response['return'])) {
      foreach($response['return']['funds'] as $crypto_name => $balance) {
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
    $urlBook = "https://yobit.net/api/3/depth/$pair?limit=50";
    $book = self::parseOrderBook(self::api($urlBook), $pair);

    $urlHistory = "https://yobit.net/api/3/trades/$pair?limit=50";
    $history = self::parseHistory(self::api($urlHistory), $pair);

    foreach($book as $key => $item) {
      $result_book[] = array_merge($item, $history[$key] ?? array());
    }

    return $result_book;
  }


  private static function api(string $url)
  {
    return json_decode(file_get_contents($url), true);
  }

  public static function parseHistory(array $data, string $pair): array
  {
    $out = array();

    foreach($data[$pair] as $row) {
      $amount = round($row['amount'], 5);
      $price = round($row['price'], 5);

      $item = array();
      $item['KindTraded'] = $row['type'] == 'buy' ? self::KIND_BUY : self::KIND_SELL;
      // Количество
      $item['QuantityTraded'] = $amount;
      $item['PriceTraded'] = $price;
      // Сумма
      $item['SumTraded'] = $amount * $price;
      $item['TimeTraded'] = Carbon::createFromTimestamp($row['timestamp'])->toDateTime()->format('H:i:s');

      $out[] = $item;
    }

    return $out;
  }

  /**
   * Открытые ордера
   */
  public static function getOpenOrder(string $pair): array
  {
    $out = array();
    $list = self::api_query('ActiveOrders', ['pair' => $pair]);

    if($list['success'] === 1) {
      if(isset($list['return'])) {
        foreach($list['return'] as $key => $row) {
          $item = $row;
          $item['order_id'] = $key;

          $out[] = $item;
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
    foreach($data[$pair]['asks'] as $key => $item) {

      $priceSell = round($item[0], 8);
      $quantitySell = round($item[1], 4);
      $sumSell = $priceSell * $quantitySell;

      $row = array();
      $row['PriceSell'] = $priceSell;
      $row['QuantitySell'] = $quantitySell;
      $row['SumSell'] = $sumSell;

      $priceBuy = round($data[$pair]['bids'][$key][0], 8);
      $quantityBuy = round($data[$pair]['bids'][$key][1], 4);
      $sumBuy = $priceBuy * $quantityBuy;

      $row['PriceBuy'] = $priceBuy;
      $row['QuantityBuy'] = $quantityBuy;
      $row['SumBuy'] = $sumBuy;

      $rows[] = $row;
    }

    return $rows;
  }
}
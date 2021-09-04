<?php

namespace App\Helpers\Stock;

use App\Helpers\MrCacheHelper;
use App\Http\Controllers\Admin\Stock\MrStockTradeController;
use App\Http\Controllers\Controller;
use Mockery\Exception;

class MrYobitHelper extends Controller
{
  const YOBIT_KEY = "CE74105A4C15A9F7D2AF98B4193E6F56";
  const YOBIT_SECRET = "98976bde58680100b539477b9f857ee7";

  /**
   * Список валютных пар для торговли с USD
   *
   * @param string $delimiter
   * @return array
   */
  public static function GetUsdPairs(string $delimiter = '/'): array
  {
    $pairs = array();
    foreach (self::GetPairs()['pairs'] as $key => $item)
    {
      $tmp = explode('_', (string)mb_convert_case(mb_strtoupper($key), MB_CASE_UPPER, "UTF-8"));
      if($tmp[1] != 'USD')
      {
        continue;
      }

      $pairs[$key] = implode($delimiter, $tmp);
    }

    ksort($pairs);

    return $pairs;
  }

  /**
   * Список всех валютных пар биржи
   *
   * @return array
   */
  private static function GetPairs(): array
  {
    return MrCacheHelper::GetCachedData('yobit_pairs', function () {
      return self::api_query('info');
    });
  }

  /**
   * @param $pairs
   * @return array
   */
  public static function GetMyCompletedTradeList($pairs): array
  {
    $parameters = array(
      "pair" => 'doge_usd', "limit" => 7, "offset" => 0
    );
    sleep(1);
    return self::api_query('TradeHistory', ["pair" => 'doge_usd'], true);
  }

  /**
   * Открытые ордера
   * @param string $pair_first
   * @param string $pair_second
   * @return array
   */
  public static function GetOpenOrder(string $pair_first = null, string $pair_second = null): array
  {
    $out = array();

    $pair_first = mb_strtolower($pair_first);
    $pair_second = mb_strtolower($pair_second);

    if($pair_first)
    {
      $list = self::api_query('ActiveOrders', ['pair' => $pair_first], true);

      if($list['success'] == 0)
      {
        MrStockTradeController::$errors[] = $list['error'];
        return $out;
      }
      elseif($list['return'] ?? null)
      {
        foreach ($list['return'] as $row_1)
        {
          $out[] = $row_1;
        }
      }
    }

    if($pair_second)
    {
      sleep(1);
      $list = self::api_query('ActiveOrders', ['pair' => $pair_second], true);


      if($list['success'] == 0)
      {
        MrStockTradeController::$errors[] = $list['error'];
        return $out;
      }
      elseif($list['return'] ?? null)
      {
        foreach ($list['return'] as $key => $row)
        {
          $row['order_id'] = $key;
          $out[] = $row;
        }
      }
    }

    return $out;
  }

  /**
   * Получение баланса
   *
   * @return array
   */
  public static function GetBalance(): array
  {
    $data = self::api_query('getInfo', [], true);
    $out = array();
    if($data['success'] == 1)
    {
      foreach ($data['return']['funds'] as $key => $item)
      {
        $out[mb_strtoupper($key)] = $item;
      }
    }

    return $out;
  }

  public static function CancelOrder(int $order_id)
  {
    dd('method "CancelOrder"');
  }

  /**
   * Книга ордеров (вместе с выполненными сделками)
   *
   * @param string $pair
   * @param int $limit
   * @return array
   */
  public static function GetOrderBook(string $pair, int $limit = 5): array
  {
    $result_book = array();
    $pair = mb_strtolower($pair);

    // Книга ордеров
    $param = '/' . $pair . '?limit=' . $limit;

    $method = 'depth' . $param;
    $book = self::ParseOrderBook(self::api_query($method, []), $pair);
    // Книга истории
    $method = 'trades' . $param;
    $history = array();//MrCryptoPairController::ParseHistoryYobit(self::api_query($method, []), $pair);

    foreach ($book as $key => $item)
    {
      $result_book[] = array_merge($item, $history[$key] ?? array());
    }

    return $result_book;
  }

  public static function ParseOrderBook(array $data, string $pair): array
  {
    $rows = array();
    foreach ($data[$pair]['asks'] as $key_sell => $item)
    {
      $rows[$key_sell]['PriceSell'] = round($item[0], 8);
      $rows[$key_sell]['QuantitySell'] = round($item[1], 5);
      $rows[$key_sell]['SumSell'] = round($item[0] * $item[1], 5);
    }

    foreach ($data[$pair]['bids'] as $key_buy => $item)
    {
      $rows[$key_buy]['PriceBuy'] = round($item[0], 8);
      $rows[$key_buy]['QuantityBuy'] = round($item[1], 4);
      $rows[$key_buy]['SumBuy'] = round($item[0] * $item[1], 5);
    }

    return $rows;
  }

  /**
   * Создание ордера
   *
   * @param float $price Цена сделки
   * @param string $pair_name
   * @param int $kind Продажа/покупка
   * @param float $quantity Количество
   * @return mixed
   */
  public static function addOrder(float $price, string $pair_name, int $kind, float $quantity)
  {
    $parameters = array(
      "pair"   => $pair_name, //"BTC_USD",
      "amount" => round($quantity, 8),
      "rate"   => round($price, 8),
      "type"   => $kind == MrStockTradeController::KIND_SELL ? 'sell' : 'buy',//"sell"
    );

    $r = self::api_query('Trade', $parameters, true);
  }

  /**
   * https://www.yobit.net/ru/api/
   *
   * @param $api_name
   * @param array $req
   * @param bool $auth другой URL для приватных запросов
   * @return mixed
   */
  public static function api_query($api_name, array $req = array(), bool $auth = false)
  {
    if($auth)
    {
      $url = "https://yobit.io/tapi";
      $req['method'] = $api_name;
    }
    else
    {
      $url = "https://yobit.io/api/3/$api_name";
    }

    $req['nonce'] = time();

    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac('sha512', $post_data, self::YOBIT_SECRET);
    // generate the extra headers
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . self::YOBIT_KEY,
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

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
}
<?php

namespace App\Classes;

use App\Helpers\MrCacheHelper;
use Carbon\Carbon;
use Mockery\Exception;

class ExmoClass extends TradeBaseClass implements TradingInterface
{
  const EXMO_KEY = "K-eeb135483b96892d849464156b01fed9a31a7a85";
  const EXMO_SECRET = "S-403f4a9693f9424843d8ed4e3e6384353e229df6";

  private array $precision = [];

  /**
   * Order Book with trades history
   */
  public function getOrderBook(int $limit = 100): array
  {
    $resultBook = array();
    $param = array('pair' => $this->pair, 'limit' => $limit);
    $book = $this->parseOrderBook(self::api_query('order_book', $param));

    $param = array('pair' => $this->pair);
    $history = $this->parseHistory(self::api_query('trades', $param));

    foreach($book as $key => $item) {
      $resultBook[] = array_merge($item, $history[$key] ?? array());
    }

    return $resultBook;
  }

  private function parseOrderBook(array $data): array
  {
    $rows = [];

    foreach($data[$this->pair]['ask'] as $key => $item) {
      $row = array();
      $row['PriceSell'] = round($item[0], 8);
      $row['QuantitySell'] = round($item[1], 4);
      $row['SumSell'] = round($item[2], 4);

      $row['PriceBuy'] = round($data[$this->pair]['bid'][$key][0], 8);
      $row['QuantityBuy'] = round($data[$this->pair]['bid'][$key][1], 4);
      $row['SumBuy'] = round($data[$this->pair]['bid'][$key][2], 4);

      $rows[] = $row;
    }

    return $rows;
  }

  public function parseHistory(array $data): array
  {
    $out = array();

    foreach($data[$this->pair] as $row) {
      $item = array();

      $item['KindTraded'] = $row['type'] == 'buy' ? self::KIND_BUY : self::KIND_SELL;
      $item['QuantityTraded'] = round($row['quantity'], 4);
      $item['PriceTraded'] = round($row['price'], 5);

      $item['SumTraded'] = round($row['amount'], 5);
      $item['TimeTraded'] = Carbon::createFromTimestamp($row['date'])->toDateTime()->format('H:i:s');
      $item['timestamp'] = $row['date'];

      $out[] = $item;
    }

    return $out;
  }

  public function getPricePrecision(): array
  {
    if(!count($this->precision)) {
      $this->precision = MrCacheHelper::GetCachedData(self::class . '_price_precision', function() {
        $pairs = array();
        foreach(self::getPairsSettings() as $key => $item) {
          $pairs[$key] = $item['price_precision'];
        }
        ksort($pairs);

        return $pairs;
      });
    }

    return $this->precision;
  }

  public function getPairsSettings(): array
  {
    return MrCacheHelper::GetCachedData(self::class . '_PairsSettings', function() {
      return $this->api_query('pair_settings', array());
    });
  }

  public function getBalance(): array
  {
    $response = $this->api_query('user_info', array());

    $balanceOut = array();

    if(isset($response['balances'])) {
      foreach($response['balances'] as $cryptoName => $balance) {
        $balanceOut[$cryptoName] = (float)$balance;
      }
    }

    return $balanceOut;
  }

  public function addOrder(float $price, string $pairName, string $kind, float $quantity): mixed
  {
    $tmp_num = (explode('.', $quantity));
    $precisionDiff = pow(10, -strlen($tmp_num[1]));
    $finalQuantity = $quantity - $precisionDiff;

    $parameters = array(
      "pair"     => $pairName,  //"BTC_USD",
      "quantity" => $finalQuantity,
      "price"    => $price,
      "type"     => $kind
    );

    return $this->api_query('order_create', $parameters);
  }

  public function cancelOrder(int $orderId)
  {
    $this->api_query('order_cancel', ["order_id" => $orderId]);
  }

  public function getOpenOrder(): array
  {
    $list = $this->api_query('user_open_orders', array());

    if(isset($list['result'])) {
      return [];
    }
    else {
      $out = array();
      foreach($list as $row) {
        if(is_array($row)) {
          foreach($row as $item) {
            $out[] = $item;
          }
        }
      }
    }

    return $out;
  }

  /**
   * API Exmo
   * Downloaded from https://github.com/exmo-dev/exmo_api_lib/blob/master/php/exmo.php
   */
  private function api_query($api_name, array $req = array()): mixed
  {
    $mt = explode(' ', microtime());
    $NONCE = $mt[1] . substr($mt[0], 2, 6);
    // API settings
    $url = "https://api.exmo.com/v1.1/$api_name";
    $req['nonce'] = $NONCE;
    // generate the POST data string
    $post_data = http_build_query($req);
    $sign = hash_hmac('sha512', $post_data, self::EXMO_SECRET);
    // generate the extra headers
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . self::EXMO_KEY,
    );
    // our curl handle (initialize if required)
    static $ch = null;

    if(is_null($ch)) {
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
    if($res === false) {
      throw new Exception('Could not get reply: ' . curl_error($ch));
    }
    $dec = @json_decode($res, true);
    if($dec === null) {
      throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
    }

    return $dec;
  }
}
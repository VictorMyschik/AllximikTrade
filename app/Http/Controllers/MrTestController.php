<?php

namespace App\Http\Controllers;

use App\Classes\Trade\SmartTradeClass;
use App\Jobs\ExmoJobTrading;

class MrTestController extends Controller
{
  public function index()
  {
    $pairs = array(
      'MNC_USD',// 'ONE/BTC'
    );

    foreach($pairs as $pair)
    {
      self::trading([
        'diff'     => 1.2,
        'maxTrade' => 100,
        'pair'     => $pair
      ]);
    }
  }

  public static function trading(array $input)
  {
    $trade = new SmartTradeClass();
    $orderBook = $trade->GetOrderBook($input['pair']);

    $input['orderBook'] = $orderBook;
    $result = $trade->tradeData($input);
    sleep(1);
    ExmoJobTrading::dispatch($input);
  }
}
<?php

namespace App\Http\Controllers;

use App\Classes\SmartTradeClass;
use App\Jobs\ExmoJobTrading;
use Illuminate\Http\Request;

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
    sleep(2);
    ExmoJobTrading::dispatch($input);
  }
}
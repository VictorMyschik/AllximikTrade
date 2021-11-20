<?php

namespace App\Http\Controllers;

use App\Classes\Trade\SmartTradeClass;
use App\Jobs\ExmoJobTrading;

class MrTestController extends Controller
{
  public function index()
  {
    $pairs = array(
      'SHIB_USD', 'MNC_USD'
    );

    foreach($pairs as $pair)
    {
      self::trading([
        'diff'     => 0.5,
        'maxTrade' => 20,
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
    //sleep(2);
    ExmoJobTrading::dispatch($input);
  }
}
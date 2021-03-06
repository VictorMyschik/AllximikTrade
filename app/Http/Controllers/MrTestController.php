<?php

namespace App\Http\Controllers;

use App\Classes\Trade\MrExmoClass;
use App\Classes\Trade\SmartTradeClass;
use App\Jobs\ExmoJobTrading;
use Illuminate\Support\Facades\DB;

class MrTestController extends Controller
{
  public function index()
  {
    $pairs = array(
      'SHIB_USD'//, 'SHIB_USDT'
    );

    foreach($pairs as $pair) {
      self::trading([
        'diff'     => 0.3,
        'maxTrade' => 120,
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
    //sleep(1);
    ExmoJobTrading::dispatch($input);
  }

  public function tradeResult()
  {
    $list = DB::table('trade_logs')->limit(50)->orderBy('id', 'desc')->get();

    return View('trade')->with(['list' => $list]);
  }

  public function index2()
  {
    //$pairList = MrExmoClass::GetPairsByName('USDT', '_');
    $pairList = MrExmoClass::GetAllPairs('_');

    $result = [];
    foreach($pairList as $pair) {
      $trade = new SmartTradeClass();

      $orderBook = $trade->GetOrderBook($pair);
      if(!count($orderBook))
        continue;

      $result[$pair] = [
        'middle' => $this->setSkipSum($orderBook),
        'period' => $this->calculatePeriod($orderBook),
        'diff'   => $this->calculateDifferentPrice($orderBook)
      ];
    }

    $result = $this->sortArr($result);

    return View('statistic')->with(['list' => $result]);
  }

  private function calculateDifferentPrice(array $orderBook): float
  {
    return round($orderBook[0]['PriceSell'] * 100 / (float)$orderBook[0]['PriceBuy'] - 100, 2);
  }

  private function sortArr(array $list): array
  {
    $tmp = [];
    foreach($list as $key => $item) {
      $d = $item['period'] * 10000;
      $tmp[$d] = $key;
    }

    ksort($tmp);

    $new = [];
    foreach($tmp as $key => $item) {
      $new[$item] = $list[$item];
    }

    return $new;
  }

  private function calculatePeriod(array $orderBook): float
  {
    $list = [];
    for($i = 0; $i < count($orderBook) - 1; $i++) {
      $list[] = $orderBook[$i]['timestamp'] - $orderBook[$i + 1]['timestamp'];
    }

    return array_sum($list) / count($orderBook);
  }

  private function setSkipSum(array $orderBook): float
  {
    $sum = 0;
    foreach($orderBook as $item) {
      if(!isset($item['SumTraded']))
        return 0.0;

      $sum += $item['SumTraded'];
    }

    return $sum / count($orderBook);
  }
}
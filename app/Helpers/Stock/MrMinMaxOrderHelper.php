<?php

namespace App\Helpers\Stock;

use App\Http\Controllers\Controller;
use App\Models\MrMinMaxOrder;
use App\Models\MrStock;

class MrMinMaxOrderHelper extends Controller
{
  public static function parseData(MrStock $stock, array $data)
  {
    $data = array();

    if ($stock->id() == MrStock::STOCK_EXMO)
    {
      $data = self::parseExmo($data);
    }

    self::insertInDB($data, $stock);
  }

  public static function parseExmo(array $data)
  {
    $out = array();

    foreach ($data as $key => $name)
    {
      $out[] = array(
          "Pair"        => $key,
          "MinQuantity" => $name["min_quantity"],
          "MinPrice"    => $name["min_price"],
          "MinAmount"   => $name["min_amount"],
      );
    }

    return $out;
  }

  /**
   * Вставка в БД
   *
   * @param array $data
   * @param MrStock $stock
   */
  public static function insertInDB(array $data, MrStock $stock)
  {
    foreach ($data as $item)
    {
      $cond = array(
          ['Pair', $item['Pair']],
          ['StockID', $stock->id()]
      );

      $row = MrMinMaxOrder::where($cond)->first() ?: new MrMinMaxOrder();

      $row->setPair($item['Pair']);
      $row->setMinQuantity($item['MinQuantity']);
      $row->setMinPrice($item['MinPrice']);
      $row->setMinAmount($item['MinAmount']);
      $row->setStockID($stock->id());

      $row->save_mr();
    }
  }
}
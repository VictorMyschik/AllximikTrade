<?php

namespace App\Helpers\Stock;

use App\Http\Controllers\Controller;
use App\Models\MrCryptoCurrency;
use App\Models\MrStock;

/**
 * Парсер комиссий разных бирж
 */
class MrCommissionsParserHelper extends Controller
{
  public static function parsingLivecoin(array $data, MrStock $stock): array
  {
    $out = array();
    foreach ($data['info'] as $row)
    {
      if (!$pair_model = MrCryptoCurrency::loadBy($row['symbol'], 'Name'))
      {
        continue;
      }
      else
      {

        $data_in_db = array(
            'CurrencyID' => $pair_model->id(),
            'StockID'    => $stock->id(),
            'in'         => 0,
            'out'        => $row['withdrawFee'],
        );
        // Статус кошелька, по умолчанию выключены
        $data_in_db['close_in'] = true;
        $data_in_db['close_out'] = true;

        if ($row['walletStatus'] == 'normal')
        {
          $data_in_db['close_in'] = false;
          $data_in_db['close_out'] = false;
        }
        elseif ($row['walletStatus'] == 'closed_cashin')
        {
          $data_in_db['close_in'] = true;
          $data_in_db['close_out'] = false;
        }
        elseif ($row['walletStatus'] == 'closed_cashout')
        {
          $data_in_db['close_in'] = false;
          $data_in_db['close_out'] = true;
        }

        $out[] = $data_in_db;
      }
    }

    return $out;
  }

  public static function parsingPoloniex(array $data, MrStock $stock): array
  {
    $out = array();
    foreach ($data as $key => $row)
    {
      if (!$pair_model = MrCryptoCurrency::loadBy($key, 'Name'))
      {
        continue;
      }
      else
      {
        $data_in_db = array(
            'CurrencyID' => $pair_model->id(),
            'StockID'    => $stock->id(),
            'in'         => $row['txFee'],
            'out'        => $row['txFee'],
        );
        $out[] = $data_in_db;
      }
    }

    return $out;
  }

  public static function parsingHitBtc(array $data, MrStock $stock): array
  {
    $out = array();
    foreach ($data as $key => $row)
    {
      if ((float)($row['payoutFee']) > 100000000)
        continue;

      if ($id = MrCryptoCurrency::GetCurrencyListOut()[$row['id']] ?? null)
      {
        $out[] = array(
            'CurrencyID' => $id,
            'StockID'    => $stock->id(),
            'in'         => $row['payoutFee'] ?? 0,
            'out'        => $row['payoutFee'] ?? 0,
            'close_in'   => $row['payinEnabled'],
            'close_out'  => $row['payoutEnabled'],
        );
      }
    }

    return $out;
  }

  public static function parsingBittrex(array $data, MrStock $stock): array
  {
    $out = array();
    foreach ($data as $key => $row)
    {
      if ($id = MrCryptoCurrency::GetCurrencyListOut()[$row['Currency']] ?? null)
      {
        $out[] = array(
            'CurrencyID' => $id,
            'StockID'    => $stock->id(),
            'in'         => $row['TxFee'],
            'out'        => $row['TxFee'],
        );
      }
    }

    return $out;
  }

  public static function parsingExmo(): array
  {
    return array(
        array('CurrencyID' => MrCryptoCurrency::loadBy('BTC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.0005),
        array('CurrencyID' => MrCryptoCurrency::loadBy('LTC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('DOGE', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('DASH', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ETH', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('WAVES', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.001),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ZEC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.001),
        array('CurrencyID' => MrCryptoCurrency::loadBy('XMR', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.001),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ETC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('BCH', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.001),
        array('CurrencyID' => MrCryptoCurrency::loadBy('BTG', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.001),
        array('CurrencyID' => MrCryptoCurrency::loadBy('EOS', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.05),
        array('CurrencyID' => MrCryptoCurrency::loadBy('DXT', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 20, 'out' => 20),
        array('CurrencyID' => MrCryptoCurrency::loadBy('XLM', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('MNX', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('OMG', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0.1, 'out' => 0.5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('TRX', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ADA', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('INK', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 10, 'out' => 50),
        array('CurrencyID' => MrCryptoCurrency::loadBy('NEO', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ZRX', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('GNT', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('GUSD', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('LSK', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('XEM', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('SMART', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('QTUM', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('HB', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 10),
        array('CurrencyID' => MrCryptoCurrency::loadBy('DAI', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('MKR', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.005),
        array('CurrencyID' => MrCryptoCurrency::loadBy('MNC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 15),
        array('CurrencyID' => MrCryptoCurrency::loadBy('PTI', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 10),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ETZ', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('USDC', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('DCR', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('VLX', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ZAG', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0),
        array('CurrencyID' => MrCryptoCurrency::loadBy('BTT', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 100),
        array('CurrencyID' => MrCryptoCurrency::loadBy('VLX', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('CRON', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ONT', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 1),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ONG', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 5),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ALGO', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.01),
        array('CurrencyID' => MrCryptoCurrency::loadBy('ATOM', 'Name')->id(), 'StockID' => MrStock::STOCK_EXMO, 'in' => 0, 'out' => 0.05),
    );
  }
}
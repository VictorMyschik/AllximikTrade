<?php

namespace App\Helpers\Stock;

use App\Http\Controllers\Controller;
use App\Models\MrCryptoCurrency;
use App\Models\MrCurrencyPair;
use App\Models\MrStatistic;
use App\Models\MrStock;
use App\Models\MrStockSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MrStockPairsHelper extends Controller
{
  /**
   * Обновление по списку бирж
   *
   * @param MrStock[] $stocks биржи
   * @return void инфо о результатах
   */
  public static function Update(array $stocks)
  {
    // Настройки
    foreach ($stocks as $stock)
    {
      $json = @file_get_contents($stock->getUrlApi());

      if ($json == false || empty($json))
      {
        continue;
      }

      $data = @json_decode($json, true);

      $data_insert = array();
      if ($data == null || !count($data))
      {
        continue;
      }

      if ($stock->id() == MrStock::STOCK_YOBIT)
      {
        // Около 8 тысяч пар. - разбивание на группы по 19 и отправка по группам
        $pair_name = '';
        $i = 0;
        $pair = array();
        foreach ($data['pairs'] as $key => $value)
        {
          $i++;
          $pair_name .= $key . '-';
          if ($i > 19)
          {
            $pair_name = substr($pair_name, 0, -1);
            $pair[] = $pair_name;
            $pair_name = '';
            $i = 0;
          }
        }

        $pair_name = null;
        foreach ($pair as $pair_name)
        {
          $url = 'https://yobit.net/api/3/depth/' . $pair_name . '?limit=4';
          $json = @file_get_contents($url);
          $part = @json_decode($json, true);
          if ($part)
          {
            foreach ($part as $key => $info)
            {

              if (is_array($info) && ($array = self::parsingYObit($info, $key)))
              {
                $data_insert[] = $array;
              }
            }
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_LIVECOIN)
      {
        foreach ($data['currencyPairs'] as $value)
        {
          if ($array = self::parsingLivecoin($value))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_EXMO)
      {
        foreach ($data as $key => $value)
        {
          if ($array = self::parsingExmo($value, $key))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_BINANCE)
      {
        foreach ($data as $value)
        {
          if ($array = self::parsingBinance($value))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_HITBTC)
      {
        foreach ($data as $value)
        {
          if ($array = self::parsingHitBTC($value))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_POLONIEX)
      {
        foreach ($data as $key => $value)
        {
          if ($array = self::parsingPoloniex($value, $key))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_BITTREX)
      {
        foreach ($data['result'] as $item)
        {
          if ($array = self::parsingBittrex($item))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_SEXIO)
      {
        foreach ($data['data'] as $key => $value)
        {
          if ($array = self::parsingCEXio($value))
          {
            $data_insert[] = $array;
          }
        }
      }

      if ($stock->id() == MrStock::STOCK_COINSBIT)
      {
        foreach ($data['result'] as $key => $value)
        {

          if ($array = self::parsingCoinsBit($value, $key))
          {
            $data_insert[] = $array;
          }
        }
      }

      $result_insert = self::setInDbCurrencyPair($data_insert, $stock);

      $stock->setDateUpdate(Carbon::now());
      $stock->setCountUpdated((int)$result_insert['update']);
      $stock->setCountIn((int)count($data_insert));

      // Время выполнения скрипта
      $stock->save_mr();

      $statistic = new MrStatistic();
      $statistic->setStockID($stock->id());
      $statistic->setDescription('Обновление');
      $statistic->setDateIssue(Carbon::now());
      $statistic->save_mr();
    }
  }

  /**
   * Общая валидация
   *
   * @param array $input
   * @return bool
   */
  protected static function ValidateInput(array $input): bool
  {
    if (($input['bid'] == 0) || ($input['ask'] == 0))
    {
      return false;
    }
    else
    {
      $bid = $input['bid'];
      $ask = $input['ask'];
    }

    if (!is_numeric($bid) || !is_numeric($ask))
    {
      return false;
    }

    if ($bid === $ask)
    {
      return false;
    }

    return true;
  }

  /** HitBTC
   *
   * @param array $value
   *
   * @return array|null
   */
  public static function parsingHitBTC(array $value): ?array
  {
    $input = array(
        'bid'  => $value['bid'],
        'ask'  => $value['ask'],
        'name' => $value['symbol'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    $pair_second_model = null;
    if (strlen($value['symbol']) == 6)
    {
      $pair = str_split($value['symbol'], 3);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    elseif (strlen($value['symbol']) == 7)
    {
      $pair = str_split($value['symbol'], 4);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    elseif (strlen($value['symbol']) == 7)
    {
      $pair = str_split($value['symbol'], 3);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    else
    {
      return null;
    }

    return [
        'origin_name'       => (string)$value['symbol'],
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['bid'],
        'sell_price'        => $value['ask'],
        'buy_price_amount'  => 0,
        'sell_price_amount' => 0,
    ];
  }

  protected static $pair_ignor = array();

  /** YObit
   *
   * @param array $value
   * @param string $pair_name
   * @return array
   */
  public static function parsingYObit(array $value, string $pair_name): ?array
  {
    if (!isset($value['asks']) || !isset($value['bids']))
    {
      return null;
    }

    $pair = explode('_', (string)mb_convert_case($pair_name, MB_CASE_UPPER, "UTF-8"));

    if (!($currency_first_id = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($currency_second_id = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    if (self::filter($currency_first_id, $currency_second_id, $value))
    {
      return null;
    }


    $asks = null;
    $bids = null;
    $bids_amount = 0;
    $asks_amount = 0;

    foreach ($value['bids'] as $item)
    {
      $bids = $item[0];
      $bids_amount += $item[1];
    }

    foreach ($value['asks'] as $item)
    {
      $asks = $item[0];
      $asks_amount += $item[1];
    }

    if ($bids_amount == 0 || $asks_amount == 0)
    {
      return null;
    }

    return [
        'origin_name'       => $pair_name,
        'pair_first'        => $currency_first_id,
        'pair_second'       => $currency_second_id,
        'buy_price'         => $bids,
        'buy_price_amount'  => $bids_amount,
        'sell_price'        => $asks,
        'sell_price_amount' => $asks_amount,
    ];
  }

  public static function parsingExmo(array $value, $pair_name): ?array
  {
    $pair = explode('_', (string)$pair_name);

    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    return [
        'origin_name'       => (string)$pair_name,
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['buy_price'],
        'buy_price_amount'  => $value['vol_curr'],
        'sell_price'        => $value['sell_price'],
        'sell_price_amount' => $value['vol'],
    ];
  }

  /** BINANCE
   *
   * @param array $value
   *
   * @return array|null
   */
  public static function parsingBinance(array $value): ?array
  {
    $input = array(
        'bid'  => $value['bidPrice'],
        'ask'  => $value['askPrice'],
        'name' => $value['symbol'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    if (strlen($value['symbol']) == 6)
    {
      $pair = str_split($value['symbol'], 3);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    elseif (strlen($value['symbol']) == 7)
    {
      $pair = str_split($value['symbol'], 4);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    elseif (strlen($value['symbol']) == 7)
    {
      $pair = str_split($value['symbol'], 3);
      if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
      {
        return null;
      }
    }
    else
    {
      return null;
    }

    return [
        'origin_name'       => (string)$value['symbol'],
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['bidPrice'],
        'sell_price'        => $value['askPrice'],
        'buy_price_amount'  => $value['bidQty'],
        'sell_price_amount' => $value['askQty'],
    ];
  }

  /** POLONIEX
   *
   * @param array $value
   * @param $pair_name
   * @return array
   */
  public static function parsingPoloniex(array $value, $pair_name): ?array
  {
    $input = array(
        'bid' => $value['highestBid'],
        'ask' => $value['lowestAsk'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    $pair = explode('_', (string)$pair_name);

    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    return [
        'origin_name'       => (string)$pair_name,
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['highestBid'],
        'sell_price'        => $value['lowestAsk'],
        'buy_price_amount'  => 0,
        'sell_price_amount' => 0,
    ];
  }


  /**
   * LIVECOIN
   *
   * @param array $value
   * @return array
   */
  public static function parsingLivecoin(array $value): ?array
  {
    $input = array(
        'bid' => $value['maxBid'],
        'ask' => $value['minAsk'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    $pair_name = (string)$value['symbol'];
    $pair = explode('/', (string)$pair_name);

    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    return [
        'origin_name'       => $pair_name,
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['maxBid'],
        'sell_price'        => $value['minAsk'],
        'buy_price_amount'  => 0,
        'sell_price_amount' => 0,
    ];
  }

  /** COINSBIT
   *
   * @param array $value
   * @param string $name
   * @return array
   */
  public static function parsingCoinsBit(array $value, string $name): ?array
  {

    $input = array(
        'bid' => $value['ticker']['bid'],
        'ask' => $value['ticker']['ask'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    $pair = explode('_', (string)$name);

    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    return [
        'origin_name'       => (string)$name,
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['ticker']['bid'],
        'sell_price'        => $value['ticker']['ask'],
        'buy_price_amount'  => 0,
        'sell_price_amount' => 0,
    ];
  }

  /** BITTREX
   *
   * @param array $value
   * @return array
   */
  public static function parsingBittrex(array $value): ?array
  {
    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$value['Market']['BaseCurrency']] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$value['Market']['MarketCurrency']] ?? null))
    {
      return null;
    }

    $asks = $value['Summary']['Ask'];
    $bids = $value['Summary']['Bid'];
    $bids_amount = $value['Summary']['BaseVolume'];
    $asks_amount = $value['Summary']['Volume'];

    return [
        'origin_name'       => (string)$value['Market']['MarketName'],
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => (float)$bids,
        'buy_price_amount'  => (float)$bids_amount,
        'sell_price'        => (float)$asks,
        'sell_price_amount' => (float)$asks_amount,
    ];
  }

  /** CEXio
   *
   * @param array $value
   * @return array
   */
  public static function parsingCEXio(array $value): ?array
  {
    $input = array(
        'bid' => $value['bid'],
        'ask' => $value['ask'],
    );

    if (!self::ValidateInput($input))
    {
      return null;
    }

    $pair_name = $value['pair'];

    $pair = explode(':', (string)$pair_name);

    if (!($pair_first_model = MrCryptoCurrency::GetCurrencyList()[$pair[0]] ?? null) || !($pair_second_model = MrCryptoCurrency::GetCurrencyList()[$pair[1]] ?? null))
    {
      return null;
    }

    return [
        'origin_name'       => (string)$pair_name,
        'pair_first'        => $pair_first_model,
        'pair_second'       => $pair_second_model,
        'buy_price'         => $value['bid'],
        'sell_price'        => $value['ask'],
        'buy_price_amount'  => 0,
        'sell_price_amount' => 0,
    ];
  }

  /**
   * Фильтр перед записью, отсеивает левые данные
   *
   * @param array $data
   * @param MrStock $stock
   * @return bool
   */
  protected static function DataFilter(array $data, MrStock $stock): bool
  {
    if (!count($data))
    {
      return false;
    }

    // Цена не должна быть минимальной, иначе торгов нету
    if ((float)$data['buy_price'] < 0.000001 || (float)$data['sell_price'] < 0.000001)
    {
      return false;
    }

    // Если разница купля/продажа больше 5% значит торги затянуты, никто не покупает и не продаёт
    $diff = $data['sell_price'] * 100 / $data['buy_price'] - 100;

    if ($diff > MrStockSettings::DIFF_BY_SELL)
    {
      return false;
    }


    return true;
  }

  /**
   * Вставка/Обновление данных в таблицу
   *
   * @param array $data_insert
   * @param MrStock $stock
   * @return array
   */
  protected static function setInDbCurrencyPair(array $data_insert, MrStock $stock): array
  {
    $count_update = 0;
    $count_new = 0;
    $count_skip = 0;
    foreach ($data_insert as $row)
    {
      if (!self::DataFilter($row, $stock))
      {
        $count_skip++;
        continue;
      }

      if ($id = DB::table(MrCurrencyPair::getTableName())
          ->WHERE('CurrencyFirstID', '=', $row['pair_first'])
          ->WHERE('CurrencySecondID', '=', $row['pair_second'])
          ->WHERE('StockID', '=', $stock->id())
          ->value('id'))
      {
        $currency_pair = MrCurrencyPair::loadBy($id);
        // данные не изменились
        if ($currency_pair->getPriceBuy() == $row['buy_price']
            && $currency_pair->getPriceSell() == $row['sell_price']
            && $currency_pair->getBuyAmount() == $row['buy_price_amount']
            && $currency_pair->getSellAmount() == $row['sell_price_amount']
        )
        {
          continue;
        }

        $pair = $currency_pair;
        $count_update++;
      }
      else
      {// новая запись
        $pair = new MrCurrencyPair();
        $count_new++;
      }

      $pair->setStockID($stock->id());
      $pair->setCurrencyFirstID((int)$row['pair_first']);
      $pair->setCurrencySecondID((int)$row['pair_second']);
      $pair->setPriceBuy($row['buy_price']);
      $pair->setBuyAmount($row['buy_price_amount']);
      $pair->setPriceSell($row['sell_price']);
      $pair->setSellAmount($row['sell_price_amount']);
      $pair->setCurrencyPairOriginName($row['origin_name']);
      $pair->save_mr();
    }

    $count['update'] = $count_update;
    $count['new'] = $count_new;
    $count['skip'] = $count_skip;

    return $count;
  }

  /**
   * Фильтр для отсеивания некоторых валют
   *
   * @param int $currency_first_id
   * @param int $currency_second_id
   * @param array $data
   * @return bool
   */
  protected static function filter(int $currency_first_id, int $currency_second_id, array $data): bool
  {
    if ($currency_first_id == MrCryptoCurrency::btc()->id() || $currency_second_id == MrCryptoCurrency::btc()->id())
    {
      return true;
    }

    return false;
  }
}
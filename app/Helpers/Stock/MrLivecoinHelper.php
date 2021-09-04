<?php

namespace App\Helpers\Stock;

use App\Helpers\MrCacheHelper;
use App\Http\Controllers\Controller;
use Mockery\Exception;

class MrLivecoinHelper extends Controller
{
  const LIVECOIN_KEY = "yTKjUzvZt9DXBN4vPcDVDeZaCnxGKEVA";
  const LIVECOIN_SECRET = "w2ACwAy3Bt8CYd9uYw8neF6njvfmQjcB";

  /**
   * Список валютных пар для торговли
   *
   * @param string $delimiter
   * @return array
   */
  public static function GetUsdPairs(string $delimiter = '/')
  {
    $pairs = array();

    foreach (self::GetLivecoinPairs() as $key => $item)
    {
      $tmp = explode('_', (string)mb_convert_case($key, MB_CASE_UPPER, "UTF-8"));

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
   */
  private static function GetLivecoinPairs(): array
  {
    return MrCacheHelper::GetCachedData('exmo_pairs', function () {
      return self::api_query('pair_settings', array());
    });
  }

  public static function api_query($api_name, array $req = array(), bool $auth = false)
  {
    if($auth)
    {
      $url = "https://api.livecoin.net";
      $req['method'] = $api_name;
    }
    else
    {
      dd('нет адреса');
      $url = "";
    }

    $req['nonce'] = time();

    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac('sha512', $post_data, self::LIVECOIN_SECRET);
    // generate the extra headers
    $headers = array(
      'Sign: ' . $sign,
      'Key: ' . self::LIVECOIN_KEY,
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
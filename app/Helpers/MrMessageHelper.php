<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class MrMessageHelper extends Controller
{
  const KIND_ERROR = 1;
  const KIND_WARNING = 2;
  const KIND_SUCCESS = 3;

  protected static $kind_list = array(
      self::KIND_ERROR => 'danger',
      self::KIND_WARNING => 'warning',
      self::KIND_SUCCESS => 'success',
  );

  /**
   * Set message
   *
   * @param int $kind
   * @param string $message
   */
  public static function SetMessage(int $kind, string $message)
  {
    $name = 'alert-' . self::$kind_list[$kind];

    Session::flash($name, $message);
  }

  /**
   * Display message
   *
   * @return string
   */
  public static function GetMessage(): string
  {
    $out = '';
    foreach (self::$kind_list as $kind)
    {
      $key = 'alert-' . $kind;
      if ($message = session($key))
      {
        $out .= '<div class="alert ' . $key . '" role="alert">';
        $out .= '<span class="badge badge-pill ' . $key . '">' . '</span> ';
        $out .= $message . "</div>";
      }
    }

    return $out;
  }
}
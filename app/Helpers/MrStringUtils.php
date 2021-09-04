<?php

namespace App\Helpers;


class MrStringUtils
{
  public static function SanitizeFileName(string $filename, bool $transliterate = true)
  {
    $r = trim($filename);



    $r = str_replace('/', '-', $r);
    $r = str_replace(' ', '_', $r);
    $r = str_replace('№', 'N', $r);

    $r = preg_replace('/[^A-Za-z0-9\.\-\_]/i', '', $r);

    return $r;
  }

  public static function StartsWith(string $haystack, string $needle) : bool
  {
    $length = strlen($needle);

    if(!$length)
    {
      return false;
    }

    return (substr($haystack, 0, $length) === $needle);
  }


  /**
   * @param string|null          $str
   * @param string[]|string $starts array ot string to check for
   * @param bool            $case_sensitive
   * @return bool
   */
  public static function MbStartsWith(?string $str, $starts, $case_sensitive = false) : bool
  {
    if(!$str || !$starts)
      return false;

    if(!is_array($starts))
      $starts = array($starts);

    foreach ($starts as $start)
    {
      if(!$len = mb_strlen($start))
        continue;

      $tmp = mb_substr($str, 0, $len);

      if(!$case_sensitive)
      {
        $tmp = mb_strtoupper($tmp);
        $start = mb_strtoupper($start);
      }

      if($tmp == $start)
        return true;
    }

    return false;
  }

  public static function EndsWith(string $haystack, string $needle): bool
  {
    $length = strlen($needle);

    if ($length == 0)
    {
      return false;
    }

    return (substr($haystack, -$length) === $needle);
  }

  public static function SimpleDiff($old, $new) : array
  {
    $old = str_replace("\r\n", "\n", $old);
    $old = str_replace("\n\r", "\n", $old);
    $new = str_replace("\r\n", "\n", $new);
    $new = str_replace("\n\r", "\n", $new);

    $old_array = explode("\n", $old);
    $new_array = explode("\n", $new);

    $result = self::ArrayStringDiff($old_array, $new_array);

    //remove first and last pieces if they are empty
    if(is_array($result[0]) && !count($result[0]['d']) && !count($result[0]['i']))
    {
      array_shift($result);
    }
    $last = count($result) - 1;
    if(is_array($result[$last]) && !count($result[$last]['d']) && !count($result[$last]['i']))
    {
      unset($result[$last]);
    }

    return $result;
  }

  /**
   * https://github.com/paulgb/simplediff/blob/master/php/simplediff.php
   *
   * @param array $old
   * @param array $new
   * @return array
   */
  private static function ArrayStringDiff($old, $new) : array
  {
    $matrix = array();

    $maxlen = 0;
    $omax = 0;
    $nmax = 0;

    foreach ($old as $oindex => $ovalue)
    {
      $nkeys = array_keys($new, $ovalue);

      foreach ($nkeys as $nindex)
      {
        $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;

        if ($matrix[$oindex][$nindex] > $maxlen)
        {
          $maxlen = $matrix[$oindex][$nindex];
          $omax = $oindex + 1 - $maxlen;
          $nmax = $nindex + 1 - $maxlen;
        }
      }
    }

    if ($maxlen == 0)
      return array(array('d' => $old, 'i' => $new));

    return array_merge(
      self::ArrayStringDiff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
      array_slice($new, $nmax, $maxlen),
      self::ArrayStringDiff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
  }

  /**
   * Function replacese in string non-standard characters
   *
   * @param string $input
   * @return string
   */
  public static function UnicodeCharsetCleanup(string $input) : string
  {
    $s = array(); //search
    $r = array(); //replace

    $s[] = 'Å'; $r[] = 'A';
    $s[] = 'Ą'; $r[] = 'A';
    $s[] = 'Ä'; $r[] = 'A';
    $s[] = 'À'; $r[] = 'A';
    $s[] = 'Á'; $r[] = 'A';
    $s[] = 'Â'; $r[] = 'A';
    $s[] = 'Ã'; $r[] = 'A';
    $s[] = 'Ā'; $r[] = 'A';
    $s[] = 'Æ'; $r[] = 'AE';
    $s[] = 'ß'; $r[] = 'ss';
    $s[] = 'Β'; $r[] = 'B'; //"beta" capital letter
    $s[] = 'Ć'; $r[] = 'C';
    $s[] = 'Č'; $r[] = 'C';
    $s[] = 'Ç'; $r[] = 'C';
    $s[] = 'Ð'; $r[] = 'D';
    $s[] = 'Ę'; $r[] = 'E';
    $s[] = 'Ė'; $r[] = 'E';
    $s[] = 'È'; $r[] = 'E';
    $s[] = 'É'; $r[] = 'E';
    $s[] = 'Ê'; $r[] = 'E';
    $s[] = 'Ě'; $r[] = 'E';
    $s[] = 'Ë'; $r[] = 'Ё';
    $s[] = 'Ē'; $r[] = 'E';
    $s[] = 'Ğ'; $r[] = 'G';
    $s[] = 'Į'; $r[] = 'I';
    $s[] = 'І'; $r[] = 'I';
    $s[] = 'Ì'; $r[] = 'I';
    $s[] = 'Í'; $r[] = 'I';
    $s[] = 'Î'; $r[] = 'I';
    $s[] = 'Ï'; $r[] = 'I';
    $s[] = 'İ'; $r[] = 'I';
    $s[] = 'Ł'; $r[] = 'L';
    $s[] = 'Ļ'; $r[] = 'L';
    $s[] = 'Λ'; $r[] = 'Л';
    $s[] = 'Μ'; $r[] = 'M';//µ
    $s[] = 'Ń'; $r[] = 'N';
    $s[] = 'Ñ'; $r[] = 'N';
    $s[] = 'Ň'; $r[] = 'N';
    $s[] = 'Ν'; $r[] = 'N';
    $s[] = '₦'; $r[] = 'N';
    $s[] = 'Ó'; $r[] = 'O';
    $s[] = 'Ö'; $r[] = 'O';
    $s[] = 'Ò'; $r[] = 'O';
    $s[] = 'Ô'; $r[] = 'O';
    $s[] = 'Õ'; $r[] = 'O';
    $s[] = 'Ø'; $r[] = 'O';
    $s[] = 'Œ'; $r[] = 'OE';
    $s[] = 'Ř'; $r[] = 'R';
    $s[] = 'Ś'; $r[] = 'S';
    $s[] = 'Š'; $r[] = 'S';
    $s[] = 'Ş'; $r[] = 'S';
    $s[] = 'Ú'; $r[] = 'U';
    $s[] = 'Ù'; $r[] = 'U';
    $s[] = 'Ü'; $r[] = 'U';
    $s[] = 'Ų'; $r[] = 'U';
    $s[] = 'Ū'; $r[] = 'U';
    $s[] = 'Û'; $r[] = 'U';
    $s[] = 'Ű'; $r[] = 'U';
    $s[] = 'Ÿ'; $r[] = 'Y';
    $s[] = '¥'; $r[] = 'Y';
    $s[] = 'Ý'; $r[] = 'Y';
    $s[] = 'Ź'; $r[] = 'Z';
    $s[] = 'Ż'; $r[] = 'Z';
    $s[] = 'Ž'; $r[] = 'Z';
    $s[] = 'Φ'; $r[] = 'Ф';

    //specials
    $s[] = '«'; $r[] = '"';
    $s[] = '»'; $r[] = '"';
    $s[] = '″'; $r[] = '"';
    $s[] = '˝'; $r[] = '"';
    $s[] = '”'; $r[] = '"';
    $s[] = '„'; $r[] = '"';
    $s[] = '“'; $r[] = '"';
    $s[] = '〞'; $r[] = '"';
    $s[] = '̈'; $r[] = '"';
    $s[] = '–'; $r[] = '-';
    $s[] = '­'; $r[] = '-';
    $s[] = '—'; $r[] = '-';
    $s[] = '‒'; $r[] = '-';
    $s[] = '¬'; $r[] = '-';
    $s[] = '‐'; $r[] = '-';
    $s[] = '−'; $r[] = '-';
    $s[] = '‑'; $r[] = '-';
    $s[] = '˜'; $r[] = '~';
    $s[] = '…'; $r[] = '...';
    $s[] = '⋅'; $r[] = '.';
    $s[] = '·'; $r[] = '.';
    $s[] = '°'; $r[] = ' ';
    $s[] = 'º'; $r[] = ' ';
    $s[] = 'ᴼ'; $r[] = ' ';
    $s[] = '⁰'; $r[] = ' ';
    $s[] = '’'; $r[] = '\'';
    $s[] = 'ʼ'; $r[] = '\''; //not the same!
    $s[] = '´'; $r[] = '\'';
    $s[] = '′'; $r[] = '\'';
    $s[] = '，'; $r[] = '\'';
    $s[] = '‘'; $r[] = '\'';
    $s[] = '́'; $r[] = '';
    $s[] = '̆'; $r[] = '\'';
    $s[] = '（'; $r[] = '(';
    $s[] = '）'; $r[] = ')';
    $s[] = '×'; $r[] = 'x';
    $s[] = '®'; $r[] = '(R)';
    $s[] = '©'; $r[] = '(C)';
    $s[] = '℗'; $r[] = '(P)';
    $s[] = '™'; $r[] = '(TM)';
    $s[] = '℠'; $r[] = '(SM)';
    $s[] = ' '; $r[] = ''; //U+2028 line separator
    $s[] = "\t"; $r[] = ' '; //tab
    $s[] = "\f"; $r[] = ' '; //formfeed
    $s[] = '¶'; $r[] = ' '; //paragraph
    $s[] = '∼'; $r[] = '~';
    $s[] = '÷'; $r[] = '-';
    $s[] = '±'; $r[] = '+-';
    $s[] = '∓'; $r[] = '-+';
    $s[] = '¹'; $r[] = '1';
    $s[] = '²'; $r[] = '2';
    $s[] = '³'; $r[] = '3';
    $s[] = '¼'; $r[] = '1/4';
    $s[] = '⅓'; $r[] = '1/3';
    $s[] = '⅔'; $r[] = '2/3';
    $s[] = '½'; $r[] = '1/2';
    $s[] = '¾'; $r[] = '3/4';
    $s[] = '⅛'; $r[] = '1/8';
    $s[] = '⅒'; $r[] = '1/10';
    $s[] = '√'; $r[] = '^';
    $s[] = '•'; $r[] = '-';
    $s[] = '●'; $r[] = '-';
    $s[] = '∅'; $r[] = 'o';
    $s[] = '∆'; $r[] = '?';
    $s[] = '⁄'; $r[] = '/';
    $s[] = '¿'; $r[] = '?';
    $s[] = '¦'; $r[] = '|';
    $s[] = '≤'; $r[] = '<=';
    $s[] = '≥'; $r[] = '>=';
    $s[] = '℃'; $r[] = 'C';
    $s[] = '￠'; $r[] = 'c';
    $s[] = ''; $r[] = '?';
    $s[] = "\u{200B}"; $r[] = '';//пробел нулевой ширины
    $s[] = "\u{0090}"; $r[] = '';
    $s[] = "\u{200E}"; $r[] = '';
    $s[] = "\u{3000}"; $r[] = ' ';//Ideographic Space
    $s[] = "\u{0300}"; $r[] = ''; //Combining Grave Accent
    $s[] = "\u{0307}"; $r[] = ''; //Combining Dot Above


    //$s[] = ''; $r[] = '';

    return str_replace($s, $r, $input);
  }

  /**
   * Compares two string cleaning them from unicode characters before comparing
   *
   * @param string $str1
   * @param string $str2
   * @param bool   $case_sensitive
   * @return bool
   */
  public static function UnicodeCleanedEquals(string $str1, string $str2, bool $case_sensitive = false) : bool
  {
    $str1 = self::UnicodeCharsetCleanup($str1);
    $str2 = self::UnicodeCharsetCleanup($str2);

    if(!$case_sensitive)
    {
      $str1 = mb_strtoupper($str1);
      $str2 = mb_strtoupper($str2);
    }

    return $str1 == $str2;
  }

  /**
   * choose string depending on count of item(s)
   *
   * @param int         $count
   * @param string      $one_item_string
   * @param string      $two_items_string
   * @param null|string $five_items_string
   * @param bool        $skip_count do not add count and just return string
   * @return string
   */
  public static function formatPlural(int $count, string $one_item_string, string $two_items_string, ?string $five_items_string = null, bool $skip_count = false) : string
  {
    $r = '';

    if(!$skip_count)
    {
      $r = (string) $count . ' ';
    }

    if(is_null($five_items_string))
      $five_items_string = $two_items_string;

    $last_two_digits = $count % 100;
    $last_digit = $count % 10;

    if($last_two_digits >= 11 && $last_two_digits <= 19)
      $r .= $five_items_string;
    elseif($last_digit == 1)
      $r .= $one_item_string;
    elseif($last_digit == 2 || $last_digit == 3 || $last_digit == 4)
      $r .= $two_items_string;
    elseif($last_digit == 5 || $last_digit == 6 || $last_digit == 7 || $last_digit == 8 || $last_digit == 9 || $last_digit == 0)
      $r .= $five_items_string;

    return $r;
  }

  public static function formatSize(int $size, bool $bytes = true, ?int $digits = null)
  {
    $base = $bytes ? 1024 : 1000;

    if($size > $base ** 3)
    {
      $suffix = 'G';
      $value = $size / ($base ** 3);
    }
    elseif($size > $base ** 2)
    {
      $suffix = 'M';
      $value = $size / ($base ** 2);
    }
    elseif($size > $base)
    {
      $suffix = 'K';
      $value = $size / $base;
    }
    else
    {
      $suffix = '';
      $value = $size;

      if(is_null($digits))
        $digits = 0;
    }

    if(is_null($digits))
    {
      if($value < 10)
        $digits = 2;
      elseif($value < 100)
        $digits = 1;
      else
        $digits = 0;
    }

    $r = sprintf('%.' . $digits . 'f', $value) . $suffix;

    if($bytes)
      $r .= 'b';

    return $r;
  }

   /**
   * Turns integers array to ranges set.
   *
   * Example: [1,2,3,4,34,47,46,45,48] => "1-4,34,45-48"
   *
   * @param array  $list
   * @param string $separator
   * @return string
   */
  public static function NumbersListToRanges(array $list, string $separator = ', ') : string
  {
    //sort numbers
    sort($list);

    //prepare variables
    $previous = null;
    $start = null;
    $last = end($list);
    $ranges = array();

    foreach ($list as $current)
    {
      //not integer
      if(!is_integer($current))
      {
        $ranges[] = $current;
        continue;
      }

      if(!$start) //first item
      {
        $start = $current;
      }

      if($previous && $current != $previous + 1) //sequence broken
      {
        $ranges[] = self::_rangeToStr($start, $previous);

        //new range started
        $start = $current;
      }

      if($current == $last) //last item
      {
        $ranges[] = self::_rangeToStr($start, $current);
      }

      $previous = $current;
    }

    $r = implode($separator, $ranges);

    return $r;
  }

  private static function _rangeToStr(int $start, int $finish) : string
  {
    if($start == $finish)
      return (string)$start;
    elseif($start == $finish - 1) //just two items, let it be with comma rather then dash
      return $start . ', ' . $finish;
    else
      return $start . '-' . $finish;
  }
}
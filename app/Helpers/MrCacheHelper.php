<?php


namespace App\Helpers;


use Illuminate\Support\Facades\Cache;

class MrCacheHelper extends Cache
{
  /**
   * Load objects array
   *
   * @param string $key
   * @param string $class_name
   * @param callable $ids
   * @return array
   */
  public static function GetCachedObjectList(string $key, string $class_name, callable $ids): array
  {
    $ids = Cache::rememberForever($key, function () use ($ids) {
      return $ids();
    });

    $out = array();
    foreach ($ids as $id)
    {
      $object_name = $class_name;

      /** @var object $object_name */
      $out[] = $object = $object_name::loadBy($id);
    }

    return $out;
  }

  /**
   * @param string $key
   * @param $value
   * @param MrDateTime $time
   */
  public static function SetCachedData(string $key, $value, ?MrDateTime $time)
  {
    if (!$time)
    {
      $time = MrDateTime::now()->AddYears(1);
    }

    Cache::remember($key, $time, function () use ($value) {
      return $value;
    });
  }

  /**
   * получить кэшированные данные
   *
   * @param string $cache_key
   * @param callable $data
   * @return mixed
   */
  public static function GetCachedData(string $cache_key, callable $data)
  {
    return Cache::rememberForever($cache_key, function () use ($data) {
      return $data();
    });
  }


  /**
   * Получение объекта по ID из кэша
   *
   * @param int $id
   * @param string $table
   * @param callable $object
   * @return mixed
   */
  public static function GetCachedObject(int $id, string $table, callable $object): ?object
  {
    $cache_key = $table . '_' . $id;
    return Cache::rememberForever($cache_key, function () use ($object) {
      return $object();
    });
  }
}
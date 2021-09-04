<?php

namespace App\Helpers;

/*
 * Common helpers
 * */

use App\Http\Controllers\Controller;

abstract class MrBaseHelper extends Controller
{
  const MR_EMAIL = 'support@crypto-change.ml';
  const MR_SITE_NAME = 'crypto-change';
  const MR_DOMAIN = 'crypto-change';
  const MR_SITE_URL = 'https://crypto-change.ml';

  const ADMIN_PHONE = '375297896282';
  const ADMIN_PHONE_FORMAT = '+375(29)789-62-82';
  const ADMIN_TELEGRAM = 'tg://resolve?domain=Allximik50';
  const ADMIN_VIBER = 'viber://chat?number=375297896282';

  /**
   * Generate short link
   *
   * @param string $url
   * @return string
   */
  public static function GetShortLink(string $url): string
  {
    return @file_get_contents("https://clck.ru/--?url=" . $url) ?? $url;
  }

  /**
   * Send Mail
   *
   * @param string $email_to
   * @param string $subject
   * @param string $message
   * @return bool
   */
  public static function SendEmail(string $email_to, string $subject, string $message): bool
  {
    if (preg_match('/\S+@\S+\.\S+/', $email_to))
    {
      $headers = "Content-type: text/html; charset=UTF8 \r\n";
      $headers .= "From: " . MrBaseHelper::MR_EMAIL . "\r\n";

      return mail($email_to, $subject, $message, $headers);
    }

    return false;
  }

  /**
   * Generate random str
   *
   * @param int $length
   * @return string
   */
  public static function GenerateRandomString($length = 10): string
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++)
    {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
  }

  /**
   * Generate link for new user
   *
   * @param string $soul
   * @return string
   */
  public static function GetLinkForNewUser(string $soul)
  {
    return self::MR_SITE_URL . '/newuser/' . $soul;
  }
}



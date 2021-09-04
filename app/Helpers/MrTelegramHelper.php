<?php


namespace App\Helpers;


use App\Http\Controllers\Controller;

class MrTelegramHelper extends Controller
{
  const LENGTH_CODE = 4;

  public static function sendMeByTelegram($text)
  {
    // сюда нужно вписать токен вашего бота
    define('TELEGRAM_TOKEN', '906843257:AAFJRFj08A1uEq2QtDo3iFRWSIK3vIm6CUg');

    // сюда нужно вписать ваш внутренний айдишник
    define('TELEGRAM_CHATID', '488545536');

    $ch = curl_init();
    curl_setopt_array(
      $ch,
      array(
        CURLOPT_URL => 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/sendMessage',
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => array(
          'chat_id' => TELEGRAM_CHATID,
          'text' => $text,
        ),
      )
    );
    curl_exec($ch);
  }
}
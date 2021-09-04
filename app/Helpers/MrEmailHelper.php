<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\MrEmailLog;
use App\Models\MrNewUsers;
use App\Models\MrUser;
use App\Models\Office\MrOffice;
use Illuminate\Support\Facades\Artisan;

class MrEmailHelper extends Controller
{
  /**
   * @param $new_user
   * @return bool
   */
  public static function SendNewUser(MrNewUsers $new_user): bool
  {
    $user = MrUser::me();

    $data = array(
      'link'     => MrNewUsers::GetLinkForNewUser($new_user->getCode()),
      'title'    => __('mr-t.Новый пользователь в системе') . ' ' . MrBaseHelper::MR_SITE_NAME,
      'email_to' => $new_user->getEmail(),
      'template' => 'email_new_user',
      'system'   => MrBaseHelper::MR_SITE_NAME,
      'new_user' => $new_user
    );

    dispatch((new SendEmailJob($data)))->afterResponse();
    Artisan::call('queue:work --once');

    // В лог
    $log = new MrEmailLog();
    $log->setAuthorUserID($user->id());
    $log->setEmailTo($new_user->getEmail());
    $log->setTitle(MrBaseHelper::MR_SITE_NAME);
    $log->setText($data['template']);
    $log->save_mr();

    return true;
  }

  /**
   * Отправка почты для нового пользователя добавленного из уже зарегистрированных
   *
   * @param int $office_id Office
   * @param string $email
   */
  public static function SendNewUserRole(int $office_id, string $email)
  {
    $system = MrBaseHelper::MR_SITE_NAME;
    $site_url = MrBaseHelper::MR_SITE_URL;
    $link_login = $site_url . '/login';
    $user = MrUser::me();
    $subject = __('mr-t.Предоставление доступа к системе') . ' ' . MrBaseHelper::MR_SITE_NAME;

    $data = array(
      'user'       => $user,
      'title'      => $subject,
      'email_to'   => $email,
      'office'     => MrOffice::loadBy($office_id),
      'template'   => 'email_has_user',
      'link_login' => $link_login,
      'system'     => $system,
    );

    dispatch((new SendEmailJob($data)))->afterResponse();
    Artisan::call('queue:work --once');

    // В лог
    $log = new MrEmailLog();
    $log->setAuthorUserID($user->id());
    $log->setEmailTo($email);
    $log->setTitle($subject);
    $log->setText($data['template']);
    $log->save_mr();
  }
}
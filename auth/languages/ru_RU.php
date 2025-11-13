<?php
$lang = array();

$lang['user_blocked'] = "Ваша учётная запись заблокирована.";
$lang['user_verify_failed'] = "Защитный код недействителен.";

$lang['email_password_invalid'] = "Недопустимые email или пароль.";
$lang['email_password_incorrect'] = "Неверный email или пароль.";
$lang['remember_me_invalid'] = 'Cookie не подходит.';

$lang['password_short'] = "Пароль слишком короткий.";
$lang['password_weak'] = "Пароль слишком ненадежный.";
$lang['password_nomatch'] = "Пароли не совпадают.";
$lang['password_changed'] = "Пароль успешно изменен.";
$lang['password_incorrect'] = "Текущий пароль указан неверно.";
$lang['password_notvalid'] = "Недопустимый пароль.";

$lang['newpassword_short'] = "Новый пароль слишком короткий.";
$lang['newpassword_long'] = "Новый пароль слишком длинный.";
$lang['newpassword_invalid'] = "Новый пароль должен содержать хотя бы одну цифру, хотя бы одну строчную букву и хотя бы одну прописную.";
$lang['newpassword_nomatch'] = "Новые пароли не совпадают.";
$lang['newpassword_match'] = "Новый пароль такой же, как старый.";

$lang['email_short'] = "Email слишком короткий.";
$lang['email_long'] = "Email слишком длинный.";
$lang['email_invalid'] = "Неверный email.";
$lang['email_incorrect'] = "Неверный email.";
$lang['email_banned'] = "Этот email запрещен.";
$lang['email_changed'] = "Email успешно изменен.";
$lang['email_taken'] = "Этот email уже используется!";

$lang['newemail_match'] = "Новый email совпадает со старым.";

$lang['account_inactive'] = "Учётная запись еще не активирована.";
$lang['account_activated'] = "Учётная запись активирована.";

$lang['logged_in'] = "Вы вошли в систему.";
$lang['logged_out'] = "Вы вышли из системы.";

$lang['system_error'] = "Произошла системная ошибка (проблема с cookies, сессией или базой данных). Попробуйте еще раз.";

$lang['register_success'] = "Учётная запись создана. На ваш email отправлена инструкция по активации.";
$lang['register_success_emailmessage_suppressed'] = "Учётная запись создана.";

$lang['resetkey_invalid'] = "Неверный код сброса пароля.";
$lang['resetkey_incorrect'] = "Неверный код сброса пароля.";
$lang['resetkey_expired'] = "Код сброса пароля устарел.";

$lang['activationkey_invalid'] = "Недопустимый ключ акцивации учётной записи.";
$lang['activationkey_incorrect'] = "Неверный ключ акцивации учётной записи.";
$lang['activationkey_expired'] = "Срок действия ключа активации истёк!";

$lang['reset_requested'] = "Запрос на сброс пароля выслан на email.";
$lang['reset_requested_emailmessage_suppressed'] = "Запрос сброса пароля создан.";
$lang['reset_exists'] = "Сброс пароля уже запрошен.";
$lang['password_reset'] = "Пароль изменён.";

$lang['already_activated'] = "Учётная запись уже активирована.";
$lang['activation_sent'] = "Сообщение с инструкцией по активации учётной записи выслано.";
$lang['activation_exists'] = "Мы уже высылали вам сообщение с инструкцией по активации учётной записи.";

$lang['email_activation_subject'] = "%s — активация учётной записи";
$lang['email_activation_body'] = 'Здравствуйте,<br/><br/>для входа в систему вам нужно сначала активировать вашу учётную запись. Перейдите, пожалуйста, по этой ссылке: <strong><a href="%1$s/%2$s/%3$s">%1$s/%2$s/%3$s</a></strong><br/><br/> Если не регистрировались на сайте %1$s, значит это сообщение вы получили по ошибке. Пожалуйста, проигнорируйте его.';
$lang['email_activation_altbody'] = 'Здравствуйте, \n\n для входа в систему вам нужно сначала активировать вашу учётную запись. Перейдите, пожалуйста, по этой ссылке: \n %1$s/%2$s/%3$s \n\n Если не регистрировались на сайте %1$s, значит это сообщение вы получили по ошибке. Пожалуйста, проигнорируйте его.';
$lang['email_reset_subject'] = "%s — запрос сброса пароля";
$lang['email_reset_body'] = 'Здравствуйте,<br/><br/>Для сброса вашего пароля пройдите, пожалуйста, по этой ссылке:<br/><br/><strong><a href="%1$s/%2$s/%3$s">%1$s/%2$s/%3$s</a></strong><br/><br/>Если вы недавно не запрашивали сброс пароля на сайте %1$s, значит это сообщение вы получили по ошибке. Пожалуйста, проигнорируйте его.';
$lang['email_reset_altbody'] = 'Здравствуйте, \n\n Для сброса вашего пароля пройдите, пожалуйста, по этой ссылке: \n %1$s/%2$s/%3$s\n\n Если вы недавно не запрашивали сброс пароля на сайте %1$s, значит это сообщение вы получили по ошибке. Пожалуйста, проигнорируйте его.';

$lang['account_deleted'] = "Учётная запись удалена.";
$lang['function_disabled'] = "Эта функция была отключена.";
$lang['account_not_found'] = "Не найдена учётная запись с таким электронным адресом.";

return $lang;

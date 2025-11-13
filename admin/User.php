<?php

declare(strict_types=1);

namespace Admin;

use Soundasleep\Html2Text;
use URLSafeTimedSerializer\URLSafeTimedSerializer;

class User
{
    protected $config;
    protected $db;

    public function __construct(\PDO $db, array &$config)
    {
        $this->config = &$config;
        $this->db = &$db;
    }

    public function getRoleByIds($role_ids, $lang = null)
    {
        if (!is_array($role_ids)) {
            $role_ids = [$role_ids];
        }

        if (count($role_ids)) {
            $r = $this->db->prepare("select r.*
from {$this->config['auth']['table_roles']} r
left join language on (language.name = ? and language.active is true)
left join {$this->config['auth']['table_roles_langs']} rl on (rl.user_role_id = r.id and rl.language_id = language.id)
where r.id in (" . str_repeat('?,', count($role_ids) - 1) . '?)
order by rl.title');

            $r->execute(array_merge([$lang], $role_ids));
            return $r->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function getUserRoleById($user_id, $lang = null)
    {
        if ($user_id) {
            $r = $this->db->prepare(
                "select r.*, rl.title
from {$this->config['auth']['table_roles']} r
join {$this->config['auth']['table_roles_users']} m on (m.role_id = r.id)
left join language on (language.name = :lang and language.active is true)
left join {$this->config['auth']['table_roles_langs']} rl on (rl.user_role_id = r.id and rl.language_id = language.id)
where m.user_id = :user_id
order by rl.title"
            );

            $r->execute([
                ':lang' => $lang,
                ':user_id' => $user_id,
            ]);

            return $r->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function register($email, $email_activation, $lang = null, $throttle = true)
    {
        if ($email) {
            $email = htmlentities(strtolower($email));

            $r = $this->db->prepare("select * from {$this->config['auth']['table_users']} where email = ?");
            $r->execute([$email]);
            $user_data = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$user_data) {
                $r = $this->db->prepare("insert into {$this->config['auth']['table_users']} (email) values (?)");
                $r->execute([$email]);

                $user_data = ['id' => $this->db->lastInsertId("{$this->config['auth']['table_users']}_id_seq")];
            }

            if (empty($user_data['isactive']) && $email_activation) {
                if ($throttle && !Throttle::save($this->db, sprintf('%s:a', mb_substr($email, 0, 100)), 1, $this->config['user_activation_throttle_time'])) {
                    return sprintf(__('The account activation message has been already sent to %s recently. Try to sent account activation message later.'), $email);
                }

                $activation_token = '';

                try {
                    $activation_token = (new URLSafeTimedSerializer($this->config['user_activation_key'], (int) $this->config['user_activation_key_expire']))->generate($user_data['id'], false);
                } catch (Exception $e) {
                    error_log((string) $e);
                }

                $r = $this->db->prepare(
                    'select mail_template.*
from mail_template
left join language on (language.id = mail_template.language_id and language.active is true)
where mail_template.name = ? and language.name = ?
limit 1'
                );

                $r->execute(['user_activation', $lang]);
                $mail_template = $r->fetch(\PDO::FETCH_ASSOC);

                if (!$mail_template) {
                    return __('No mail template');
                }

                $mail = new \PHPMailer\PHPMailer\PHPMailer();
                $mail->CharSet = $this->config['mailing']['charset'];

                if (!empty($this->config['mailing']['smtp']) && !empty($this->config['mailing']['smtp_host'])) {
                    $mail->isSMTP();
                    $mail->Host = $this->config['mailing']['smtp_host'];

                    if (!empty($this->config['mailing']['smtp_username']) && !empty($this->config['mailing']['smtp_password'])) {
                        $mail->SMTPAuth = true;
                        $mail->Password = $this->config['mailing']['smtp_password'];
                        $mail->Username = $this->config['mailing']['smtp_username'];
                    } else {
                        $mail->SMTPAuth = false;
                    }

                    $mail->Port = $this->config['mailing']['smtp_port'] ?? 0;
                    $mail->SMTPSecure = $this->config['mailing']['smtp_security'] ?? false;
                }

                $mail->setFrom($this->config['mailing']['from_email'], $this->config['mailing']['from_name']);
                $mail->addAddress(mb_substr($email, 0, 100));

                if (!empty($this->config['mailing']['reply_email'])) {
                    $mail->addReplyTo($this->config['mailing']['reply_email'], $this->config['mailing']['reply_name'] ?? '');
                }

                $mail->isHTML(true);

                $mail->Subject = strtr(
                    $mail_template['subject'],
                    [
                        '{{activation_url}}' => $this->config['user_activation_url'] . $activation_token,
                        '{{email}}' => mb_substr($email, 0, 100),
                    ]
                );

                $mail->Body = strtr(
                    $mail_template['text'],
                    [
                        '{{activation_url}}' => $this->config['user_activation_url'] . $activation_token,
                        '{{email}}' => mb_substr($email, 0, 100),
                    ]
                );

                $mail->AltBody = Html2Text::convert($mail->Body);

                if (!$mail->send()) {
                    error_log($mail->ErrorInfo);
                    return __('Mail error');
                }

                return true;
            }
        }
    }

    public function updateUserRole($user_id, $role_ids)
    {
        if (is_array($role_ids)) {
            $r = $this->db->prepare(
                "insert into {$this->config['auth']['table_roles_users']} (role_id, user_id)
(select r.id, u.id from {$this->config['auth']['table_roles']} r, {$this->config['auth']['table_users']} u where r.id = :role_id and u.id = :user_id)
on conflict (role_id, user_id) do update set role_id = excluded.role_id"
            );

            $a = [];
            foreach ($role_ids as $x) {
                $x = (int) $x;
                if ($x) {
                    $a[] = $x;
                    $r->execute([
                        ':role_id' => (int) $x,
                        ':user_id' => $user_id,
                    ]);
                }
            }
            $r = $this->db->prepare("delete from {$this->config['auth']['table_roles_users']} where user_id = ?" . (count($a) ? ' and role_id not in (' . str_repeat('?,', count($a) - 1) . '?)' : ''));
            $r->execute(array_merge([$user_id], $a));
        }
    }
}

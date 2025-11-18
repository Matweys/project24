<?php

namespace Admin;

use Helpers\Form;
use ZxcvbnPhp\Zxcvbn;

class UserController extends BaseController
{
    protected $default_url;
    protected $lookup_page_size = 50;

    public function action()
    {
        $this->current_user = $this->auth->rolesRequired('admin', 'user_management');
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->default_url = $this->config['base_url'].'/users/';
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $ids = ($id ? [$id] : filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY));

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            if (($_POST['action'] ?? null) === 'action_delete') {
                try {
                    if ($ids) {
                        try {
                            $r = $this->db->prepare("delete from {$this->config['auth']['table_users']} where id in (".str_repeat('?,', count($ids) - 1).'?)');
                            $r->execute($ids);
                        } catch (PDOException $e) {
                            flash(__('Database error.'), 'error');
                            error_log((string) $e);
                            throw new ElseException();
                        }
                        if ($r->rowCount()) {
                            flash(sprintf(_n('User has been deleted.', '%d users have been deleted.', $r->rowCount()), $r->rowCount()));
                        }
                    }
                } catch (ElseException $e) {
                }
            }
        }

        header('Location: '.$return_url);
    }

    public function create()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'user_management']);
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->default_url = $this->config['base_url'].'/users/';
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $form_errors = [];

        $data = self::filterInput();

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $this->form_validate($data, $form_errors);

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        "insert into {$this->config['auth']['table_users']} (
    description,
    email,
    isactive,
    name,
    password
)
values (
    :description,
    :email,
    :isactive,
    :name,
    :password
)"
                    );
                    $r->execute([
                        ':description' => $data['description'] ?: null,
                        ':email' => htmlentities(strtolower($data['email'])),
                        ':isactive' => $data['active'],
                        ':name' => $data['name'] ?: null,
                        ':password' => \Auth\Auth::getHash($data['password'], $this->config['auth']['bcrypt_cost']),
                    ]);

                    $id = $this->db->lastInsertId("{$this->config['auth']['table_users']}_id_seq");

                    $this->User->updateUserRole($id, $data['role'] ?: []);

                    $this->db->commit();
                } catch (PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    $this->db->rollBack();
                }
                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'user_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => $this->default_url,
                'form_data' => Form::getFormData(
                    [
                        'active',
                        'description',
                        'email',
                        'name',
                    ],
                    [
                        'active' => 1,
                    ]
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }

    public function edit()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'user_management']);
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->default_url = $this->config['base_url'].'/users/';
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $id = (int) ($_GET['id'] ?? 0);

        $user_data = null;
        $widget_data = [];

        if ($id) {
            try {
                $r = $this->db->prepare("select *, isactive active from {$this->config['auth']['table_users']} where id = ?");
                $r->execute([$id]);
                $user_data = $r->fetch(\PDO::FETCH_ASSOC);

                if ($user_data) {
                    $user_data['role'] = $this->User->getUserRoleById($id, $this->lang['name'] ?? null);
                }
            } catch (PDOException $e) {
                flash(__('Database error.'), 'error');
                error_log((string) $e);
                header('Location: '.$return_url);
                return;
            }
        }

        if (!$user_data) {
            http_response_code(404);
            return;
        }

        $form_errors = [];

        $data = self::filterInput();

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $this->form_validate($data, $form_errors, $id);

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        "update {$this->config['auth']['table_users']}
set
    description = :description,
    email = :email,
    isactive = :isactive,
    modified = now(),
    name = :name
where id = :id"
                    );
                    $r->execute([
                        ':description' => $data['description'] ?: null,
                        ':email' => htmlentities(strtolower($data['email'])),
                        ':id' => $id,
                        ':isactive' => $data['active'],
                        ':name' => $data['name'] ?: null,
                    ]);

                    if ($data['password']) {
                        $r = $this->db->prepare("update {$this->config['auth']['table_users']} set password = ? where id = ?");
                        $r->execute([\Auth\Auth::getHash($data['password'], $this->config['auth']['bcrypt_cost']), $id]);
                    }

                    $this->User->updateUserRole($id, $data['role'] ?: []);

                    $this->db->commit();
                } catch (PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    $this->db->rollBack();
                }
                header('Location: '.$return_url);
                return;
            }
            $widget_data['role'] = [];
            $roles = $this->User->getRoleByIds($data['role']);
            if (is_array($roles)) {
                foreach ($roles as $x) {
                    $widget_data['role'][] = [$x['id'], $x['title']];
                }
            }
        } else {
            $widget_data['role'] = [];
            if (is_array($user_data['role'])) {
                foreach ($user_data['role'] as $x) {
                    $widget_data['role'][] = [$x['id'], $x['title']];
                }
            }
        }

        render_template(
            'user_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => $this->default_url,
                'data' => $user_data,
                'form_data' => Form::getFormData(
                    [
                        'active',
                        'description',
                        'email',
                        'name',
                    ],
                    $user_data
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
                'widget_data' => $widget_data,
            ],
            __DIR__.'/templates/'
        );
    }

    public function index()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'user_management']);
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->default_url = $this->config['base_url'].'/users/';
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $page = (int) ($_GET['p'] ?? 0);
        $page_size = (int) ($this->current_user['settings']['page_size'] ?? 0);
        $search_query = (string) ($_GET['q'] ?? '');
        $sort_desc = (int) ($_GET['desc'] ?? 0);
        $sort_idx = (int) ($_GET['sort'] ?? 0);

        if ($search_query) {
            $search_query = mb_substr($search_query, 0, 200);
        }

        if ($search_query) {
            $r = $this->db->prepare("select count(id) from {$this->config['auth']['table_users']} where search @@ plainto_tsquery('russian', ?)");
            $r->execute([$search_query]);
        } else {
            $r = $this->db->prepare("select count(id) from {$this->config['auth']['table_users']}");
            $r->execute();
        }

        $count = $r->fetchColumn();

        $num_pages = $page_size ? ceil($count / $page_size) : 0;
        $offset = (int) max(0, min($page, $num_pages - 1) * $page_size);

        $sort_fields = [
            ['u.email', 'u.email DESC'],
            ['u.name', 'u.name DESC'],
            ['roles.roles, u.email', 'roles.roles DESC, u.email'],
            ['u.isactive, u.email', 'u.isactive DESC, u.email'],
        ];

        $sort_by = isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : ($search_query ? "ts_rank(search, plainto_tsquery('russian', :search)) DESC" : 'u.email');

        $r = $this->db->prepare(
            "select u.*, u.isactive active, roles.roles
from {$this->config['auth']['table_users']} u
left join (
    select rt.user_id, string_agg(distinct rt.role_title, '. ' order by rt.role_title) as roles
    from (
        select m.user_id, 
               (select rl.title from {$this->config['auth']['table_roles_langs']} rl 
                join language l on (l.id = rl.language_id and l.id = :language_id)
                where rl.user_role_id = r.id 
                limit 1) as role_title
        from {$this->config['auth']['table_roles']} r
        join {$this->config['auth']['table_roles_users']} m on (m.role_id = r.id)
    ) rt
    where rt.role_title is not null
    group by rt.user_id
) roles on roles.user_id = u.id
".($search_query ? "where search @@ plainto_tsquery('russian', :search)" : '')."
order by {$sort_by}
limit :limit offset :offset"
        );

        if ($search_query) {
            pdo_bind_param($r, ':search', $search_query);
        }

        pdo_bind($r, [
            ':language_id' => $this->lang['id'] ?? null,
            ':limit' => $page_size,
            ':offset' => $offset,
        ]);

        $r->execute();
        $data = $r->fetchAll(\PDO::FETCH_ASSOC);

        $pager_url = function ($p) use ($search_query, $sort_desc, $sort_idx) {
            return (function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'desc' => $sort_desc ? 1 : null,
                'p' => $p ?: null,
                'q' => $search_query,
                'sort' => $sort_idx,
            ]);
        };

        $sort_url = function ($column, $invert = false) use ($page, $search_query, $sort_desc) {
            return (function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'desc' => ($invert && !$sort_desc) ? 1 : null,
                'p' => $page ?: null,
                'q' => $search_query,
                'sort' => $column,
            ]);
        };

        render_template(
            'users',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'clear_search_url' => (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query([
                    'desc' => $sort_desc ? 1 : null,
                    'p' => $page ?: null,
                    'q' => null,
                    'sort' => $sort_idx,
                ]),
                'count' => $count,
                'data' => $data,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'page' => $page,
                'page_size' => $page_size,
                'pager_url' => $pager_url,
                'return_url' => (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query([
                    'desc' => $sort_desc ? 1 : null,
                    'p' => $page ?: null,
                    'q' => $search_query,
                    'sort' => $sort_idx,
                ]),
                'search_query' => $search_query,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
                'sort' => $sort_idx,
                'sort_desc' => $sort_desc,
                'sort_url' => $sort_url,
            ],
            __DIR__.'/templates/'
        );
    }

    public function roleLookup()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'user_management']);
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $q = (string) ($_GET['q'] ?? '');
        $page = (int) ($_GET['page'] ?? 1);

        if ($page < 1) {
            $page = 1;
        }

        $r = $this->db->prepare(
            "select count(r.id)
from {$this->config['auth']['table_roles']} r
left join language on (language.name = :lang and language.active is true)
left join {$this->config['auth']['table_roles_langs']} rl on (rl.user_role_id = r.id and rl.language_id = language.id)
where rl.title like :q"
        );

        $r->execute([
            ':lang' => $this->lang['name'] ?? null,
            ':q' => '%'.$q.'%',
        ]);

        $count = (int) $r->fetchColumn();

        $offset = ($page - 1) * $this->lookup_page_size;

        $r = $this->db->prepare(
            "select r.*, rl.title
from {$this->config['auth']['table_roles']} r
left join language on (language.name = :lang and language.active is true)
left join {$this->config['auth']['table_roles_langs']} rl on (rl.user_role_id = r.id and rl.language_id = language.id)
where rl.title ilike :q
limit :limit offset :offset"
        );

        pdo_bind($r, [
            ':lang' => $this->lang['name'] ?? null,
            ':limit' => $this->lookup_page_size,
            ':offset' => $offset,
            ':q' => '%'.$q.'%',
        ]);

        $r->execute();

        $data = $r->fetchAll(\PDO::FETCH_ASSOC);
        $data = is_array($data) ? array_map(function ($v) {
            return [
                'id' => $v['id'],
                'text' => $v['title'],
            ];
        }, $data) : [];

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'data' => $data,
            'more' => $page < ceil($count / $this->lookup_page_size),
        ], JSON_UNESCAPED_UNICODE);
    }

    protected static function filterInput()
    {
        return Form::filterInput([
            'active' => FILTER_VALIDATE_BOOLEAN,
            'description' => null,
            'email' => FILTER_VALIDATE_EMAIL,
            'name' => null,
            'password' => FILTER_DEFAULT,
            'role' => [FILTER_VALIDATE_INT, null, FILTER_REQUIRE_ARRAY],
        ]);
    }

    protected function form_validate(&$data, &$errors, $id = null)
    {
        if (empty($data['email'])) {
            $errors['email'] = __('Enter Email.');
        } elseif ($data['email']) {
            $r = $this->db->prepare("select id from {$this->config['auth']['table_users']} where email = ?".($id ? ' and id <> ?' : ''));
            $r->execute($id ? [htmlentities(strtolower($data['email'])), $id] : [htmlentities(strtolower($data['email']))]);

            if ($r->fetchColumn()) {
                $errors['email'] = __('A user with this email already exists.');
            }
        }

        if (!empty($data['password'])) {
            if (strlen($data['password']) < (int) $this->config['auth']['verify_password_min_length']) {
                $errors['password'] = __('Password is too short.');
            }

            if ((new Zxcvbn())->passwordStrength($data['password'])['score'] < (int) $this->config['auth']['password_min_score']) {
                $errors['password'] = __('Password is too weak.');
            }
        }
    }
}

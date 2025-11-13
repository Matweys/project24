<?php

namespace Admin;

class LogController extends BaseController
{
    public function index()
    {
        $this->current_user = $this->auth->rolesRequired(['admin']);
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $page = (int) ($_GET['p'] ?? 0);
        $page_size = (int) ($this->current_user['settings']['page_size'] ?? 0);
        $search_query = (string) ($_GET['q'] ?? '');
        $sort_desc = (int) ($_GET['desc'] ?? 0);
        $sort_idx = $_GET['sort'] ?? null;

        if ($search_query) {
            $search_query = mb_substr($search_query, 0, 200);
        }

        if ($search_query) {
            $r = $this->db->prepare("select count(id) from log where search @@ plainto_tsquery('russian', ?)");
            $r->execute([$search_query]);
        } else {
            $r = $this->db->prepare('select count(id) from log');
            $r->execute();
        }

        $count = $r->fetchColumn();

        $num_pages = $page_size ? ceil($count / $page_size) : 0;
        $offset = (int) max(0, min($page, $num_pages - 1) * $page_size);

        $sort_fields = [
            ['created', 'created desc'],
        ];

        $sort_by = isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : ($search_query ? "ts_rank(search, plainto_tsquery('russian', :search)) desc" : 'created desc');

        $r = $this->db->prepare(
            'select * from log
'.($search_query ? "where search @@ plainto_tsquery('russian', :search)" : '')."
order by {$sort_by}
limit :limit offset :offset"
        );

        if ($search_query) {
            pdo_bind_param($r, ':search', $search_query);
        }

        pdo_bind($r, [
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
            'log',
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
}

<?php

declare(strict_types=1);

namespace Main;

class StorageLogController extends BaseController
{
    public function index($storage_uid = null)
    {
        $current_user = $this->auth->loginRequired();
        $Storage = new Storage($this->db);
        $storages = $Storage->getAllowedStorages($current_user['id']);

        $lang = $this->language->getCurrentLanguage($current_user['id']);
        $this->load_translation($lang['name'] ?? null);
        $this->setting->load($lang['name'] ?? null);

        $storage = $Storage->getStorage(null, $storage_uid, $current_user['id']);
        $file_table = $storage ? $Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || ($storage['permission_name'] ?? null) !== 'full') {
            http_response_code(404);
            return;
        }

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';

        $page = (int) ($_GET['p'] ?? 0);
        $page_size = (int) ($current_user['settings']['page_size'] ?? 0);
        $search_query = (string) ($_GET['q'] ?? '');
        $sort_desc = (int) ($_GET['desc'] ?? 0);
        $sort_idx = $_GET['sort'] ?? null;

        if ($search_query) {
            $search_query = mb_substr($search_query, 0, 200);
        }

        if ($search_query) {
            $r = $this->db->prepare("select count(id) from log where search @@ plainto_tsquery('russian', ?) and storage_id = ?");
            $r->execute([$search_query, $storage['id']]);
        } else {
            $r = $this->db->prepare('select count(id) from log where storage_id = ?');
            $r->execute([$storage['id']]);
        }

        $count = $r->fetchColumn();

        $num_pages = $page_size ? ceil($count / $page_size) : 0;
        $offset = (int) max(0, min($page, $num_pages - 1) * $page_size);

        $sort_fields = [
            ['created', 'created desc'],
        ];

        $sort_by = isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : ($search_query ? "ts_rank(search, plainto_tsquery('russian', :search)) desc" : 'created desc');

        $r = $this->db->prepare('select * from log where '.($search_query ? "search @@ plainto_tsquery('russian', :search) and " : '')." storage_id = :storage_id order by {$sort_by} limit :limit offset :offset");

        if ($search_query) {
            pdo_bind_param($r, ':search', $search_query);
        }

        pdo_bind($r, [
            ':limit' => $page_size,
            ':offset' => $offset,
            ':storage_id' => $storage['id'],
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
            'storage_log',
            [
                'config' => &$this->config,
                'current_user' => &$current_user,
                'storages' => &$storages,
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
                'lang' => $lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'page' => $page,
                'page_size' => $page_size,
                'pager_url' => $pager_url,
                'return_url' => $return_url,
                'search_query' => $search_query,
                'search_url' => (function () {
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
                'sort' => $sort_idx,
                'sort_desc' => $sort_desc,
                'sort_url' => $sort_url,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $lang['name'] ?? ''),
            ],
            __DIR__.'/templates/'
        );
    }
}

<?php

declare(strict_types=1);

namespace Main;

class StorageSearchController extends BaseController
{
    protected $storage;

    public function index($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table) {
            http_response_code(404);
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("SELECT * FROM {$file_table} WHERE id = ? AND folder IS TRUE");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder) {
                http_response_code(404);
                return;
            }
        }

        $upload_config = array_merge($this->config['upload'], [
            'path' => rtrim($this->config['upload']['path'], '/').'/'.$storage['uid'],
            'url' => rtrim($this->config['upload']['url'], '/').'/'.$storage['uid'].'/',
        ]);

        $page = (int) ($_GET['p'] ?? 0);
        $search_mode = (int) ($_GET['mode'] ?? 0);
        $search_query = (string) ($_GET['q'] ?? '');

        $page_size = (int) ($this->current_user['settings']['page_size'] ?? 0);

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/search/'.$storage['uid'].'/'.($folder ? $folder['id'].'/' : '').'?'.http_build_query(['mode' => $search_mode ?: null, 'p' => $page ?: null, 'q' => $search_query ?: null]);

        $clear_search_url = (function () {
            $v = $_SERVER['REQUEST_URI'] ?? '';
            if (($p = strpos($v, '?')) !== false) {
                $v = substr($v, 0, $p);
            }
            return $v;
        })().'?'.http_build_query([
            'mode' => $search_mode ?: null,
            'p' => $page ?: null,
            'q' => null,
        ]);

        $offset = (int) max(0, $page * $page_size);

        $search_query = $search_query ? mb_substr($search_query, 0, 200) : '';

        $sphinx = new \PDO($this->config['sphinx_uri'] ?? null);
        $sphinx->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $folder_ids = null;

        if ($folder && !$search_mode) {
            $r = $this->db->prepare("select id from {$file_table} where lft between ? and ? and folder is true");
            $r->execute([$folder['lft'], $folder['rgt']]);
            $folder_ids = $r->fetchAll(\PDO::FETCH_COLUMN);
        }

        $query = "select * from {$file_table}_main where match(:search_query)".
            (
                $folder_ids ?
                'and parent_id in ('.array_reduce(
                    array_keys($folder_ids),
                    function ($r, $v) {
                        $r .= ($r ? ',' : '').':p'.($v + 1);
                        return $r;
                    },
                    ''
                ).')' :
                ''
            ).
            ' limit :offset, :page_size';

        try {
            $r = $sphinx->prepare($query);

            pdo_bind($r, [
                ':offset' => $offset,
                ':page_size' => $page_size,
                ':search_query' => $search_query,
            ]);

            if ($folder_ids) {
                foreach ($folder_ids as $i => $v) {
                    $r->bindValue(':p'.($i + 1), $v, \PDO::PARAM_INT);
                }
            }

            $r->execute();
        } catch (\PDOException $e) {
            if ('42000' === $e->getCode()) {
                error_log((string) $e);
            } else {
                throw $e;
            }
        }

        $data = [];

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $data[$row['id']] = null;
        }

        $r = $sphinx->prepare('show meta');
        $r->execute();

        $meta = [];

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $meta[$row['Variable_name']] = $row['Value'];
        }

        $count = (int) ($meta['total_found'] ?? 0);
        $num_pages = ceil($count / $page_size);

        if ($data) {
            $r = $this->db->prepare("select * from {$file_table} where id in (".str_repeat('?,', count($data) - 1).'?)');
            $r->execute(array_keys($data));

            while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
                $data[$row['id']] = $row;

                $attribute_text = !empty($storage['attributes']) ?
                    array_reduce(
                        $storage['attributes'],
                        function ($r, $v) use ($row) {
                            $k = 'a'.$v['id'];
                            if (!empty($row[$k])) {
                                $r .= sprintf("%s: %s\n", $v['title'], $row[$k]);
                            }
                            return $r;
                        },
                        ''
                    ) :
                    '';

                $data[$row['id']]['text'] = implode("\n", array_filter([$attribute_text, $row['text']]));
            }

            $data = array_filter($data);
        }

        if ($data) {
            $r = $sphinx->prepare('call snippets(('.str_repeat('?,', count($data) - 1)."?), '{$file_table}_main', ?, 5 as around)");

            pdo_bind($r, array_merge(array_map(function ($v) {
                return $v['text'] ?? '';
            }, $data), [$search_query]));

            $r->execute();

            $snippets = $r->fetchAll(\PDO::FETCH_COLUMN);

            foreach (array_values($data) as $i => $v) {
                if (isset($snippets[$i])) {
                    $data[$v['id']]['snippet'] = $snippets[$i];
                }
            }
        }

        $pager_url = function ($p) use ($search_query) {
            return (function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'p' => $p ?: null,
                'q' => $search_query,
            ]);
        };

        render_template(
            'search_result',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'clear_search_url' => $clear_search_url,
                'count' => $count,
                'data' => $data,
                'folder' => $folder,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'meta' => $meta,
                'page' => $page,
                'page_size' => $page_size,
                'pager_url' => $pager_url,
                'return_url' => $return_url,
                'search_mode' => $search_mode,
                'search_query' => $search_query,
                'storage' => $storage,
                'upload_config' => $upload_config,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }
}

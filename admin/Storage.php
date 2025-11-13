<?php

declare(strict_types=1);

namespace Admin;

class Storage
{
    protected $db;

    public function __construct(\PDO $db)
    {
        $this->db =& $db;
    }

    protected static function _catchWarning($errno, $errstr, $errfile, $errline)
    {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    protected static function _deleteFiles(?array &$config, $files)
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        set_error_handler('static::_catchWarning');

        foreach ($files as $v) {
            if (is_string($v) && $v) {
                $f = rtrim($config['path'], '/') . '/' . $v;
                if (is_file($f)) {
                    try {
                        unlink($f);
                    } catch (\Exception $e) {
                        error_log((string) $e);
                    }
                }
            }
        }

        restore_error_handler();
    }

    public function deleteFileAndFolders(array $upload_config, int $storage_id, array $file_ids, int $max_files_to_delete = 0)
    {
        $file_ids = array_filter(filter_var($file_ids, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY));

        $r = $this->db->prepare('select * from storage where id = ?');
        $r->execute([$storage_id]);
        $storage = $r->fetch(\PDO::FETCH_ASSOC);

        if ($storage && $file_ids) {
            $file_table = $this->getFileTable($storage_id);

            if ($file_table) {
                // Получаем количество всех отмеченных и вложенных в отмеченные каталоги файлов

                $r = $this->db->prepare(
                    "select count(file.id)
from $file_table file
left join $file_table folder on folder.id = file.parent_id
join $file_table src on src.id in (" . str_repeat('?,', count($file_ids) - 1) . '?)
where
    (folder.lft between src.lft and src.rgt
        or file.parent_id = src.id
        or file.id = src.id)
    and file.folder is not true'
                );

                $r->execute($file_ids);
                $count_to_delete = $r->fetchColumn();

                // Получаем порцию имен фалов для удаления

                $r = $this->db->prepare(
                    "select file.id, file.file, file.image
from $file_table file
left join $file_table folder on folder.id = file.parent_id
join $file_table src on src.id in (" . str_repeat('?,', count($file_ids) - 1) . '?)
where
    (folder.lft between src.lft and src.rgt
        or file.parent_id = src.id
        or file.id = src.id)
    and file.folder is not true
limit ' . (int) $max_files_to_delete
                );

                $r->execute($file_ids);
                $files = $r->fetchAll(\PDO::FETCH_ASSOC);

                $upload_config = array_merge($upload_config, [
                    'path' => rtrim($upload_config['path'], '/') . '/' . $storage['uid'],
                    'url' => rtrim($upload_config['url'], '/') . '/' . $storage['uid'] . '/',
                ]);

                if ($count_to_delete > count($files)) {
                    // Все файлы за раз удалить не получится
                    // Поэтому удаляем порцию файлов, а каталоги не трогаем

                    $file_ids_to_delete = array_column($files, 'id');

                    $r = $this->db->prepare("delete from $file_table where id in (" . str_repeat('?,', count($file_ids_to_delete) - 1) . '?)');
                    $r->execute($file_ids_to_delete);

                    static::_deleteFiles($upload_config, array_column($files, 'file'));
                    static::_deleteFiles($upload_config, array_column($files, 'image'));

                    return count($files);
                } else {
                    // Все файлы удаляем за раз

                    // Удаляем каталоги из дерева

                    $r = $this->db->prepare("select id from $file_table where id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and folder is true');
                    $r->execute($file_ids);
                    $folder_ids_to_delete = $r->fetchAll(\PDO::FETCH_COLUMN);

                    if ($folder_ids_to_delete) {
                        $r = $this->db->prepare('call storage_delete_folder(:storage_id, :folder_id)');

                        foreach ($folder_ids_to_delete as $v) {
                            $r->execute([
                                ':folder_id' => $v,
                                ':storage_id' => $storage['id'],
                            ]);
                        }
                    }

                    // Удаляем файлы и вложенные каталоги (вложенные файлы удалятся по foreign key cascade)

                    $r = $this->db->prepare("delete from $file_table where id in (" . str_repeat('?,', count($file_ids) - 1) . '?)');
                    $r->execute($file_ids);

                    static::_deleteFiles($upload_config, array_column($files, 'file'));
                    static::_deleteFiles($upload_config, array_column($files, 'image'));

                    return count($files);
                }
            }
        }
    }

    public function deleteFiles(array $upload_config, int $storage_id, array $file_ids)
    {
        $r = $this->db->prepare('select * from storage where id = ?');
        $r->execute([$storage_id]);
        $storage = $r->fetch(\PDO::FETCH_ASSOC);

        if ($storage) {
            $file_table = $this->getFileTable($storage_id);

            if ($file_table) {
                $upload_config = array_merge($upload_config, [
                    'path' => rtrim($upload_config['path'], '/') . '/' . $storage['uid'],
                    'url' => rtrim($upload_config['url'], '/') . '/' . $storage['uid'] . '/',
                ]);

                $r = $this->db->prepare("select file, image from $file_table where id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and folder is not true');
                $r->execute($file_ids);
                $files = $r->fetchAll(\PDO::FETCH_ASSOC);

                if ($files) {
                    $r = $this->db->prepare("delete from $file_table where id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and folder is not true');
                    $r->execute($file_ids);

                    static::_deleteFiles($upload_config, array_column($files, 'file'));
                    static::_deleteFiles($upload_config, array_column($files, 'image'));

                    return $r->rowCount();
                }
            }
        }
    }

    public function getAllowedStorages(int $user_id)
    {
        if ($user_id) {
            $r = $this->db->prepare(
                'select storage.*, m.*, p.name as permission_name
from storage_user_permission m
join storage on storage.id = m.storage_id
join storage_permission p on p.id = m.permission_id
where m.user_id = ?
order by storage.title, storage.id'
            );
            $r->execute([$user_id]);

            $rv = $r->fetchAll(\PDO::FETCH_ASSOC);

            if ($rv && count($rv) === 1) {
                $table = $this->getFileTable($rv[0]['id']);

                $r = $this->db->prepare("select id, name from $table where folder is true and parent_id is null order by name");
                $r->execute();

                $rv[0]['folders'] = $r->fetchAll(\PDO::FETCH_ASSOC);
            }

            return $rv;
        }
    }

    public function getAttributes(int $storage_id)
    {
        if ($storage_id) {
            $r = $this->db->prepare('select *, type_id as type from storage_attribute where storage_id = ? order by sort, id');
            $r->execute([$storage_id]);
            return $r->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function getAttributeTypes(?string $lang = null)
    {
        $r = $this->db->prepare(
            'select storage_attribute_type.*, storage_attribute_type_lang.title
from storage_attribute_type
left join language on (language.name = ? and language.active is true)
left join storage_attribute_type_lang on (storage_attribute_type_lang.storage_attribute_type_id = storage_attribute_type.id and storage_attribute_type_lang.language_id = language.id)
order by storage_attribute_type.sort, storage_attribute_type_lang.title'
        );
        $r->execute([$lang]);
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getFileCount(string $table, ?int $folder_id)
    {
        if ($folder_id) {
            $r = $this->db->prepare(
                "select
    count(file.id)
from
    $table file
join $table src on src.id = ?
left join $table folder on folder.id = file.parent_id
where
    folder.lft between src.lft and src.rgt
    and file.folder is not true"
            );

            $r->execute([$folder_id]);
            return $r->fetchColumn();
        } else {
            $r = $this->db->prepare("select count(file.id) from $table file where file.folder is not true");
            $r->execute();
            return $r->fetchColumn();
        }
    }

    public function getBreadcrubms(string $table, ?int $folder_id)
    {
        if ((int) $folder_id) {
            $r = $this->db->prepare(
                "select p.id, p.name
from $table as f, $table p
where
    f.lft between p.lft
    and p.rgt
    and f.folder is true
    and f.id = ?
order by p.lft"
            );

            $r->execute([(int) $folder_id]);

            return $r->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function getFileTable(int $storage_id)
    {
        $table = 'file_' . $storage_id;

        $r = $this->db->prepare('select to_regclass(?)');
        $r->execute([$table]);
        if ($r->fetchColumn()) {
            return $table;
        }
    }

    public function getNextPrevFileIds(string $table, int $file_id, string $order_field)
    {
        $r = $this->db->prepare("select parent_id from $table where id = ?");
        $r->execute([$file_id]);
        $parent_id = $r->fetchColumn();

        $r = $this->db->prepare(
            "select next, prev
from (
    select id, lag(id) over (order by $order_field) as prev, lead(id) over (order by $order_field) as next
    from $table
    where " . ($parent_id ? 'parent_id = :parent_id' : 'parent_id is null') . ' and folder is not true
) r
where id = :id'
        );

        pdo_bind($r, [
            ':id' => $file_id,
        ]);

        if ($parent_id) {
            pdo_bind($r, [
                ':parent_id' => $parent_id,
            ]);
        }

        $r->execute();

        return $r->fetch(\PDO::FETCH_NUM);
    }

    public function getStorage(?int $storage_id = null, ?string $storage_uid = null, ?int $user_id = null)
    {
        if ($storage_id || $storage_uid) {
            if ($user_id) {
                $r = $this->db->prepare(
                    'select storage.*, p.name as permission_name
from storage, storage_user_permission m
left join storage_permission p on p.id = m.permission_id
where m.storage_id = storage.id and m.user_id = :user_id and ' . ($storage_uid ? 'uid = :storage_id' : 'id = :storage_id')
                );

                $r->execute([
                    ':storage_id' => $storage_uid ?: $storage_id,
                    ':user_id' => $user_id,
                ]);
            } else {
                $r = $this->db->prepare('select * from storage where ' . ($storage_uid ? 'uid = ?' : 'id = ?'));
                $r->execute([$storage_uid ?: $storage_id]);
            }

            $rv = $r->fetch(\PDO::FETCH_ASSOC);

            if ($rv) {
                $r = $this->db->prepare(
                    'select storage_attribute.*, t.name as type_name
from storage_attribute
left join storage_attribute_type t on t.id = storage_attribute.type_id
where storage_id = ?
order by sort, id'
                );
                $r->execute([$rv['id']]);
                $rv['attributes'] = $r->fetchAll(\PDO::FETCH_ASSOC);
            }

            return $rv;
        }
    }

    public function getStoragePermissions(?string $lang = null)
    {
        $r = $this->db->prepare(
            'select storage_permission.*, storage_permission_lang.title
from storage_permission
left join language on (language.name = ? and language.active is true)
left join storage_permission_lang on (storage_permission_lang.storage_permission_id = storage_permission.id and storage_permission_lang.language_id = language.id)
order by storage_permission_lang.title'
        );
        $r->execute([$lang]);
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserPermissions(int $storage_id, ?string $lang = null)
    {
        if ($storage_id) {
            $r = $this->db->prepare(
                'select m.*, m.permission_id as permission, sp.*, spl.title, u.email, u.isactive
from storage_user_permission m
left join public.user u on u.id = m.user_id
left join storage_permission sp on sp.id = m.permission_id
left join language on (language.name = :lang and language.active is true)
left join storage_permission_lang spl on (spl.storage_permission_id = sp.id and spl.language_id = language.id)
where m.storage_id = :storage_id
order by u.email, spl.title'
            );

            $r->execute([
                ':lang' => $lang,
                ':storage_id' => $storage_id,
            ]);

            return $r->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function updateAttributes(int $storage_id, ?array $data)
    {
        if (is_array($data)) {
            foreach ($data as $v) {
                if (!empty($v['del'])) {
                    $r = $this->db->prepare('delete from storage_attribute where id = ? and storage_id = ?');
                    $r->execute([$v['id'], $storage_id]);
                } else {
                    $updated = false;

                    if (!empty($v['id'])) {
                        $r = $this->db->prepare('select id from storage_attribute where id = ? and storage_id = ?');
                        $r->execute([$v['id'], $storage_id]);

                        if ($r->fetchColumn()) {
                            $r = $this->db->prepare(
                                'update storage_attribute
set
    filter = :filter,
    sort = :sort,
    title = :title,
    type_id = :type_id
where id = :id and storage_id = :storage_id'
                            );

                            $r->execute([
                                ':filter' => $v['filter'] ?: null,
                                ':id' => $v['id'],
                                ':sort' => $v['sort'] ?: null,
                                ':storage_id' => $storage_id,
                                ':title' => $v['title'] ?: null,
                                ':type_id' => $v['type'] ?: null,
                            ]);

                            $updated = true;
                        }
                    }

                    if (!$updated) {
                        $r = $this->db->prepare('insert into storage_attribute (filter, sort, storage_id, title, type_id) values (:filter, :sort, :storage_id, :title, :type_id)');
                        $r->execute([
                            ':filter' => $v['filter'] ?: null,
                            ':sort' => $v['sort'] ?: null,
                            ':storage_id' => $storage_id,
                            ':title' => $v['title'] ?: null,
                            ':type_id' => $v['type'] ?: null,
                        ]);
                    }
                }
            }
        }
    }

    public function updateUserPermissions(int $storage_id, ?array $data, ?array $excluded_emails = null)
    {
        if (is_array($data)) {
            $excluded_emails = array_map(function ($v) {
                return htmlentities(strtolower($v));
            }, $excluded_emails ?: []);

            $r = $this->db->prepare(
                'insert into storage_user_permission (storage_id, permission_id, user_id)
(
    select storage.id, storage_permission.id, public.user.id
    from storage, storage_permission, public.user
    where storage.id = :storage_id and storage_permission.id = :permission_id and public.user.email = :email
)
on conflict (storage_id, user_id) do update set permission_id = excluded.permission_id, user_id = excluded.user_id'
            );

            foreach ($data as $v) {
                $v['email'] = htmlentities(strtolower($v['email']));

                if (!in_array($v['email'], $excluded_emails ?: [])) {
                    $r->execute([
                        ':email' => $v['email'],
                        ':permission_id' => $v['permission_id'],
                        ':storage_id' => $storage_id,
                    ]);
                }
            }

            $a = array_merge(array_map(function ($v) {
                return htmlentities(strtolower($v));
            }, array_column($data, 'email')), $excluded_emails ?: []);

            $r = $this->db->prepare(
                'delete from storage_user_permission using public.user where
storage_user_permission.storage_id = ?
and storage_user_permission.user_id = public.user.id' . (count($a) ? ' and public.user.email not in (' . str_repeat('?,', count($a) - 1) . '?)' : '')
            );

            $r->execute(array_merge([$storage_id], $a));
        }
    }

    public function validateFilenameUnique(string $table, string $filename, ?int $parent_id = null, ?array $excluded_ids = null)
    {
        $r = $this->db->prepare(
            "select id
from $table
where name = :name"
            . ($parent_id ? ' and parent_id = :parent_id' : ' and parent_id is null')
            . ($excluded_ids ? ' and id not in (' . array_reduce(
                array_keys($excluded_ids),
                function ($acc, $v) {
                    $acc .= ($acc ? ',' : '') . ':i' . ($v + 1);
                    return $acc;
                },
                ''
            ) . ')' : '')
        );

        pdo_bind($r, [
            ':name' => $filename,
        ]);

        if ($parent_id) {
            pdo_bind($r, [
                ':parent_id' => $parent_id,
            ]);
        }

        if ($excluded_ids) {
            foreach ($excluded_ids as $i => $v) {
                $r->bindValue(':i' . ($i + 1), $v, \PDO::PARAM_INT);
            }
        }

        $r->execute();

        if (!$r->fetchColumn()) {
            return true;
        }
    }
}

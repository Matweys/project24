<?php

declare(strict_types=1);

namespace Main;

class StorageFolderSelectController extends BaseController
{
    public function index($storage_uid = null)
    {
        $current_user = $this->auth->isLogged() ? $this->auth->getCurrentUser() : null;

        if (!$current_user) {
            http_response_code(401);
            exit;
        }

        $Storage = new Storage($this->db);
        $storages = $Storage->getAllowedStorages($current_user['id']);

        $lang = $this->language->getCurrentLanguage($current_user['id']);
        $this->load_translation($lang['name'] ?? null);

        $storage = $Storage->getStorage(null, $storage_uid, $current_user['id']);
        $file_table = $storage ? StorageUtil::getFileTable($this->db, $storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(401);
            echo __('No permission.');
            exit;
        }

        $sql = <<<QUERY
with recursive t as (
  select f.id, f.name, f.parent_id, f.lft, f.rgt, array[f.name] as path, 0 as level
  from {$file_table} f
  where f.folder is true and f.parent_id is null
  union all
  select f.id, f.name, f.parent_id, f.lft, f.rgt, t.path || f.name, level+1
  from {$file_table} f join t on f.parent_id = t.id where f.folder is true
) 
select ( '[' || string_agg( json, '' ) || ']' ) :: json from (
  select
    '{"id":'||to_json(id)||',"label":'||to_json( name ) || 
    case lead( level, 1 ) over( order by path )
      when level then '},' --same lavel, no children, only close
	  when level + 1 then ', "children":[' -- there's children, add item array
      else -- last child in group start to close
	  '}' || --close actual element
	  case
	    when lead( level ) over( order by path ) < level then -- last children in group, close parents, until next level
        repeat( ']}', level - lead( level ) over( order by path ) ) || ',' 
		else repeat( ']}', level ) -- last element in list, close parents all levels
      end
    end as json
  from t
) s1
QUERY;

        $r = $this->db->prepare($sql);
        $r->execute();
        $folder_tree = $r->fetchColumn();

        header('Content-Type: application/json; charset=utf-8');
        echo $folder_tree ?: '[]';
    }
}

<?php

declare(strict_types=1);

namespace Main;

use Main\BaseController;
use Main\Storage;

class MainController extends BaseController
{
    public function index()
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        if ($this->storages) {
            header('Location: ' . $this->config['base_url'] . '/storage/' . $this->storages[0]['uid'] . '/');
            return;
        }

        $lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($lang['name'] ?? null);
        $this->setting->load($lang['name'] ?? null);

        render_template(
            'index',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'lang' => $lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'view' => $this->setting->getByGroups(['view'], $lang['name'] ?? null),
            ],
            __DIR__ . '/templates/'
        );
    }
}

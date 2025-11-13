<?php

// Usage: [['query_path', 'controller_class', 'controller_method', [max_params_count], [route_args]]

return [
    ['storage/delete', \Main\StorageDeleteController::class, 'index', 1],
    ['storage/download', \Main\StorageDownloadController::class, 'index', 2], // хранилище/folder_id
    ['storage/edit', \Main\StorageController::class, 'edit', 1],
    ['storage/edit_folder', \Main\StorageController::class, 'edit_folder', 1],
    ['storage/export', \Main\StorageExportController::class, 'index', 2], // хранилище/folder_id
    ['storage/folder_select', \Main\StorageFolderSelectController::class, 'index', 1],
    ['storage/import_attributes', \Main\StorageImportAttributesController::class, 'index', 2], // хранилище/folder_id
    ['storage/import_attributes_columns', \Main\StorageImportAttributesController::class, 'columns', 2], // хранилище/folder_id
    ['storage/log', \Main\StorageLogController::class, 'index', 1],
    ['storage/move', \Main\StorageMoveController::class, 'index', 1],
    ['storage/new_folder', \Main\StorageController::class, 'new_folder', 2], // хранилище/folder_id
    ['storage/permissions', \Main\StoragePermissionsController::class, 'index', 1],
    ['storage/search', \Main\StorageSearchController::class, 'index', 2], // хранилище/folder_id
    ['storage/settings', \Main\StorageSettingsController::class, 'index', 1],
    ['storage/upload', \Main\StorageController::class, 'upload', 2], // хранилище/folder_id

    ['storage', \Main\StorageController::class, 'index', 2], // хранилище/folder_id

    ['profile/', \Main\ProfileController::class, 'index'],

    ['/', \Main\MainController::class, 'index'],
];

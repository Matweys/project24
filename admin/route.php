<?php
// Usage: [['query_path', 'controller_class', 'controller_method', [max_params_count], [route_args]]

return [
    ['admin/log/', \Admin\LogController::class, 'index'],

    ['admin/storages/action', \Admin\StorageController::class, 'action'],
    ['admin/storages/edit', \Admin\StorageController::class, 'edit'],
    ['admin/storages/new', \Admin\StorageController::class, 'create'],
    ['admin/storages/', \Admin\StorageController::class, 'index'],

    ['admin/users/action', \Admin\UserController::class, 'action'],
    ['admin/users/edit', \Admin\UserController::class, 'edit'],
    ['admin/users/new', \Admin\UserController::class, 'create'],
    ['admin/users/role_lookup', \Admin\UserController::class, 'roleLookup'],
    ['admin/users/', \Admin\UserController::class, 'index'],
];

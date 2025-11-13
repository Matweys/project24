<?php
// Usage: [['query_path', 'controller_class', 'controller_method', [max_params_count], [route_args]]

return [
    ['forgot_password',  \Auth\AuthController::class, 'forgotPassword'],
    ['login', \Auth\AuthController::class, 'login'],
    ['logout', \Auth\AuthController::class, 'logout'],
    ['reset_password/', \Auth\AuthController::class, 'resetPassword', 1],
    ['user', \Auth\AuthController::class, 'activate', 1],
];

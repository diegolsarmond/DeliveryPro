<?php
require_once __DIR__ . '/load_env.php';
return [
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_NAME') ?: 'database',
        'username' => getenv('DB_USER') ?: 'user',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4'
    ],
    'debug' => true,
    'middlewares' => 'TokenAuth'
]; 
<?php
require_once __DIR__ . '/load_env.php';
return [
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'chatbot_mysql',
        'dbname' => getenv('DB_NAME') ?: 'deliverypro',
        'username' => getenv('DB_USER') ?: 'deliverypro',
        'password' => getenv('DB_PASS') ?: 'C@104rm0nd1994',
        'charset' => 'utf8mb4'
    ],
    'debug' => true,
    'middlewares' => 'TokenAuth'
]; 
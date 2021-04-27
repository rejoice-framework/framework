<?php

use function Prinx\Dotenv\env;

return [
    'default' => env('APP_DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('APP_DB_HOST', '127.0.0.1'),
            'port' => env('APP_DB_PORT', '3306'),
            'database' => env('APP_DB_NAME', ''),
            'username' => env('APP_DB_USER', ''),
            'password' => env('APP_DB_PASS', ''),
            'unix_socket' => env('APP_DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('APP_DB_URL'),
            'database' => env('APP_DB_NAME', ''),
            'prefix' => '',
            'foreign_key_constraints' => env('APP_DB_FOREIGN_KEYS', true),
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('APP_DB_URL'),
            'host' => env('APP_DB_HOST', '127.0.0.1'),
            'port' => env('APP_DB_PORT', '5432'),
            'database' => env('APP_DB_NAME', 'forge'),
            'username' => env('APP_DB_USER', 'forge'),
            'password' => env('APP_DB_PASS', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('APP_DB_URL'),
            'host' => env('APP_DB_HOST', 'localhost'),
            'port' => env('APP_DB_PORT', '1433'),
            'database' => env('APP_DB_NAME', 'forge'),
            'username' => env('APP_DB_USER', 'forge'),
            'password' => env('APP_DB_PASS', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ],
];

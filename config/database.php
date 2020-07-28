<?php
use function Prinx\Dotenv\env;

return [

    'default' => [
        'user' => env('APP_DEFAULT_DB_USER', ''),
        'password' => env('APP_DEFAULT_DB_PASS', ''),
        'host' => env('APP_DEFAULT_DB_HOST', ''),
        'port' => env('APP_DEFAULT_DB_PORT', ''),
        'dbname' => env('APP_DEFAULT_DB_NAME', ''),
    ],

];

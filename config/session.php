<?php

use function Prinx\Dotenv\env;

return [

    'driver' => env('SESSION_DRIVER', 'file'), // file|database

    'lifetime' => 60 * 5, // expires after 5h (in min)

    'database' => [
        'user' => env('SESSION_DB_USER', ''),
        'password' => env('SESSION_DB_PASS', ''),
        'host' => env('SESSION_DB_HOST', ''),
        'port' => env('SESSION_DB_PORT', ''),
        'dbname' => env('SESSION_DB_NAME', ''),
    ],

];

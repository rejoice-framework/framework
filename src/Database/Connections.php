<?php

namespace Rejoice\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Rejoice\Foundation\Kernel;

class Connections
{
    public static function load(Kernel $app)
    {
        $capsule = new Capsule;

        $connections = $app->config('database.connections', []);

        if ($defaultConnection = $connections[$app->config('database.default', '')] ?? []) {
            $capsule->addConnection($defaultConnection);
        }

        foreach ($connections as $name => $config) {
            $capsule->addConnection($config, $name);
        }

        $capsule->bootEloquent();
        $capsule->setAsGlobal();
    }
}

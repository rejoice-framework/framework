<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Foundation;

use Prinx\Utils\DB;

/**
 * Connect to and provide the connections to the databases.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Database
{
    protected static $defaultDbParams = [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => '3306',
        'dbname'   => '',
        'user'     => 'root',
        'password' => '',
    ];

    public static function loadSessionDB($params)
    {
        if (!$params) {
            throw new \Exception('Invalid session configuration');
        }

        $params = array_replace(self::$defaultDbParams, $params['database']);

        return DB::load($params);
    }

    public static function loadAppDBs($params)
    {
        $appDBs = [];

        foreach ($params as $key => $param) {
            $param = array_replace(self::$defaultDbParams, $param);
            $appDBs[$key] = DB::load($param);
        }

        return $appDBs;
    }
}

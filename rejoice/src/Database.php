<?php
/**
 * Connect to and provide the connections to the databases
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

namespace Prinx\Rejoice;

use Prinx\Utils\DB;

class Database
{
    protected static $session_db;
    protected static $app_db = [];

    protected static $default_db_params = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => '',
        'user' => 'root',
        'password' => '',
    ];

    protected static $session_config =
    __DIR__ . '/../../../../config/session.php';
    protected static $app_db_config =
    __DIR__ . '/../../../../config/database.php';

    public static function retrieveDbParams($params_file)
    {
        $config = [];

        if ((file_exists($params_file))) {
            $config = require_once $params_file;
        } else {
            throw new \Exception('Database configuration not found. Kindly configure the database settings in the "' . $params_file . '"');
        }

        return $config;
    }

    public static function loadSessionDB()
    {
        $config = require_once self::$session_config;

        if ($config['driver'] === 'database') {
            $params = array_merge(self::$default_db_params, $config['database']);

            self::$session_db = DB::load($params);
            return self::$session_db;
        }

        return null;
    }

    public static function loadAppDBs()
    {
        $params = self::retrieveDbParams(self::$app_db_config);

        foreach ($params as $key => $param) {
            $param = array_merge(self::$default_db_params, $param);
            self::$app_db[$key] = DB::load($param);
        }

        return self::$app_db;
    }
}

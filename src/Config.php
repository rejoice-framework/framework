<?php
namespace Prinx\Rejoice;

class Config
{
    protected $config = [];

    public function __construct()
    {
        $this->setConfig([
            'menus_root_path' => realpath(__DIR__ . '/../../../../app/Menus/'),
            'config_root_path' => realpath(__DIR__ . '/../../../../config/'),
            'storage_root_path' => realpath(__DIR__ . '/../../../../storage/logs/'),
            'logs_root_path' => realpath(__DIR__ . '/../../../../storage/logs/'),
            'sessions_root_path' => realpath(__DIR__ . '/../../../../storage/sessions/'),
            'app_config_path' => realpath(__DIR__ . '/../../../../config/app.php'),
            'database_config_path' => realpath(__DIR__ . '/../../../../config/database.php'),
            'session_config_path' => realpath(__DIR__ . '/../../../../config/session.php'),
            'default_env' => realpath(__DIR__ . '/../../../../.env'),
            'default_namespace' => 'Prinx\Rejoice\\',
        ]);
    }

    public function get($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->config[$name];
        } elseif ($default) {
            return $default;
        } else {
            throw new \Exception('Undefined key ' . $name . ' in the framework configuration');
        }
    }

    public function has($name)
    {
        return isset($this->config[$name]);
    }

    public function set($name, $value)
    {
        $this->config[$name] = $value;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }
}

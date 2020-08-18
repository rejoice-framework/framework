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

/**
 * Common configurations of the framework.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class PathConfig
{
    protected $paths = [];

    public function __construct()
    {
        $projectRootDir = realpath(__DIR__.'/../../../../../');
        $frameworkRootDir = realpath(__DIR__.'/../../');

        $this->setAll([
            // App
            'project_root' => $projectRootDir,

            'default_env_file' => $projectRootDir.'/.env',

            'app_root_dir' => $projectRootDir.'/app/',

            'app_menu_class_dir' => $projectRootDir.'/app/Menus/',

            'app_config_dir' => $projectRootDir.'/config/',

            'app_config_file' => $projectRootDir.'/config/app.php',

            'app_database_config_file' => $projectRootDir.'/config/database.php',

            'app_session_config_file' => $projectRootDir.'/config/session.php',

            'public_root_dir' => $projectRootDir.'/public/',

            'resource_root_dir' => $projectRootDir.'/resources/',

            'menu_resource_dir' => $projectRootDir.'/resources/menus/',

            'storage_root_dir' => $projectRootDir.'/storage/',

            'cache_root_dir' => $projectRootDir.'/storage/cache/',

            'app_default_cache_file' => $projectRootDir.'/storage/cache/rejoice.cache',

            'app_default_log_count_file' => $projectRootDir.'/storage/cache/.log-count.cache',

            'log_root_dir' => $projectRootDir.'/storage/logs/',

            'app_default_log_file' => $projectRootDir.'/storage/logs/rejoice.log',

            'session_root_dir' => $projectRootDir.'/storage/sessions/',

            'test_root_dir' => $projectRootDir.'/tests/',

            'app_command_dir' => $projectRootDir.'/app/Console/Commands/',

            'app_command_file' => $projectRootDir.'/app/Console/commands.php',

            // Framework
            'default_config_dir' => $frameworkRootDir.'/config/',

            'framework_command_dir' => $frameworkRootDir.'/src/Console/Commands/',

            'framework_command_file' => $frameworkRootDir.'/src/Console/commands.php',

            'framework_template_dir' => $frameworkRootDir.'/src/Templates/',
        ]);
    }

    /**
     * Retrieve a path of a file or a directory of the framework.
     *
     * @param  string            $name    The key by which the path is named
     * @param  string            $default A default path if the path is not found
     * @throws \RuntimeException If the path is not found and no default was passed.
     * @return string
     */
    public function get(string $name, string $default = ''): string
    {
        if ($this->has($name)) {
            return $this->paths[$name];
        } elseif (\func_num_args() > 1) {
            return $default;
        }

        throw new \RuntimeException('Key '.$name.' does not exist in the path configuration');
    }

    public function has(string $pathName): bool
    {
        return isset($this->paths[$pathName]);
    }

    public function set($name, $value): PathConfig
    {
        $this->paths[$name] = $value;

        return $this;
    }

    public function setAll($paths): PathConfig
    {
        $this->paths = $paths;

        return $this;
    }
}

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

use function Symfony\Component\String\u as str;
use Prinx\Os;

/**
 * Common configurations of the framework.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Path
{
    /**
     * The project root path.
     *
     * @var string
     */
    protected static $toProject;

    /**
     * The framework root path.
     *
     * @var string
     */
    protected static $toFramework;

    /**
     * The paths.
     *
     * @var array
     */
    protected $paths = [];

    public static function toDefaultPathsConfigFile()
    {
        return static::toDefaultConfigDir('rejoice.php');
    }

    public static function toDefaultConfigDir($append = '')
    {
        return static::toFramework('config'.Os::slash().$append);
    }

    /**
     * Rejoice package path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toFramework($append = '')
    {
        if (!isset(static::$toFramework)) {
            static::$toFramework = str(realpath(__DIR__.'/../../'))->ensureEnd(Os::slash());
        }

        return static::$toFramework.$append;
    }

    /**
     * Project root path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toProject($append = '')
    {
        if (!isset(static::$toProject)) {
            static::$toProject = str(realpath(__DIR__.'/../../../../../'))->ensureEnd(Os::slash());
        }

        return static::$toProject.$append;
    }

    /**
     * 'app' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toApp($append = '')
    {
        return static::toProject('app'.Os::slash().$append);
    }

    /**
     * 'config' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toConfig($append = '')
    {
        return static::toProject('config'.Os::slash().$append);
    }

    /**
     * 'public' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toPublic($append = '')
    {
        return static::toProject('public'.Os::slash().$append);
    }

    /**
     * 'resources' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toResources($append = '')
    {
        return static::toProject('resources'.Os::slash().$append);
    }

    /**
     * 'storage' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toStorage($append = '')
    {
        return static::toProject('storage'.Os::slash().$append);
    }

    /**
     * 'tests' folder path.
     *
     * @param  string   $append
     * @return string
     */
    public static function toTests($append = '')
    {
        return static::toProject('tests'.Os::slash().$append);
    }

    public function __construct(array $customPaths = [])
    {
        $this->populate($customPaths);
    }

    public function populate(array $customPaths = [])
    {
        $customPathsFromConfig = [];
        $rejoiceConfig = static::toConfig('rejoice.php');

        if (file_exists($rejoiceConfig) && $config = include $rejoiceConfig) {
            $customPathsFromConfig = $config['paths'] ?? $customPathsFromConfig;
        }

        $this->set(array_replace($this->defaults(), $customPathsFromConfig, $customPaths));
    }

    public function defaults()
    {
        $defaultPathsConfigFile = static::toDefaultPathsConfigFile();

        $defaults = (require $defaultPathsConfigFile)['paths'];
        $defaults['default_config_dir'] = static::toDefaultConfigDir();

        return $defaults;
    }

    /**
     * Retrieve a path of a file or a directory of the application.
     *
     * @param  string            $name    The key by which the path is named
     * @param  string            $default A default path if the path is not found
     * @throws \RuntimeException If the path is not found and no default was passed.
     * @return string
     */
    public function get(string $name, string $default = '')
    {
        if ($this->has($name)) {
            return $this->paths[$name];
        } elseif (\func_num_args() > 1) {
            return $default;
        }

        throw new \RuntimeException("Key '$name' is not associated to any path.");
    }

    public function has(string $pathName)
    {
        return isset($this->paths[$pathName]);
    }

    public function set($name, $value = null)
    {
        $paths = is_array($name) ? $name : [$name => $value];

        array_map(function ($name, $value) {
            $this->paths[$name] = Os::toPathStyle($value);
        }, array_keys($paths), $paths);
    }

    public function root(string $append = '')
    {
        return static::toProject($append);
    }

    public function projectRootDir(string $append = '')
    {
        return $this->root($append);
    }

    public function vendor(string $append = '')
    {
        return $this->root('vendor/'.$append);
    }

    public function vendorDir(string $append = '')
    {
        return $this->vendor($append);
    }

    public function appCommandsDir(string $append = '')
    {
        return $this->get('app_command_dir').$append;
    }

    public function appCommandListFile()
    {
        return $this->get('app_command_file');
    }

    public function frameworkStubDir(string $append = '')
    {
        return $this->get('framework_stub_dir').$append;
    }

    public function appMenuDir(string $append = '')
    {
        return $this->get('app_menu_class_dir').$append;
    }

    public function baseMenuFile()
    {
        return $this->appMenuDir('Menu.php');
    }

    public function baseMenuFileRelativeToApp()
    {
        return $this->fromAppDir($this->baseMenuFile());
    }

    public function appModelDir($append = '')
    {
        return $this->get('app_model_dir').$append;
    }

    public function fromAppDir($path)
    {
        return substr($path, strlen($this->root()));
    }
}

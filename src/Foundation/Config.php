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

use Prinx\Arr;

/**
 * Application's configurations.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Config
{
    protected $config = [];
    protected $rootDir;
    protected $separator = '.';
    protected $fileSuffix = '.php';

    /**
     * New config.
     *
     * If array provided as configDirs, the config from those paths will be merge
     *
     * @param string|string[] $configDirs The config folder path or an array of paths
     * @param string          $separator
     * @param string          $fileSuffix
     */
    public function __construct($configDirs, string $separator = '.', string $fileSuffix = '.php')
    {
        $this->configDirs = is_array($configDirs) ? $configDirs : [$configDirs];
        $this->fileSuffix = $fileSuffix;
        $this->separator = $separator;
        $this->loadConfig();
    }

    public function loadConfig()
    {
        foreach ($this->configDirs as $path) {
            $directory = new \DirectoryIterator($path);
            $iterator = new \IteratorIterator($directory);
            $files = [];

            foreach ($iterator as $info) {
                $filename = $info->getFileName();

                if ('.' === $filename || '..' === $filename) {
                    continue;
                }

                $name = substr($filename, 0, strlen($filename) - strlen($this->fileSuffix));

                $files[$name] = require $info->getPathname();
            }

            $this->config = array_replace_recursive($this->config, $files);
        }
    }

    /**
     * Get a configuration variable from the config.
     *
     * @param string $key
     * @param mixed  $default The default to return if the configuration is not found
     * @param bool   $silent  If true, will shutdown the exception throwing if configuration variable not found and no default was passed.
     *
     * @throws \RuntimeException
     *
     * @return Config|mixed
     */
    public function get($key = null, $default = null, $silent = false)
    {
        $argCount = \func_num_args();

        if (0 === $argCount) {
            return $this->config;
        }

        $value = Arr::multiKeyGet($key, $this->config);

        if (null === $value) {
            $defaultWasPassed = $argCount >= 2;

            if ($defaultWasPassed || (!$defaultWasPassed && $silent)) {
                return $default;
            }

            throw new \RuntimeException("Index $key not found in the config.");
        }

        return $value;
    }

    public function has(string $key)
    {
        return Arr::multiKeyGet($key, $this->config) !== null;
    }

    public function set(string $key, $value)
    {
        $this->config = Arr::multiKeySet($key, $value, $this->config);

        return $this;
    }

    public function setAll($config)
    {
        $this->config = $config;
    }
}

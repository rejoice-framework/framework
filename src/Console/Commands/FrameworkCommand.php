<?php

namespace Rejoice\Console\Commands;

use Prinx\Config;
use Prinx\Os;
use Rejoice\Foundation\PathConfig;

class FrameworkCommand extends SmileCommand
{
    /**
     * @var \Rejoice\Foundation\PathConfig
     */
    protected $paths;

    /**
     * @var \Prinx\Config
     */
    protected $config;

    public function config($key = null, $default = null, $silent = false)
    {
        if (!isset($this->config)) {
            $this->config = new Config($this->path('app_config_dir'));
        }

        if (!isset($key)) {
            return $this->config;
        } else {
            return $this->config->get(...(func_get_args()));
        }
    }

    public function projectRootDir()
    {
        if (!isset($this->projectRootDir)) {
            $this->projectRootDir = Os::toPathStyle(
                $this->path('project_root')
            );
        }

        return $this->projectRootDir;
    }

    public function appCommandsDir()
    {
        if (!isset($this->appCommandsDir)) {
            $this->appCommandsDir = Os::toPathStyle(
                $this->path('app_command_dir')
            );
        }

        return $this->appCommandsDir;
    }

    public function appCommandsRepo()
    {
        if (!isset($this->appCommandsRepo)) {
            $this->appCommandsRepo = Os::toPathStyle(
                $this->path('app_command_file')
            );
        }

        return $this->appCommandsRepo;
    }

    public function frameworkTemplateDir()
    {
        if (!isset($this->frameworkTemplateDir)) {
            $this->frameworkTemplateDir = Os::toPathStyle(
                $this->path('framework_template_dir')
            );
        }

        return $this->frameworkTemplateDir;
    }

    public function baseMenuFolder()
    {
        if (!isset($this->baseMenuFolder)) {
            $this->baseMenuFolder = Os::toPathStyle(
                $this->path('app_menu_class_dir')
            );
        }

        return $this->baseMenuFolder;
    }

    public function baseMenuPath()
    {
        return $this->baseMenuFolder().Os::slash().'Menu.php';
    }

    public function baseMenuPathRelativeToApp()
    {
        return $this->pathFromApp($this->baseMenuPath());
    }

    public function pathFromApp($path)
    {
        // +1 to remove the slash before the app folder name too
        return substr($path, strlen($this->projectRootDir()) + 1);
    }

    public function path($name)
    {
        if (!$this->paths) {
            $this->paths = new PathConfig();
        }

        return $this->paths->get($name);
    }
}

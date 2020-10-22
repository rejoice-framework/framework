<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Console;

use Rejoice\Console\Commands\FrameworkCommand;
use Rejoice\Foundation\App as Rejoice;
use Rejoice\Foundation\Path;
use Symfony\Component\Console\Application as Console;

/**
 * Create an instance instance of the application.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Application
{
    protected static $rejoice;

    public function run()
    {
        $this->startRejoice();

        $app = new Console('Rejoice Console', 'v1.0.0');

        $this->loadCommandsInto($app);

        $app->run();
    }

    public function loadCommandsInto($app)
    {
        $commands = $this->retrieveCommands();

        foreach ($commands as $command) {
            $newCommand = new $command;

            if ($newCommand instanceof FrameworkCommand) {
                $newCommand->setRejoice($this->getRejoice());
            }

            $app->add($newCommand);
        }
    }

    /**
     * Get the instance of the app for the console session.
     *
     * @return \Rejoice\Foundation\Kernel
     */
    public function getRejoice()
    {
        return static::$rejoice;
    }

    public function startRejoice()
    {
        static::$rejoice = Rejoice::mock();

        return $this;
    }

    public function retrieveCommands()
    {
        $paths = new Path;

        $commands = require $paths->get('app_command_file');
        $frameworkCommands = require $paths->get('framework_command_file');

        return array_merge($commands, $frameworkCommands);
    }
}

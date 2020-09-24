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

use Rejoice\Foundation\PathConfig;
use Symfony\Component\Console\Application as SymfonyConsoleApp;

/**
 * Create an instance instance of the application.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class Application
{
    public function run()
    {
        $app = new SymfonyConsoleApp('Rejoice Console', 'v1.0.0');

        $this->loadCommandsInto($app);

        $app->run();
    }

    public function loadCommandsInto($app)
    {
        $commands = $this->retrieveCommands();

        foreach ($commands as $command) {
            $app->add(new $command());
        }
    }

    public function retrieveCommands()
    {
        $paths = new PathConfig();

        $commands = require $paths->get('app_command_file');
        $frameworkCommands = require $paths->get('framework_command_file');

        return $commands = array_replace($commands, $frameworkCommands);
    }
}

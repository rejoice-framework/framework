<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prinx\Rejoice;

use Symfony\Component\Console\Application;

/**
 * Create an instance instance of the application
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class ConsoleApp
{
    public function run()
    {
        $app = new Application('Rejoice Console', 'v1.0.0');
        $commands = require_once realpath(__DIR__ . '/../../../../config/') . '/commands.php';

        foreach ($commands as $command) {
            $app->add(new $command);
        }

        $app->run();
    }
}

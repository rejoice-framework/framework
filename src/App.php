<?php
namespace Prinx\Rejoice;

/**
 * Bootstraps the application
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */

class App
{
    public static function run($name = 'default')
    {
        $application = new Kernel();
        return $application->run($name);
    }
}

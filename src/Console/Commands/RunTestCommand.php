<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Commands\FrameworkCommand as Smile;
use Rejoice\Foundation\Path;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/**
 * To look at.
 */
class RunTestCommand extends Smile
{
    public function configure()
    {
        $this->setName('test')
            ->setDescription('Run all the tests for the application.');
    }

    public function fire()
    {
        passthru(PHP_BINARY.' '.Path::toProject('vendor/bin/phpunit'));

        // $process = (new Process([
        //     PHP_BINARY,
        //     Path::toProject('vendor/phpunit/phpunit/phpunit'),
        //     '--printer',
        //     'NunoMaduro\Collision\Adapters\Phpunit\Printer',
        //     '-c',
        //     Path::toProject('phpunit.xml')
        // ]))->setTimeout(null);

        // try {
        //     return $process->run(function ($type, $line) {
        //         $this->write($line);
        //     });
        // } catch (ProcessSignaledException $e) {
        //     if (extension_loaded('pcntl') && $e->getSignal() !== SIGINT) {
        //         throw $e;
        //     }
        // }

        return Smile::SUCCESS;
    }
}

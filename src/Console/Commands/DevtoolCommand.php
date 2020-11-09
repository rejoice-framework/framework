<?php

namespace Rejoice\Console\Commands;

use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;
use Rejoice\Console\DevTool\ClassAliasAutoloader;

/**
 * Rejoice Devtool Command.
 * Inspired by Laravel Tinker Command.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class DevtoolCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('devtool')
            ->setDescription('Interactive console')
            ->setHelp('Quickly run PHP script');
    }

    public function fire()
    {
        if (!class_exists('Psy\Shell')) {
            $this->writeln("Devtool not installed. Run `{$this->colorize('composer require psy/psysh', 'yellow')}` to install it.");

            return SmileCommand::FAILURE;
        }

        $this->getApplication()->setCatchExceptions(false);

        $config = Configuration::fromInput($this->getInput());

        $config->setUpdateCheck(Checker::NEVER);

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        $shell = new Shell($config);
        $shell->addCommands($this->getCommands());

        $vendorPath = $this->path()->vendor();
        $classMapPath = $this->path()->vendor('composer/autoload_classmap.php');

        $loader = new ClassAliasAutoloader(
            $shell,
            $classMapPath,
            $vendorPath,
            $this->config('devtool.alias', []),
            $this->config('devtool.dont_alias', [])
        );

        $loader->register();

        try {
            return $shell->run();
        } finally {
            $loader->unregister();
        }
    }

    public function getCasters()
    {
        return [
            'Illuminate\Support\Collection'      => 'Rejoice\Console\Devtool\Caster::castCollection',
            'Illuminate\Database\Eloquent\Model' => 'Rejoice\Console\Devtool\Caster::castModel',
        ];
    }

    public function getCommands()
    {
        return array_values($this->getApplication()->all());
    }
}

<?php

namespace Rejoice\Console\Commands;

use function Symfony\Component\String\u as str;
use Prinx\Os;
use Rejoice\Console\Argument;
use Rejoice\Console\Option;

/**
 * Command to create a new command.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class NewCommandCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('make:command')
            ->setDescription('Create a new console command')
            ->addArgument('name', Argument::REQUIRED, 'The name of the command')
            ->addOption('example', 'e', Option::NONE, 'Create a full example command');
    }

    public function fire()
    {
        $commandClass = str($this->getArgument('name'))
            ->ensureEnd('Command')
            ->camel()
            ->title();

        return $this->createCommandFile($commandClass);
    }

    public function createCommandFile($commandClass)
    {
        $path = $this->path()->appCommandsDir();
        $file = Os::toPathStyle($path.$commandClass.'.php');

        if (file_exists($file) && !$this->confirm([
            "Command file $file already exists.",
            '<fg=yellow>Do you want to overwrite this file?</>',
        ])) {
            return SmileCommand::FAILURE;
        }

        if ($this->writeClassInFile($file, $commandClass)) {
            $commandRepo = $this->path()->fromAppDir($this->path()->appCommandListFile());

            $this->writeln([
                '',
                $this->colorize('Command successfully created in '.$this->path()->fromAppDir($file), 'green'),
                '',
                "Register the command in $commandRepo to make it available to the application.",
            ]);

            return SmileCommand::SUCCESS;
        }

        return SmileCommand::FAILURE;
    }

    public function writeClassInFile($file, $commandClass)
    {
        $created = file_put_contents($file, $this->template($commandClass));

        return false !== $created;
    }

    public function template($commandClass)
    {
        $commandName = str($commandClass)
            ->trim('Command')
            ->snake()
            ->replace('_', ':');

        $parameters = [
            'commandName'  => $commandName,
            'commandClass' => $commandClass,
        ];

        $isExampleCommand = $this->getOption('example');
        $stub = $isExampleCommand ? 'NewCommandExample' : 'NewCommandReal';
        $stubPath = $this->path()->frameworkStubDir("Commands/{$stub}.stub");

        return $this->generateTemplateFromStub($stubPath, $parameters);
    }
}

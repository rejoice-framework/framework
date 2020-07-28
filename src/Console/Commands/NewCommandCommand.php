<?php
namespace Prinx\Rejoice\Console\Commands;

use Prinx\Os;
use Prinx\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NewCommandCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('app:new-command')
            ->setDescription('Create a new console command class')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the command'
            )
            ->addOption(
                'example',
                'e',
                InputOption::VALUE_NONE,
                'Create a full example command'
            );
    }

    public function fire()
    {
        $path = $this->appCommandsDir();
        $commandName = $this->createCommandName($this->getArgument('name'));

        return $this->createCommandFile($path, $commandName);
    }

    public function createCommandName($name)
    {
        $exploded = explode(':', $name);
        $name = Str::pascalCase($exploded[0]) . Str::pascalCase(($exploded[1] ?? ''));

        return $name;
    }

    public function createCommandFile($path, $commandName)
    {
        $file = Os::toPathStyle($path . $commandName . '.php');

        if (
            file_exists($file) &&
            !$this->confirm([
                "Command file $file already exists.",
                "<fg=yellow>Do you want to overwrite this file?</>",
            ])
        ) {
            return SmileCommand::FAILURE;
        }

        if ($this->writeClassInFile($file, $commandName)) {
            $commandRepo = $this->appCommandsRepo();

            $this->writeln([
                '',
                $this->colorize("Command successfully created at $file", 'green'),
                "Register the command in $commandRepo to be able to use it",
            ]);

            return SmileCommand::SUCCESS;
        }

        return SmileCommand::FAILURE;
    }

    public function writeClassInFile($file, $commandName)
    {
        $exampleCommand = $this->getOption('example');
        $created = file_put_contents($file, $this->template($commandName, $exampleCommand));

        return false !== $created;
    }

    public function template($commandName, $example = true)
    {
        if ($example) {
            return $this->exampleTemplate($commandName);
        } else {
            return $this->normalTemplate($commandName);
        }
    }

    public function exampleTemplate($commandName)
    {
        return require $this->frameworkTemplateDir() . 'Commands/NewCommandExample.php';
    }

    public function normalTemplate($commandName)
    {
        return require $this->frameworkTemplateDir() . 'Commands/NewCommandSimple.php';
    }
}

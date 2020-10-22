<?php
namespace Rejoice\Console\Commands;

use function Symfony\Component\String\u as str;
use Prinx\Os;
use Rejoice\Console\Argument;

class NewModelCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('make:model')
            ->setDescription('Create a new model class')
            ->addArgument('name', Argument::REQUIRED, 'The name of the model.');
    }

    public function fire()
    {
        $class = str($this->getArgument('name'))->camel()->title();
        $path = $this->path()->appModelDir();
        $file = Os::toPathStyle($path.$class.'.php');

        if (file_exists($file) && !$this->confirm([
            "Model file $file already exists.",
            '<fg=yellow>Do you want to overwrite this file?</>',
        ])) {
            return SmileCommand::FAILURE;
        }

        if ($this->writeClassInFile($file, $class)) {
            $this->writeln([
                $this->colorize('Model successfully created in '.$this->path()->fromAppDir($file), 'green'),
            ]);

            return SmileCommand::SUCCESS;
        }

        return SmileCommand::FAILURE;
    }

    public function writeClassInFile($file, $className)
    {
        $created = file_put_contents($file, $this->template($className));

        return $created !== false;
    }

    public function template($className)
    {
        $parameters = [
            'className' => $className,
        ];

        $stubPath = $this->path()->frameworkStubDir('Models/Model.stub');

        return $this->generateTemplateFromStub($stubPath, $parameters);
    }
}

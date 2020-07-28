<?php
$commandName = $commandName ?? 'ExampleCommand';

$template = "<?php
namespace Prinx\Rejoice\Console;

use Prinx\Rejoice\Console\Commands\SmileCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class {$commandName}Command extends SmileCommand
{
    public function configure()
    {
        \$this->setName('namespace:command')
            ->setDescription(\"Get someone's name\")
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name of the person'
            )
            ->addOption(
                'greet',
                'g',
                InputOption::VALUE_NONE,
                'If passed, we will greet the person after receiving the name',
                null
            );
    }

    public function fire()
    {
        \$name = \$this->getArgument('name');

        \$this->writeln('Cool, your name is ' . \$name);

        if (\$this->getOption('greet')) {
            \$this->writeln('Hello ' . \$name);
        }

        return SmileCommand::SUCCESS;
    }
}
";

return $template;

<?php

$commandName = $commandName ?? 'SimpleCommand';

$template = "<?php
namespace Rejoice\Console;

use Rejoice\Console\Commands\SmileCommand;

class {$commandName}Command extends SmileCommand
{
    public function configure()
    {
        \$this->setName('namespace:command')
            ->setDescription('Describe me');
    }

    public function fire()
    {
        //

        return SmileCommand::SUCCESS;
    }
}
";

return $template;

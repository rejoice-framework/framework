<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Option;

class ServeCommand extends FrameworkCommand
{
    protected $allowedChannels = ['web', 'console'];

    public function configure()
    {
        $this->setName('serve')
            ->setDescription('Alias for simulator commands')
            ->setHelp('By default, this command will run the web simulator. With the console option passed, it will run the console simulator.')
            ->addOption('console', 'c', Option::OPTIONAL, 'Run the simulator in console', false);
    }

    public function fire()
    {
        $channel = $this->getOption('console') ? 'console' : 'web';

        $command = $this->getApplication()->find("simulator:{$channel}");

        return $command->run($this->getInput(), $this->getOutput());
    }
}

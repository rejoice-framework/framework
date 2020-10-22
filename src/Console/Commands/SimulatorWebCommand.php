<?php

namespace Rejoice\Console\Commands;

use Prinx\Os;
use Rejoice\Console\Option;
use Rejoice\Simulator\Libs\Simulator;

class SimulatorWebCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('simulator:web')
            ->setDescription('Run the USSD simulator web interface')
            ->setHelp('This command allow you to test your USSD application')
            ->addOption(
                'host',
                null,
                Option::OPTIONAL,
                'The ip address on which to run the simulator',
                '127.0.0.1'
            )
            ->addOption(
                'port',
                null,
                Option::OPTIONAL,
                'The port on which to run the simulator',
                '8000'
            );
    }

    public function fire()
    {
        if (!class_exists('Rejoice\Simulator\Libs\Simulator')) {
            $this->writeln([
                $this->colorize('Simulator not installed.', 'red'),
                'Run `composer require rejoice/simulator` to install it.',
            ]);

            return SmileCommand::FAILURE;
        }

        $ip = $this->getOption('host');
        $port = $this->getOption('port');
        $keyCombination = Os::getCtrlKey().'+c';

        $this->writeln([
            $this->colorize('Server started at http://'.$ip.':'.$port, 'green'),
            "Press {$keyCombination} to stop the server.",
        ]);

        Simulator::serve($ip, $port);

        return SmileCommand::SUCCESS;
    }
}

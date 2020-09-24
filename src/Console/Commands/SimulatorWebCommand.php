<?php

namespace Rejoice\Console\Commands;

use Prinx\Os;
use Symfony\Component\Console\Input\InputOption;

class SimulatorWebCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('simulator:web')
            ->setDescription('Run the USSD simulator web interface')
            ->setHelp('This command allow you to test your USSD application')
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specify the port on which to run the simulator',
                '8001'
            );
    }

    public function fire()
    {
        $simulatorPath = realpath(__DIR__.'/../../../../simulator/src/');
        if (!is_dir($simulatorPath)) {
            $this->writeln([
                $this->colorize('Simulator not found.', 'red'),
                'Use `composer require rejoice/simulator` to install it.',
            ]);

            return SmileCommand::FAILURE;
        }

        $ip = '127.0.0.1';
        $port = $this->getOption('port');

        $keyCombination = Os::getCtrlKey().'+c';

        $this->writeln([
            $this->colorize('Server started at http://'.$ip.':'.$port, 'green'),
            "Press {$keyCombination} to stop the server.",
        ]);

        passthru('php -S '.$ip.':'.$port.' -t "'.$simulatorPath.'"', $return);

        return SmileCommand::SUCCESS;
    }
}

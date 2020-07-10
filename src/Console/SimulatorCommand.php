<?php
namespace Prinx\Rejoice\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SimulatorCommand extends SmileCommand
{
    public function configure()
    {
        $this->setName('simulator:run')
            ->setDescription('Run the USSD simulator')
            ->setHelp('This command allow you to test your USSD application')
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specify the port on which to run the simulator',
                '8001'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $simulatorPath = realpath(__DIR__ . '/../../../ussd-simulator/src/');
        if (!is_dir($simulatorPath)) {
            $output->writeln([
                '<fg=red>Simulator not found.</>',
                'Use `composer require prinx/ussd-simulator` to install it.',
            ]);
            return SmileCommand::FAILURE;
        }

        $ip = '127.0.0.1';
        $port = $input->getOption('port');
        // if ($this->portAvailabe($port, $ip)) {
        passthru('php -S ' . $ip . ':' . $port . ' -t "' . $simulatorPath . '"', $return);

        // $output->writeln([
        //     '<info>Server started at http://' . $ip . ':' . $port . '</info>',
        //     'Press Ctrl+C to stop the server.',
        // ]);
        // } else {
        //     $output->writeln([
        //         '<fg=red>The port ' . $port . ' on http://' . $ip . ' is not available.</>',
        //         'Kindly check if it is open and not used by another application.',
        //     ]);

        //     return SmileCommand::FAILURE;
        // }

        return SmileCommand::SUCCESS;
    }

    public function portAvailabe($port, $ip = '127.0.0.1')
    {
        $connection = fsockopen($ip, $port);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }

        return false;
    }
}

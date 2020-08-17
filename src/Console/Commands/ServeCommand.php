<?php
namespace Rejoice\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is not used
 * To be reviewed
 */
class ServeCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('serve')
            ->setDescription('Run the USSD application')
            ->setHelp('This command allow you to run your USSD application on a PHP development server')
            ->addOption(
                'port',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specify the port on which to run the application',
                '8000'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $simulatorPath = realpath(__DIR__.'/../../../../../');
        if (!is_dir($simulatorPath)) {
            $output->writeln([
                '<fg=red>index.php not found.</>',
            ]);

            return SmileCommand::FAILURE;
        }

        $ip = '127.0.0.1';
        $port = $input->getOption('port');
        // if ($this->portAvailabe($port, $ip)) {
        passthru('php -S '.$ip.':'.$port.' -t "'.$simulatorPath.'"', $return);

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

    // To be reviewed
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

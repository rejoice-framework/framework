<?php
namespace Prinx\Rejoice\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class GenerateMenuCommand extends SmileCommand
{
    public function configure()
    {
        $this->setName('menus:json')
            ->setDescription('Generate the menu flow as JSON')
            ->setHelp('This command allow you to generate the USSD menus as JSON. It does not support the menu generate in the menu entities. Only the menus in the menus.php file will be translate to JSON')
            ->addOption(
                'app',
                null,
                InputOption::VALUE_OPTIONAL,
                'If using under an application namespace, specify the app name.',
                ''
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'If the menus.php is not at the root of the menus folder, of the app, specify the proper path starting from the USSD app folder (not the framework app folder).',
                ''
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = __DIR__ . '/../../../../../app/Menus/';
        $appName = $input->getOption('app');
        $path .= $appName ? $appName . '/' : '';
        $addPath = $input->getOption('path');
        $path .= $addPath ? $addPath . '/' : '';

        $menusDir = realpath($path);
        $menusPath = $menusDir . '/menus.php';
        $jsonPath = $menusDir . '/menus.json';

        if (!file_exists($menusPath)) {
            $output->writeln('Menus to generate not found at ' . $menusPath);
            return SmileCommand::FAILURE;
        }

        $overwrite = true;

        if (file_exists($jsonPath)) {
            $helper = $this->getHelper('question');
            $question = new Question('Are you sure to override existing menus.json? ', 'no');
            $overwriteResponse = $helper->ask($input, $output, $question);
            $overwrite = in_array(strtolower($overwriteResponse), ['y', 'yes']);
        }

        if ($overwrite) {
            $created = file_put_contents(
                $jsonPath,
                json_encode($menusPath, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            if ($created !== false) {
                $output->writeln('<info>JSON generated successfully in ' . $jsonPath . '</info>');
            } else {
                $output->writeln('<fg=red>Error when generating the json file at ' . $jsonPath . '</>');
                return SmileCommand::FAILURE;
            }
        } else {
            $output->writeln('<info>menus.json generation discarded.</info>');
        }

        return SmileCommand::SUCCESS;
    }

    public function portAvailabe($port, $ip = '127.0.0.1')
    {
        $connection = @fsockopen($ip, $port);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }

        return false;
    }
}

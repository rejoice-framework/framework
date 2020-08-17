<?php
namespace Rejoice\Console\Commands;

use Prinx\Os;
use Symfony\Component\Console\Input\InputOption;

class GenerateMenuJsonCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('menu:json')
            ->setDescription('Generate the menu flow as JSON')
            ->setHelp("This command allow you to generate the USSD menus as JSON. It does not generate the menu created in the menu entities. Only the menus in the resources/menus/menus.php file will be translate to JSON.\nThis is useful if all your flows are in the menus.php and you want to export it as JSON.")
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'If the menus to generate from reside in a particular namespace (folder) in the resources/menus/, specify the path, taking as root folder the resources/menus/ folder.',
                ''
            );
    }

    public function fire()
    {
        $path = $this->path('menu_resource_dir');
        $addPath = $this->getOption('path');
        $path .= $addPath ? $addPath.'/' : '';

        $menusDir = realpath($path);
        $menusPath = Os::toPathStyle($menusDir.'/menus.php');
        $jsonPath = Os::toPathStyle($menusDir.'/menus.json');

        if (!file_exists($menusPath)) {
            $this->writeln('Menus to generate not found at '.$menusPath);

            return SmileCommand::FAILURE;
        }

        $overwrite = true;

        if (file_exists($jsonPath)) {
            $overwrite = $this->confirm('<fg=yellow>A menus.json already exists. Are you sure to override it?</>', 'no');
        }

        if ($overwrite) {
            $menus = require $menusPath;

            $created = file_put_contents($jsonPath, json_encode(
                $menus,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            ));

            if (false !== $created) {
                $this->writeln('<info>JSON generated successfully in '.$jsonPath.'</info>');
            } else {
                $this->writeln('<fg=red>Error when generating the json file at '.$jsonPath.'</>');

                return SmileCommand::FAILURE;
            }
        } else {
            $this->writeln('menus.json generation discarded.');
        }

        return SmileCommand::SUCCESS;
    }
}

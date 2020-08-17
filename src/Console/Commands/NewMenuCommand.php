<?php
namespace Rejoice\Console\Commands;

use Prinx\Os;
use Prinx\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NewMenuCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('menu:new')
            ->setDescription('Create a new menu class')
            ->setHelp('Create a new menu')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The name or the namespace of the new menu.'
            )
            ->addOption(
                'paginable',
                'p',
                InputOption::VALUE_NONE,
                'Create a paginable menu.',
                null
            )
            ->addOption(
                'no-comment',
                'x',
                InputOption::VALUE_NONE,
                'Create a paginable menu.',
                null
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Create menu with all the menu entity methods',
                null
            )
            ->addOption(
                'basic',
                'b',
                InputOption::VALUE_NONE,
                'Create menu with the basic menu entity methods (message, actions, validate and saveAs)',
                null
            )
            ->addOption(
                'validate',
                '',
                InputOption::VALUE_NONE,
                'Create menu with the validate menu entity method',
                null
            )
            ->addOption(
                'save-as',
                '',
                InputOption::VALUE_NONE,
                'Create a paginable menu.',
                null
            )
            ->addOption(
                'after',
                '',
                InputOption::VALUE_NONE,
                'Create a paginable menu with the after method',
                null
            )
            ->addOption(
                'on-next',
                '',
                InputOption::VALUE_NONE,
                'Create a paginable menu with the onMoveToNextMenu method',
                null
            )
            ->addOption(
                'on-back',
                '',
                InputOption::VALUE_NONE,
                'Create a paginable menu with the onMoveToNextMenu method',
                null
            )
            ->setHelp("Eg: php smile menu:new EnterName\n  Will create the menu class 'EnterName' in app/Menus/EnterName.php file.\n\nphp smile menu:new UserPurchaseFlow/GetName\n  Will create the class GetName in app/Menus/UserPurchaseFlow/GetName.php\n\nNote: Using a slash (/) or a backslash (\) will produce the same result.\nA menu name like 'enter-name' or 'enter_name' or 'Enter_name', etc. will be converted to 'EnterName' (any hyphen or underscore is removed and the name converted to PascalCase).\n\nUse the -x or --no-comment to generate the menu without the generated comment.\nThe generated comment are meant to give a quick explanation about what you need to do. As you are becoming used to the framework, you will not really need them.\nSome few comments, that we consider very important, will still remain.");
    }

    public function fire()
    {
        $fileRelativePath = $this->getArgument('name');

        $newFileData = $this->newFileFromArgument($fileRelativePath);

        if ($newFileData) {
            $path = $newFileData['path'];
            $filename = $newFileData['name'];

            if ($this->writeClassInMenuFile($path, $filename)) {
                $this->info('Menu created at '.$this->pathFromApp($path));
            } else {
                $this->error('An error happened.');
            }
        } else {
            $this->writeln('Menu creation discarded');
        }

        return SmileCommand::SUCCESS;
    }

    public function writeClassInMenuFile($path, $newFileName)
    {
        $paginable = $this->getOption('paginable');
        $template = $this->template($newFileName, $path, $paginable);

        return file_put_contents($path, $template) !== false;
    }

    public function getFileNamespaceFrom($path)
    {
        $namespace = substr($path, strpos($path, Os::toPathStyle('app/Menus/')));
        $namespace = substr($namespace, 0, strrpos($namespace, Os::slash()));
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = ucfirst($namespace);

        return $namespace;
    }

    public function newFileFromArgument($fileRelativePath)
    {
        $name = Os::toPathStyle($fileRelativePath);

        $slash = Os::slash();
        $name = Str::startsWith(".$slash", $name) ? ltrim($name, ".$slash") : $name;
        $name = Str::startsWith($slash, $name) ? ltrim($name, $slash) : $name;

        $relativePathChunks = explode($slash, $name);
        $relativePathChunks = array_map(function ($element) {
            return Str::pascalCase($element);
        }, $relativePathChunks);

        $filename = array_pop($relativePathChunks);

        if (!$filename) {
            $this->writeln('No menu name.');

            return false;
        }

        $relativeDir = implode($slash, $relativePathChunks);
        // $dir = $this->baseMenuFolder() . $slash . $relativeDir;
        $dir = $this->baseMenuFolder().$relativeDir;

        $fullPath = $dir.$slash.$filename.'.php';

        if (!$this->overrideMenuFileIfExists($fullPath)) {
            return false;
        }

        if (!$this->createBaseMenuFileIfNotExists()) {
            return false;
        }

        if (!$this->createRequestedDirIfNotExists($dir)) {
            return false;
        }

        return [
            'path' => $fullPath,
            'name' => $filename,
        ];
    }

    public function createRequestedDirIfNotExists($dir)
    {
        if (is_dir($dir)) {
            return true;
        }

        if (mkdir($dir, 0777, true)) {
            $this->writeln('Directory created.');

            return true;
        }

        return false;
    }

    public function createBaseMenuFileIfNotExists()
    {
        if (!file_exists($this->baseMenuPath())) {
            if (!$this->confirm([
                '',
                "The base Menu {$this->baseMenuPathRelativeToApp()} does not exist.",
                $this->colorize('Will you like to generate it? [Y,n] ', 'yellow'),
            ])) {
                return false;
            }

            if ($this->generateBaseMenu()) {
                $this->info("Base menu created at {$this->baseMenuPathRelativeToApp()}");

                return true;
            } else {
                $this->error('Error when generating the base menu file.');

                return false;
            }
        }

        return true;
    }

    public function overrideMenuFileIfExists($file)
    {
        if (file_exists($file)) {
            return (
                $this->confirm([
                    "Menu {$this->pathFromApp($file)} already exists.",
                    $this->colorize('Do you want to overwrite it? [Y,n] ', 'red'),
                ]) &&
                rename($file, $file) // We force the file to take the new name.Because of Windows that does not consider capitalisation the wanted name could differ from the name that the file had.
            );
        }

        return true;
    }

    public function template($name, $path, $paginable = false)
    {
        $namespace = $this->getFileNamespaceFrom($path);

        if ($paginable) {
            return $this->paginableMenuTemplate($name, $namespace);
        } else {
            return $this->standardMenuTemplate($name, $namespace);
        }
    }

    // The variables here seems not to be used but will actually have effect
    // when the page will be required (included)
    public function paginableMenuTemplate($name, $namespace)
    {
        $noComment = $this->getOption('no-comment');

        if ($noComment) {
            $template = require $this->frameworkTemplateDir().'Menus/PaginableMenuNoComment.php';
        } else {
            $template = require $this->frameworkTemplateDir().'Menus/PaginableMenu.php';
        }

        return $template;
    }

    // The variables here seems not to be used but will actually have effect
    // when the page will be required (included)
    public function standardMenuTemplate($name, $namespace)
    {
        $generateAll = $this->getOption('all');
        $generateBasic = $this->getOption('basic');

        $validate = $generateAll || $generateBasic || $this->getOption('validate');
        $saveAs = $generateAll || $generateBasic || $this->getOption('save-as');
        $after = $generateAll || $this->getOption('after');
        $onNext = $generateAll || $this->getOption('on-next');
        $onBack = $generateAll || $this->getOption('on-back');
        $noComment = $this->getOption('no-comment');

        if ($noComment) {
            $template = require $this->frameworkTemplateDir().'Menus/MenuNoComment.php';
        } else {
            $template = require $this->frameworkTemplateDir().'Menus/Menu.php';
        }

        return $template;
    }

    public function generateBaseMenu()
    {
        $template = file_get_contents($this->frameworkTemplateDir().'Menus/BaseMenu.php');

        return file_put_contents($this->baseMenuPath(), $template) !== false;
    }
}

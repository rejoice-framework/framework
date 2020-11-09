<?php

namespace Rejoice\Console\Commands;

use Prinx\Os;
use Prinx\Str;
use Rejoice\Console\Argument;
use Rejoice\Console\Option;
use function Symfony\Component\String\u as str;

class NewMenuCommand extends FrameworkCommand
{
    public function configure()
    {
        $this->setName('make:menu')
            ->setDescription('Create a new menu.')
            ->addArgument(
                'name',
                Argument::REQUIRED,
                'The name of the new menu.'
            )
            ->addOption(
                'paginable',
                'p',
                Option::NONE,
                'Create a paginable menu.',
                null
            )
            ->addOption(
                'no-comment',
                'x',
                Option::NONE,
                'Create menu without docBlock.',
                null
            )
            ->addOption(
                'all',
                'a',
                Option::NONE,
                'Create menu with all the menu methods',
                null
            )
            ->addOption(
                'basic',
                'b',
                Option::NONE,
                'Create menu with the basic menu methods (message, actions, validate and saveAs)',
                null
            )
            ->addOption(
                'validate',
                '',
                Option::NONE,
                "Create menu with the 'validate' method",
                null
            )
            ->addOption(
                'save-as',
                '',
                Option::NONE,
                "Create menu with the 'saveAs' method",
                null
            )
            ->addOption(
                'after',
                '',
                Option::NONE,
                "Create a menu with the 'after' method",
                null
            )
            ->addOption(
                'on-next',
                '',
                Option::NONE,
                "Create a menu with the 'onMoveToNextMenu' method",
                null
            )
            ->addOption(
                'on-back',
                '',
                Option::NONE,
                "Create a paginable menu with the 'onBack' method\n",
                null
            )
            ->setHelp($this->help());
    }

    public function fire()
    {
        $fileRelativePath = $this->getArgument('name');

        if ($file = $this->newFileFromArgument($fileRelativePath)) {
            if ($this->writeClassInFile($file['path'], $file['name'])) {
                $this->info('Menu successfuly created at '.$this->path()->fromAppDir($file['path']));
            } else {
                $this->error('An error happened.');
            }
        } else {
            $this->writeln('Menu creation discarded.');
        }

        return SmileCommand::SUCCESS;
    }

    public function writeClassInFile($path, $fileName)
    {
        $template = $this->template($fileName, $path);

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
        $slash = Os::slash();
        $name = Os::toPathStyle($fileRelativePath);
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
        $dir = str($this->path()->appMenuDir().$relativeDir)->ensureEnd($slash);

        $fullPath = $dir.$filename.'.php';

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
            $this->writeln('Subdirectory created.');

            return true;
        }

        return false;
    }

    public function createBaseMenuFileIfNotExists()
    {
        if (!file_exists($this->path()->baseMenuFile())) {
            if (!$this->confirm([
                '',
                "The base Menu {$this->path()->baseMenuFileRelativeToApp()} does not exist.",
                $this->colorize('Will you like to generate it?', 'yellow'),
            ], 'yes')) {
                return false;
            }

            if ($this->generateBaseMenu()) {
                $this->info("Base menu created at {$this->path()->baseMenuFileRelativeToApp()}");

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
            $override = $this->confirm([
                "Menu {$this->path()->fromAppDir($file)} already exists.",
                $this->colorize('Do you want to overwrite it?', 'red'),
            ]);

            // On some OS (typically Windows) path is case-insentitive.
            // To avoid any insconsistency, we force the file to take the new name.
            $override &= rename($file, $file);

            return $override;
        }

        return true;
    }

    public function template($name, $path)
    {
        $namespace = $this->getFileNamespaceFrom($path);

        if ($this->getOption('paginable')) {
            return $this->paginableMenuTemplate($name, $namespace);
        }

        return $this->standardMenuTemplate($name, $namespace);
    }

    public function paginableMenuTemplate($name, $namespace)
    {
        $baseMenu = $this->baseMenuDetails($name, $namespace);
        $paginator = $this->paginatorDetails($name);

        $parameters = [
            'name'              => $name,
            'namespace'         => trim($namespace, '\\'),
            'baseMenu'          => $baseMenu['name'],
            'baseMenuFullName'  => $baseMenu['fullname'],
            'paginator'         => $paginator['name'],
            'paginatorFullName' => $paginator['fullname'],
            'methods'           => $this->generatePaginableMenuMethods(),
        ];

        $stubPath = $this->path()->frameworkStubDir('Menus/PaginableMenu.stub');

        $template = $this->generateTemplateFromStub($stubPath, $parameters);

        return $template;
    }

    public function standardMenuTemplate($name, $namespace)
    {
        $baseMenu = $this->baseMenuDetails($name, $namespace);

        $parameters = [
            'name'             => $name,
            'namespace'        => trim($namespace, '\\'),
            'baseMenu'         => $baseMenu['name'],
            'baseMenuFullName' => $baseMenu['fullname'],
            'methods'          => $this->generateSimpleMenuMethods(),
        ];

        $stubPath = $this->path()->frameworkStubDir('Menus/Menu.stub');

        $template = $this->generateTemplateFromStub($stubPath, $parameters);

        return $template;
    }

    public function baseMenuDetails($classname, $classNamespace)
    {
        $name = 'Menu';
        $fullname = 'App\Menus\\'.$name;

        // When the name of the menu is the same as the base menu name
        if ($classname === $name) {
            $name = 'BaseMenu';

            if ($classNamespace.'\\'.$classname === $fullname) {
                $fullname = "Rejoice\Menu\\".$name;
            } else {
                $fullname .= ' as '.$name;
            }
        }

        return compact('name', 'fullname');
    }

    public function paginatorDetails($classname)
    {
        $name = 'EloquentPaginator';
        $fullname = 'Rejoice\Menu\\'.$name;

        $name = $classname === $name ? 'PaginatorTrait' : 'Paginator';
        $fullname .= ' as '.$name;

        return compact('name', 'fullname');
    }

    public function generateSimpleMenuMethods()
    {
        $defaultOptions = ['message', 'actions'];
        $options = ['validate', 'save-as', 'after', 'on-next', 'on-back'];

        if ($this->getOption('all')) {
            $options = array_merge($defaultOptions, array_values($options));
        } elseif ($this->getOption('basic')) {
            $options = array_merge($defaultOptions, ['validate', 'save-as']);
        } else {
            $options = array_merge($defaultOptions, array_filter($options, function ($name) {
                return $this->getOption($name);
            }));
        }

        $noComment = $this->getOption('no-comment');
        $methods = array_map(function ($optionName) use ($noComment) {
            $methodStub = $this->path()->frameworkStubDir("Menus/simple/{$optionName}.method.stub");
            $commentStub = $this->path()->frameworkStubDir("Menus/simple/{$optionName}.comment.stub");

            $method = file_get_contents($methodStub);
            $comment = $noComment ? '' : file_get_contents($commentStub)."\n";

            return $comment.$method;
        }, $options);

        return implode("\n\n", $methods);
    }

    public function generatePaginableMenuMethods()
    {
        $stubs = ['message', 'paginate', 'action'];

        $noComment = $this->getOption('no-comment');
        $methods = array_map(function ($stubName) use ($noComment) {
            $methodStub = $this->path()->frameworkStubDir("Menus/paginable/{$stubName}.method.stub");
            $commentStub = $this->path()->frameworkStubDir("Menus/paginable/{$stubName}.comment.stub");

            $method = file_get_contents($methodStub);
            $comment = $noComment ? '' : file_get_contents($commentStub)."\n";

            return $comment.$method;
        }, $stubs);

        return implode("\n\n", $methods);
    }

    public function generateBaseMenu()
    {
        $template = file_get_contents($this->path()->frameworkStubDir('Menus/BaseMenu.stub'));

        return file_put_contents($this->path()->baseMenuFile(), $template) !== false;
    }

    public function help()
    {
        return
            "php smile make:menu EnterName
    Will create the menu class 'EnterName' in app/Menus/EnterName.php file.

php smile make:menu UserPurchaseFlow/GetName
    Will create the class 'GetName' in app/Menus/UserPurchaseFlow/GetName.php

Use the '-x' or '--no-comment' option to create the menu without the generated comment.";
    }
}

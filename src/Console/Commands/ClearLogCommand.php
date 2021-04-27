<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Commands\FrameworkCommand as Smile;
use Rejoice\Console\Option;
use Symfony\Component\Finder\Finder;

class ClearLogCommand extends Smile
{
    protected $defaultLogDirs = ['menus'];
    protected $defaultLogFiles = ['rejoice.log'];

    public function configure()
    {
        $this->setName('log:clear')
            ->setDescription('Clear log files.')
            ->addOption(
                'all',
                'a',
                Option::OPTIONAL,
                'Remove both log files created by rejoice and custom log files.',
                true
            )
            ->addOption(
                'file',
                'f',
                Option::OPTIONAL,
                'Remove only files.',
                false
            )
            ->addOption(
                'dir',
                'd',
                Option::OPTIONAL,
                'Remove only directories.',
                false
            );
    }

    public function fire()
    {
        $finder = (new Finder())->ignoreDotFiles(false)->sortByType();

        if (!$this->getOption('all')) {
            $finder->name($this->defaultLogs());
        }

        if ($this->getOption('file')) {
            $finder->files();
        } elseif ($this->getOption('dir')) {
            $finder->directories();
        }

        $path = storage_path('logs');

        $finder->in($path);

        $files = array_reverse(iterator_to_array($finder));

        try {
            $this->deleteIn($path, $files);

            foreach ($this->defaultLogDirs as $dirname) {
                $dir = storage_path('logs/'.$dirname);

                if (!is_dir($dir)) {
                    mkdir($dir);
                }
            }

            foreach ($this->defaultLogFiles as $filename) {
                $file = storage_path('logs/'.$filename);

                if (!file_exists($file)) {
                    file_put_contents($file, '');
                }
            }
        } catch (\Throwable $th) {
            $this->error('Error!');
            $this->write($th->getMessage().' in '.$th->getFile().' at line '.$th->getLine());

            return Smile::FAILURE;
        }

        $this->info('Logs cleared successfully!');

        return Smile::SUCCESS;
    }

    public function defaultLogs()
    {
        return array_merge($this->defaultLogFiles, $this->defaultLogDirs);
    }

    public function deleteIn($path, $files = null)
    {
        $files = $files ?: array_reverse(iterator_to_array((new Finder())
                ->ignoreDotFiles(false)
                ->sortByType()
                ->in($path)));

        foreach ($files as $file) {
            if ($file->isFile() && file_exists($file->getPathname())) {
                unlink($file->getPathname());
                continue;
            }

            $this->deleteIn($file->getPathname());

            if (file_exists($file->getPathname())) {
                rmdir($file->getPathname());
            }
        }
    }
}

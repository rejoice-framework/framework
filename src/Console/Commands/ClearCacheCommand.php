<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Commands\FrameworkCommand as Smile;
use Symfony\Component\Finder\Finder;

class ClearCacheCommand extends Smile
{
    protected $defaultCacheFiles = ['log-count.cache', 'rejoice.cache'];

    public function configure()
    {
        $this->setName('cache:clear')
            ->setDescription('Clear the cache files.');
    }

    public function fire()
    {
        $finder = new Finder();

        $finder->files()->ignoreDotFiles(false)->in(storage_path('cache'));

        try {
            foreach ($finder as $file) {
                if (in_array($file->getFileName(), $this->defaultCacheFiles)) {
                    file_put_contents($file->getPathname(), '');
                } else {
                    unlink($file->getPathname());
                }
            }
        } catch (\Throwable $th) {
            $this->error('Error!');
            $this->write($th->getMessage().' in '.$th->getFile().' at line '.$th->getLine());

            return Smile::FAILURE;
        }

        $this->info('Cache cleared successfully!');

        return Smile::SUCCESS;
    }
}

<?php

namespace Rejoice\Console\Devtool;

use Illuminate\Support\Str;
use Psy\Shell;

/**
 * Rejoice Devtool Class Alias Autoloader.
 *
 * Based on Laravel\Tinker\ClassAliasAutoloader.
 */
class ClassAliasAutoloader
{
    /**
     * The shell instance.
     *
     * @var \Psy\Shell
     */
    protected $shell;

    /**
     * All of the discovered classes.
     *
     * @var array
     */
    protected $classes = [];

    /**
     * Path to the vendor directory.
     *
     * @var string
     */
    protected $vendorPath;

    /**
     * Explicitly included namespaces/classes.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $includedAliases;

    /**
     * Excluded namespaces/classes.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $excludedAliases;

    /**
     * Register a new alias loader instance.
     *
     * @return static
     */
    public function register()
    {
        spl_autoload_register([$this, 'aliasClass']);
    }

    /**
     * Create a new alias loader instance.
     *
     * @param \Psy\Shell $shell
     * @param string     $classMapPath
     * @param string     $vendorPath
     * @param array      $includedAliases
     * @param array      $excludedAliases
     *
     * @return void
     */
    public function __construct(Shell $shell, $classMapPath, $vendorPath, array $includedAliases = [], array $excludedAliases = [])
    {
        $this->shell = $shell;
        $this->vendorPath = $vendorPath;
        $this->includedAliases = collect($includedAliases);
        $this->excludedAliases = collect($excludedAliases);

        $classes = require $classMapPath;

        foreach ($classes as $class => $path) {
            if (!$this->isAliasable($class, $path)) {
                continue;
            }

            $name = class_basename($class);

            if (!isset($this->classes[$name])) {
                $this->classes[$name] = $class;
            }
        }
    }

    /**
     * Find the closest class by name.
     *
     * @param string $class
     *
     * @return void
     */
    public function aliasClass($class)
    {
        if (Str::contains($class, '\\')) {
            return;
        }

        $fullName = $this->classes[$class] ?? false;

        if ($fullName) {
            $this->shell->writeStdout("[!] Resolving class '{$class}' to '{$fullName}'\n");

            class_alias($fullName, $class);
        }
    }

    /**
     * Unregister the alias loader instance.
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister([$this, 'aliasClass']);
    }

    /**
     * Handle the destruction of the instance.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->unregister();
    }

    /**
     * Whether a class may be aliased.
     *
     * For a class to be aliasable:
     *  1. It must be under a namespace different from the root namespace
     *  2. It must not be a class from a dependency package (no class from vendor)
     *  3. It must not be intentionally excluded by the developer
     *
     * @param string $class
     * @param string $path
     */
    public function isAliasable($class, $path)
    {
        // Classes under no namespace does not need to be aliased
        if (!Str::contains($class, '\\')) {
            return false;
        }

        if ($this->includedAliases->filter(function ($alias) use ($class) {
            return Str::startsWith($class, $alias);
        })->isNotEmpty()) {
            return true;
        }

        if (Str::startsWith($path, $this->vendorPath)) {
            return false;
        }

        if ($this->excludedAliases->filter(function ($alias) use ($class) {
            return Str::startsWith($class, $alias);
        })->isNotEmpty()) {
            return false;
        }

        return true;
    }
}

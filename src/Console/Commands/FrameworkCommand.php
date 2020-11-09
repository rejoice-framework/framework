<?php

namespace Rejoice\Console\Commands;

use Rejoice\Foundation\Kernel as Rejoice;

/**
 * Base class for all Rejoice command.
 * Provides some helpers method to interact more easyly with the application.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class FrameworkCommand extends SmileCommand
{
    /**
     * Instance of Rejoice.
     *
     * @var \Rejoice\Foundation\Kernel
     */
    protected $rejoice;

    protected $stubVariableDelimiter = ':';

    /**
     * @var \Rejoice\Foundation\Path
     */
    protected $paths;

    /**
     * @var \Prinx\Config
     */
    protected $config;

    public function getRejoice()
    {
        return $this->rejoice;
    }

    public function setRejoice(Rejoice $rejoice)
    {
        $this->rejoice = $rejoice;

        return $this;
    }

    /**
     * Get a configuration variable from the config.
     *
     * Returns the config object instance if no parameter passed.
     *
     *
     *
     * @param string $key
     * @param mixed  $default The default to return if the configuration is not found
     * @param bool   $silent  If true, will shutdown the exception throwing if configuration variable not found and no default was passed.
     *
     * @throws \RuntimeException
     *
     * @return Config|mixed
     */
    public function config($key = null, $default = null, $silent = false)
    {
        return $this->getRejoice()->config(...(func_get_args()));
    }

    /**
     * Return a path to a file or a folder.
     *
     *
     *
     * @param string|null $name
     *
     * @throws \RuntimeException
     *
     * @return string|\Rejoice\Foundation\Path
     */
    public function path($name = null)
    {
        return $this->getRejoice()->path($name);
    }

    public function generateTemplateFromStub(string $stubPath, array $parameters)
    {
        $template = file_get_contents($stubPath);

        foreach ($parameters as $name => $value) {
            $delimiter = $this->stubVariableDelimiter();
            $numberOfsides = 2;
            $length = strlen($name) + $numberOfsides * strlen($delimiter);
            $variableReference = str_pad($name, $length, $delimiter, STR_PAD_BOTH);

            $template = str_replace($variableReference, $value, $template);
        }

        return $template;
    }

    public function stubVariableDelimiter()
    {
        return $this->config('app.stub_variable_delimiter', $this->stubVariableDelimiter);
    }
}

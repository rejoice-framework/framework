<?php

namespace Rejoice\Console;

use Rejoice\Console\Traits\QuestionTrait;
use Rejoice\Console\Traits\StyleSheetTrait;
use Rejoice\Console\Traits\TableTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Smile extends Command
{
    use QuestionTrait, StyleSheetTrait, TableTrait;
    
    protected $colors = [
        'black',
        'red',
        'green',
        'yellow',
        'blue',
        'magenta',
        'cyan',
        'white',
        'default',
    ];

    /**
     * Intput Interface.
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * Output Interface.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * Do not implement this method in your command
     * This method is implicitely called by the `fire` method
     * When the command is run.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        return (int) $this->fire();
    }

    /**
     * This is the method called when the command is run
     * Implement the logic of the command here.
     *
     * @return int Command::SUCCESS or Command::FAILURE
     */
    public function fire()
    {
        $this->writeln('This command does not have a logic yet.');

        return Command::SUCCESS;
    }

    /**
     * Returns the argument value for a given argument name.
     *
     * @param  string                   $name
     * @throws InvalidArgumentException — When argument given doesn't exist
     * @return string|string[]|null     — The argument value
     */
    public function getArgument($name)
    {
        return $this->getInput()->getArgument($name);
    }

    /**
     * Get the object representing the input.
     *
     * @return InputInterface
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Returns the option value for a given option name.
     *
     * @param  string                    $name
     * @throws InvalidArgumentException  — When option given doesn't ex
     * @return string|string[]|bool|null — The option value
     */
    public function getOption($name)
    {
        return $this->getInput()->getOption($name);
    }

    /**
     * Get the object representing the output.
     *
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Draw a line with hyphen.
     *
     * @param  array  $options
     * @return void
     */
    public function drawSeparationLine($options = [
        'padding-top'    => true,
        'padding-bottom' => true,
        'fg'             => 'grey',
        'bg'             => 'black',
        'middle'         => '',
    ]) {
        $defaultOptions = [
            'padding-top'    => true,
            'padding-bottom' => true,
            'fg'             => 'grey',
            'bg'             => 'black',
            'middle'         => '',
        ];

        $options = array_merge($defaultOptions, $options);
        $padLength = 25;
        $sides = 2;
        $length = strlen($options['middle']) + ($sides * $padLength);
        $line = str_pad($options['middle'], $length, '-', STR_PAD_BOTH);
        $line = $options['padding-top'] ? "\n".$line : $line;
        $line = $options['padding-bottom'] ? $line."\n" : $line;

        $this->writeln($line);
    }

    /**
     * Writes a message to the output.
     *
     * @param string|iterable $messages The message as an iterable of strings or a single string
     * @param bool            $newline  Whether to add a newline
     * @param int             $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function write($messages, bool $newline = false, int $options = 0)
    {
        return $this->getOutput()->write(...(func_get_args()));
    }

    /**
     * Write a new line to the output.
     *
     * @param  string|iterable $messages The message to write. Can be a string or an iterable of strings
     * @param  int             $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     * @return mixed
     */
    public function writeln($messages, int $options = 0)
    {
        return $this->getOutput()->writeln(...(func_get_args()));
    }
}

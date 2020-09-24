<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Table;
use Rejoice\Console\TableDivider;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SmileCommand extends SymfonyCommand
{
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

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * Ask user for a response.
     *
     * @param string $question The question to ask
     * @param mixed  $default  The default value of the response
     *
     * @return void
     */
    public function ask($question, $default = null)
    {
        $helper = $this->getHelper('question');
        $args = \func_get_args();
        $quest = new Question(...$args);

        return $helper->ask($this->getInput(), $this->getOutput(), $quest);
    }

    /**
     * Return the string passed after appliying the color tags to it.
     *
     * @param string $message The string to colorize
     * @param string $fg      The foreground color
     * @param string $bg      The background color
     *
     * @return string
     */
    public function colorize($message, $fg = '', $bg = '')
    {
        $fg = $fg ?: 'grey';
        $bg = $bg ?: 'black';

        return '<fg='.$fg.';bg='.$bg.'>'.$message.'</>';
    }

    /**
     * Ask user for confirmation.
     *
     * @param string|string[] $question         The question to ask
     * @param mixed           $defaultResponse
     * @param array           $validResponses
     * @param array           $invalidResponses
     *
     * @return bool
     */
    public function confirm(
        $questions,
        $defaultResponse = 'no',
        array $validResponses = ['y', 'yes'],
        array $invalidResponses = ['n', 'no']
    ) {
        if (!is_array($questions)) {
            $questions = [$questions];
        }

        $last = count($questions) - 1;

        foreach ($questions as $key => $quest) {
            if ($key === $last) {
                break;
            }

            $this->writeln($quest);
        }

        $hasAccepted = null;
        $hasDeclined = null;

        do {
            if (null !== $hasAccepted && null !== $hasDeclined) {
                $this->error('Response must be '.implode(', ', $validResponses).' or '.implode(', ', $invalidResponses));
            }

            $response = $this->ask($questions[$last]." [$defaultResponse]: ", $defaultResponse);
            $response = strtolower($response);

            if (!($hasAccepted = in_array($response, $validResponses))) {
                $hasDeclined = in_array($response, $invalidResponses);
            }
        } while (!$hasAccepted && !$hasDeclined);

        return (bool) $hasAccepted;
    }

    public function createTable()
    {
        return new Table($this->getOutput());
    }

    /**
     * Draw a line with hyphen.
     *
     * @param array $options
     *
     * @return void
     */
    public function drawSeparationLine($options = [
        'padding-top'    => true,
        'padding-bottom' => true,
        'fg'             => 'grey',
        'bg'             => 'black',
        'middle'         => '',
    ])
    {
        $defaultOptions = [
            'padding-top'    => true,
            'padding-bottom' => true,
            'fg'             => 'grey',
            'bg'             => 'black',
            'middle'         => '',
        ];

        $options = array_merge($defaultOptions, $options);
        $line = '-----------------------'.$options['middle'].'--------------------------';
        $line = $options['padding-top'] ? "\n".$line : $line;
        $line = $options['padding-bottom'] ? $line."\n" : $line;

        $this->writeln($line);
    }

    /**
     * Write in console with foreground white on background red.
     *
     * @param string|array $messages
     *
     * @return void
     */
    public function error($messages)
    {
        $this->writeWithColor($messages, 'white', 'red');
    }

    /**
     * Do not implement this method in your command
     * This method is implicitely called by the `fire` method
     * When the command is run.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
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
     * @return int SmileCommand::SUCCESS or SmileCommand::FAILURE
     */
    public function fire()
    {
        $this->getOutput()->writeln('This command does not have any logic yet.');

        return SymfonyCommand::FAILURE;
    }

    /**
     * Returns the argument value for a given argument name.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException — When argument given doesn't exist
     *
     * @return string|string[]|null — The argument value
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
     * @param string $name
     *
     * @throws InvalidArgumentException — When option given doesn't ex
     *
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
     * Write in console with foreground green on background black.
     *
     * @param string|array $messages
     *
     * @return void
     */
    public function info($messages)
    {
        $this->writeWithColor($messages, 'green');
    }

    /**
     * Write in console with foreground black on background cyan.
     *
     * @param string|array $messages
     *
     * @return void
     */
    public function question($messages)
    {
        $this->writeWithColor($messages, 'black', 'cyan');
    }

    /**
     * Write in console with foreground green on background black.
     *
     * @param string|array $messages
     *
     * @return void
     */
    public function success($messages)
    {
        $this->info($messages);
    }

    public function tableLine()
    {
        return new TableDivider();
    }

    /**
     * Write in console with foreground red on background magenta.
     *
     * @param string|array $messages
     *
     * @return void
     */
    public function warning($messages)
    {
        $this->writeWithColor($messages, 'red', 'magenta');
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
     * Write in console with color.
     *
     * @param string|array $messages The message(s) to write with color
     * @param string       $fg       The foreground color
     * @param string       $bg       The background color
     *
     * @return void
     */
    public function writeWithColor($messages, $fg = '', $bg = '')
    {
        if (is_string($messages)) {
            $messages = [$messages];
        }

        foreach ($messages as $value) {
            if (!is_string($value)) {
                throw new \Exception('Only string and iterable containing string are supported by the writeln
                 method');
            }

            $this->writeln($this->colorize($value, $fg, $bg));
        }
    }

    /**
     * Write a new line to the output.
     *
     * @param string|iterable $messages The message to write. Can be a string or an iterable of strings
     * @param int             $options  A bitmask of options (one of the OUTPUT or VERBOSITY constants), is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     *
     * @return mixed
     */
    public function writeln($messages, int $options = 0)
    {
        return $this->getOutput()->writeln(...(func_get_args()));
    }
}

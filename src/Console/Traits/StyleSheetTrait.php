<?php

namespace Rejoice\Console\Traits;

/**
 * Console colors related Trait.
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
trait StyleSheetTrait
{
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
                throw new \Exception(
                    'Only string and iterable containing string are supported by the writeln method'
                );
            }

            $this->writeln($this->colorize($value, $fg, $bg));
        }
    }
}

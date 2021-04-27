<?php

namespace Rejoice\Jobs;

use GO\Job;
use GO\Scheduler as BaseScheduler;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Scheduler.
 */
class Scheduler extends BaseScheduler
{
    /**
     * Console application instance.
     *
     * @var \App\Console\Commands\ScheduleRunCommand
     */
    protected $schedulerCommand = null;

    public function setSchedulerCommand($command)
    {
        $this->schedulerCommand = $command;
    }

    /**
     * Console Output.
     *
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getSmileOutput()
    {
        return $this->schedulerCommand->getOutput();
    }

    /**
     * Smile console application.
     *
     * @return \Symfony\Component\Console\Application
     */
    public function getSmile()
    {
        return $this->schedulerCommand->getApplication();
    }

    /**
     * Rejoice mock for console.
     *
     * @return \Rejoice\Foundation\Kernel
     */
    public function getRejoice()
    {
        return $this->schedulerCommand->getRejoice();
    }

    /**
     * Rejoice default logger.
     *
     * @return \Prinx\Notify\Log
     */
    public function logger()
    {
        return $this->getRejoice()->logger();
    }

    /**
     * Schedule a Smile Command.
     *
     * @param array|string|null $arguments
     *
     * @return Job
     */
    public function command(string $command, $arguments = null)
    {
        return $this->call(function (string $commandName, $arguments) {
            try {
                $command = $this->getSmile()->find($commandName);

                $input = $this->resolveInput($arguments);
                $output = new BufferedOutput();

                $command->run($input, $output);

                return $output->fetch();
            } catch (CommandNotFoundException $e) {
                $this->logger()->error($e->getMessage());

                return $e->getMessage();
            } catch (\Throwable $th) {
                $message = sprintf('Error when executing scheduled command "%s": %s',
                    $commandName,
                    $th->getMessage()
                );

                $this->logger()->error($message);

                return $message;
            }
        }, [
            $command,
            $arguments,
        ]);
    }

    /**
     * Resolve input.
     *
     * @param array|string $arguments
     *
     * @return InputInterface|StreamableInputInterface
     */
    public function resolveInput($arguments)
    {
        if (is_string($arguments)) {
            return new StringInput($arguments);
        }

        if (is_array($arguments)) {
            return new ArrayInput($arguments);
        }

        return new ArgvInput();
    }
}

<?php

namespace Rejoice\Console\Commands;

use Rejoice\Console\Commands\FrameworkCommand as Smile;
use Rejoice\Jobs\Scheduler;

class ScheduleRunCommand extends Smile
{
    public function configure()
    {
        $this->setName('schedule:run')
            ->setDescription('Run the scheduler');
    }

    public function fire()
    {
        $scheduler = new Scheduler();
        $scheduler->setSchedulerCommand($this);

        $jobClass = $this->config('app.jobs_class');
        $jobs = new $jobClass();

        $jobs->schedule($scheduler);

        $scheduler->run();

        return Smile::SUCCESS;
    }
}

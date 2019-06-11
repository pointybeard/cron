<?php

declare(strict_types=1);

namespace Symphony\Shell\Command\Cron;

use Symphony\Shell\Lib\AuthenticatedCommand;
use Symphony\Shell\Lib\Traits;
use Symphony\Shell\Lib\Shell as Shell;
use pointybeard\Symphony\Extensions\Cron;

class RunTasks extends AuthenticatedCommand
{
    use Traits\hasRequiresAuthenticationTrait;

    public function usage()
    {
        echo 'usage: run-tasks [OPTION...]
Runs all ready tasks

Examples:
... run-tasks

';
    }

    public function run(array $args = null)
    {
        if (!Shell::Author()->isDeveloper()) {
            Shell::message('Only developers can run cron related tasks.');
            exit(1);
        }

        \Extension_Cron::init();

        $iterator = new Cron\TaskIterator(realpath(MANIFEST.'/cron'), Shell::Database());

        $tasks = [];

        foreach ($iterator as $task) {
            if (true !== $task->enabledReal() || $task->nextExecution() > 0) {
                continue;
            }
            $tasks[] = $task;
        }

        echo
            'Running Tasks ('.count($tasks).')'.PHP_EOL.
            '----------------'.PHP_EOL
        ;

        foreach ($tasks as $index => $task) {
            $start = precision_timer();

            Shell::message(sprintf(
                '(%d/%d): %s  ... ',
                $index + 1,
                count($tasks),
                $task->name
            ), false, false);

            $task->run();

            Shell::message(sprintf(
                'complete (%s sec)',
                precision_timer('stop', $start)
            ), false, true);
        }

        Shell::message('All avaialble tasks run. Exiting.');
    }
}

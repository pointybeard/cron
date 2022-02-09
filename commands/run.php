<?php

declare(strict_types=1);

/*
 * This file is part of the "Cron Tasks Extension for Symphony CMS" repository.
 *
 * Copyright 2009-2018 Alannah Kearney, Allen Chang
 * Copyright 2019-2021 Alannah Kearney
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Console\Commands\Cron;

use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Foundation\BroadcastAndListen;
use pointybeard\Symphony\Extensions\Console;
use pointybeard\Symphony\Extensions\Console\Commands\Console\Symphony;
use pointybeard\Symphony\Extensions\Cron;
use pointybeard\Symphony\Extensions\Cron\Task;
use SebastianBergmann\Timer\Timer;
use SebastianBergmann\Timer\ResourceUsageFormatter;

class run extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface, BroadcastAndListen\Interfaces\AcceptsListenersInterface
{
    use BroadcastAndListen\Traits\HasListenerTrait;
    use BroadcastAndListen\Traits\HasBroadcasterTrait;
    use Console\Traits\hasCommandRequiresAuthenticateTrait;

    public function __construct()
    {
        parent::__construct();
        $this
            ->description('run task(s) that are ready (or force a specific task)')
            ->version('1.0.0')
            ->example(
                'symphony -t 4141e465 cron run'.PHP_EOL.
                'symphony -t 4141e465 cron run --task=mytask --force'
            )
            ->support("If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/cron/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2019-2020 Alannah Kearney. See ".realpath(__DIR__.'/../LICENCE')." for full software licence information.\r\n")
        ;
    }

    public function init(): void
    {
        parent::init();

        $this
            ->addInputToCollection(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('task')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('name of a specific task to run')
                    ->validator(
                        function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                            $task = Cron\Task::load(sprintf(
                                '%s/%s',
                                MANIFEST.'/cron',
                                strtolower($context->find('task'))
                            ));

                            return $task;
                        }
                    )
                    ->default(null)
            )
            ->addInputToCollection(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('force')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('force all tasks (if --task is not set), or a specifc task, to be run')
                    ->default(false)
            )
        ;
    }

    public function execute(Cli\Input\Interfaces\InputHandlerInterface $input): bool
    {
        $iterator = new Cron\TaskIterator(MANIFEST.'/cron');

        $tasks = [];

        foreach ($iterator as $task) {
            // --force is not set and this task isn't due to be executed
            if (true !== $input->find('force') && false == $task->isReadyToRun()) {
                continue;

            // --task is set and the specified task filename is the same as
            // $task->filename
            } elseif (null !== $input->find('task') && (string) $input->find('task')->filename == $task->filename) {
                $tasks[] = $task;
                break;

            // --task is not set
            } elseif (null === $input->find('task')) {
                $tasks[] = $task;
            }
        }

        if (count($tasks) <= 0) {
            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Cli\Message\Message())
                    ->message('No tasks ready to run. Nothing to do.')
                    ->foreground(Colour::FG_YELLOW)
            );

            return true;
        }

        $this->broadcast(
            Symphony::BROADCAST_MESSAGE,
            E_NOTICE,
            (new Cli\Message\Message())
                ->message(sprintf('Running Tasks (%d task/s found)', count($tasks)))
                ->foreground(Colour::FG_DEFAULT)
        );

        $timer = new Timer;

        foreach ($tasks as $index => $task) {

            $timer->start();

            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Cli\Message\Message())
                    ->message(sprintf(
                        '(%d/%d): %s  ... ',
                        $index + 1,
                        count($tasks),
                        $task->name
                    ))
                    ->foreground(Colour::FG_GREEN)
                    ->flags(null)
            );

            try {
                $task->run(true == (bool) $input->find('force') ? Task::FLAG_FORCE : null);

                $elapsed = $timer->stop();

                if (true == ($elapsed instanceof \SebastianBergmann\Timer\Duration)) {
                    $elapsed = strtolower((new ResourceUsageFormatter)->resourceUsage($elapsed));
                }

                $this->broadcast(
                    Symphony::BROADCAST_MESSAGE,
                    E_NOTICE,
                    (new Cli\Message\Message())
                        ->message(sprintf(
                            'done (%s)',
                            $elapsed
                        ))
                        ->foreground(Colour::FG_DEFAULT)
                );
            } catch (\Exception $ex) {
                $this->broadcast(
                    Symphony::BROADCAST_MESSAGE,
                    E_ERROR,
                    (new Cli\Message\Message())
                        ->message('failed! Returned: '.$ex->getMessage())
                        ->foreground(Colour::FG_RED)
                );
            }
        }

        return true;
    }
}

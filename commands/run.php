<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Console\Commands\Cron;

use Extension_Cron;
use pointybeard\Symphony\Extensions\Console;
use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Symphony\Extensions\Cron;
use SebastianBergmann\Timer\Timer;

class Run extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface
{
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
            ->support("If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/cron/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2019 Alannah Kearney. See ".realpath(__DIR__.'/../LICENCE')." for full software licence information.\r\n")
        ;
    }

    public function init(): void
    {
        parent::init();
        Extension_Cron::init();
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
                                CRON_PATH,
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
        Extension_Cron::init();

        $iterator = new Cron\TaskIterator(
            realpath(MANIFEST.'/cron')
        );

        $tasks = [];

        foreach ($iterator as $task) {
            // --force is not set and this task isn't due to be executed
            if (true !== $input->find('force') && (true !== $task->enabledReal() || $task->nextExecution() > 0)) {
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
            (new Cli\Message\Message())
                ->message('No tasks ready to run. Nothing to do.')
                ->foreground(Colour::FG_YELLOW)
                ->background(Colour::BG_DEFAULT)
                ->display()
            ;

            return true;
        }

        (new Cli\Message\Message())
            ->message(sprintf('Running Tasks (%d task/s found)', count($tasks)))
            ->foreground(Colour::FG_WHITE)
            ->background(Colour::BG_BLUE)
            ->display()
        ;

        foreach ($tasks as $index => $task) {
            Timer::start();

            (new Cli\Message\Message())
                ->message(sprintf(
                    '(%d/%d): %s  ... ',
                    $index + 1,
                    count($tasks),
                    $task->name
                ))
                ->foreground(Colour::FG_GREEN)
                ->flags(null)
                ->display()
            ;

            try {
                $task->run((bool) $input->find('force'));

                $time = Timer::stop();

                (new Cli\Message\Message())
                    ->message(sprintf(
                        'done (%s)',
                        strtolower(
                            Timer::resourceUsage()
                    )
                    ))
                    ->foreground(Colour::FG_DEFAULT)
                    ->display()
                ;
            } catch (\Exception $ex) {
                echo Colour::colourise('failed!', Colour::FG_RED).PHP_EOL;
                echo Colour::colourise($ex->getMessage(), Colour::FG_RED).PHP_EOL;
            }
        }

        return true;
    }
}

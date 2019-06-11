<?php

declare(strict_types=1);

namespace Symphony\Console\Commands\Cron;

use Extension_Cron;
use Symphony\Console;
use pointybeard\Helpers\Cli;
use pointybeard\Symphony\Extensions\Cron;
use SebastianBergmann\Timer\Timer;

class Tasks extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface
{
    use Console\Traits\hasCommandRequiresAuthenticateTrait;

    public function __construct()
    {
        parent::__construct();
        $this
            ->description('run tasks and perform maintenance such as enabling and disabling tasks')
            ->version('1.0.0')
            ->example(
                'symphony -t 4141e465 cron tasks run'.PHP_EOL
            )
            ->support("If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/cron/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2019 Alannah Kearney. See ".realpath(__DIR__.'/../LICENCE')." for full software licence information.\r\n")
        ;
    }

    public function init(): void
    {
        parent::init();

        $this
            ->addInputToCollection(
                Cli\Input\InputTypeFactory::build('Argument')
                    ->name('action')
                    ->flags(Cli\Input\AbstractInputType::FLAG_REQUIRED)
                    ->description('can be run, enable, disable, or status')
                    ->validator(
                        function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                            $action = strtolower($context->find('action'));
                            if (!in_array($action, ['run', 'enable', 'disable', 'status'])) {
                                throw new Console\Exceptions\ConsoleException('Supported ACTIONs are run, enable, disable, and status.');
                            }

                            return $action;
                        }
                    )
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
            if (true !== $task->enabledReal() || $task->nextExecution() > 0) {
                continue;
            }
            $tasks[] = $task;
        }

        if (count($tasks) <= 0) {
            (new Cli\Message\Message())
                ->message('No tasks to run. Nothing to do.')
                ->foreground(Cli\Colour\Colour::FG_YELLOW)
                ->background(Cli\Colour\Colour::BG_DEFAULT)
                ->display()
            ;

            return true;
        }

        (new Cli\Message\Message())
            ->message(sprintf('Running Tasks (%d task/s found)', count($tasks)))
            ->foreground(Cli\Colour\Colour::FG_WHITE)
            ->background(Cli\Colour\Colour::BG_BLUE)
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
                ->foreground(Cli\Colour\Colour::FG_GREEN)
                ->background(Cli\Colour\Colour::BG_DEFAULT)
                ->flags(null)
                ->display()
            ;

            $task->run();

            $time = Timer::stop();

            (new Cli\Message\Message())
                ->message(sprintf(
                    'done (%s)',
                    strtolower(
                        Timer::resourceUsage()
                )
                ))
                ->foreground(Cli\Colour\Colour::FG_DEFAULT)
                ->background(Cli\Colour\Colour::BG_DEFAULT)
                ->display()
            ;
        }

        return true;
    }
}

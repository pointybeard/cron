<?php

declare(strict_types=1);

use pointybeard\Symphony\Extensions\Cron;
use SebastianBergmann\Timer\Timer;

class EventCronTriggerTasks extends SectionEvent
{
    public static function about(): array
    {
        return [
            'name' => 'Cron: Trigger Tasks',
            'author' => [
                'name' => 'Alannah Kearney',
                'website' => 'http://alannahkearney.com',
                'email' => 'hi@alannahkearney.com',
            ],
            'release-date' => '2020-04-17',
            'trigger-condition' => 'A task that is due to run.',
        ];
    }

    public function load()
    {
        return $this->__trigger();
    }

    protected function __trigger(): XMLElement
    {
        Extension_Cron::init();

        $result = new XMLElement('cron-trigger-tasks');
        $tasks = new XMLElement('tasks');

        $it = new Cron\TaskIterator(MANIFEST . '/cron');

        $skipped = $failed = $run = 0;

        foreach ($it as $index => $t) {
            if (false == $t->isReadyToRun()) {
                ++$skipped;
                continue;
            }

            Timer::start();

            $item = new XMLElement('item');

            try {
                $t->run();

                $time = Timer::stop();

                $item->setAttributeArray(
                    [
                    'name' => (string) $t->name(),
                    'filename' => (string) $t->filename(),
                    'status' => 'success',
                    'time' => $time, ]
                );
                ++$run;
            } catch (\Exception $ex) {
                $item->setAttribute('status', 'failed');
                $item->appendChild(new XMLElement('error', $ex->getMessage()));
                ++$failed;
            }
            $tasks->appendChild($item);
        }

        $tasks->setAttributeArray([
            'total' => $it->count(),
            'skipped' => $skipped,
            'failed' => $failed,
            'run' => $run,
        ]);

        $result->appendChild($tasks);

        return $result;
    }

    public static function documentation(): string
    {
        return '<h3>Event Cron: Trigger Tasks</h3><p>Checks if there any tasks that are due to run and runs them.</p>';
    }
}

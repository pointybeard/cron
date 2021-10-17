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

        $it = new Cron\TaskIterator(MANIFEST.'/cron');

        $skipped = $failed = $run = 0;

        $timer = new Timer;

        foreach ($it as $index => $t) {
            if (false == $t->isReadyToRun()) {
                ++$skipped;

                continue;
            }

            $timer->start();

            $item = new XMLElement('item');

            try {
                $t->run();

                $time = $timer->stop();

                $item->setAttributeArray(
                    [
                        'name' => (string) $t->name(),
                        'filename' => (string) $t->filename(),
                        'status' => 'success',
                        'time' => $time->asSeconds()
                    ]
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

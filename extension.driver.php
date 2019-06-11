<?php
include __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extensions\Cron;

class Extension_Cron extends Extension
{
    const SORT_ASCENDING = 'asc';
    const SORT_DESCENDING = 'desc';

    public function fetchNavigation()
    {
        return [
            [
                'location' => 'System',
                'name' => 'Cron',
                'link' => '/',
            ],
        ];
    }

    public static function init()
    {
    }

    public function uninstall()
    {
        \Symphony::Database()->query("DROP TABLE `tbl_cron`");
    }

    public function install()
    {
        return \Symphony::Database()->query("CREATE TABLE `tbl_cron` (
              `name` varchar(100) NOT NULL,
              `last_executed` int(14) DEFAULT NULL,
              `enabled` set('yes','no') NOT NULL DEFAULT '',
              `last_output` text,
              `force_execution` set('yes','no') DEFAULT 'no',
              PRIMARY KEY (`name`)
            )"
        );
    }

    public static function getSortedTaskList($direction=self::SORT_ASCENDING) {
        $iterator = new Cron\TaskIterator(realpath(MANIFEST . '/cron'));

        $tasks = [];

        if($iterator->count() > 0) {
            foreach($iterator as $t) {
                $tasks[(string)$t->filename] = $t;
            }

            (
                $direction == self::SORT_ASCENDING
                    ? ksort($tasks)
                    : krsort($tasks)
            );

        }

        return $tasks;
    }
}

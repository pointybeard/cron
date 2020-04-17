<?php

declare(strict_types=1);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf(
        'Could not find composer autoload file %s. Did you run `composer update` in %s?',
        __DIR__.'/vendor/autoload.php',
        __DIR__
    ));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extensions\Cron;

define_safe('CRON_PATH', MANIFEST.'/cron');

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

    public function enable()
    {
        return $this->install();
    }

    public function uninstall()
    {
        \Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_cron`');
    }

    public function install()
    {
        if (!is_dir(MANIFEST.'/cron')) {
            \General::realiseDirectory(MANIFEST.'/cron', 0777, false);
        }

        return \Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_cron` (
              `name` varchar(100) NOT NULL,
              `last_executed` int(14) DEFAULT NULL,
              `enabled` set('yes','no') NOT NULL DEFAULT '',
              `last_output` text,
              `force_execution` set('yes','no') DEFAULT 'no',
              PRIMARY KEY (`name`)
            )"
        );
    }

    public static function getSortedTaskList($direction = self::SORT_ASCENDING)
    {
        $iterator = new Cron\TaskIterator(realpath(MANIFEST.'/cron'));

        $tasks = [];

        if ($iterator->count() > 0) {
            foreach ($iterator as $t) {
                $tasks[(string) $t->filename] = $t;
            }

            (
                self::SORT_ASCENDING == $direction
                    ? ksort($tasks)
                    : krsort($tasks)
            );
        }

        return $tasks;
    }
}

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

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf('Could not find composer autoload file %s. Did you run `composer update` in %s?', __DIR__.'/vendor/autoload.php', __DIR__));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Helpers\Functions\Files;
use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extensions\Cron;

// This file is included automatically in the composer autoloader, however,
// Symphony might try to include it again which would cause a fatal error.
// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_Cron')) {
    class Extension_Cron extends Extended\AbstractExtension
    {
        public const SORT_ASCENDING = 'asc';

        public const SORT_DESCENDING = 'desc';

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
            parent::install();

            try {
                Files\realise_directory(MANIFEST.'/cron');
            } catch (Files\Exceptions\Directory\AlreadyExistsException $ex) {
                // Its okay, the cron folder already exists.
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
}

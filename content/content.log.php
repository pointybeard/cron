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

    class contentExtensionCronLog extends AdministrationPage
    {
        public function view()
        {
            Extension_Cron::init();

            try {
                $task = (new Cron\Task(Symphony::Database()))->load(realpath(MANIFEST.'/cron').'/'.$this->_context[0]);
            } catch (Exception $e) {
                throw new \SymphonyErrorPage('The cron task <code>'.$this->_context[0].'</code> could not be found.', 'Task Not Found');
            }

            header('Content-Type: text/plain');
            echo 'Log for task `'.$task->name.'`:'.PHP_EOL.'------------------------------------'.PHP_EOL.PHP_EOL;
            echo $task->getLog();
            exit();
        }
    }

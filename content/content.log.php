<?php

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
            echo "Log for task `".$task->name."`:".PHP_EOL."------------------------------------".PHP_EOL.PHP_EOL;
            echo $task->getLog();
            exit();
        }
    }

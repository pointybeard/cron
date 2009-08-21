<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionCronLog extends AdministrationPage{

		public function view(){

			if(!file_exists(MANIFEST . '/cron/' . $this->_context[0])){
				throw new SymphonyErrorPage('The cron task <code>' . $this->_context[0] . '</code> could not be found.', 'Task Not Found');
			}
			
			$this->_driver = Administration::instance()->ExtensionManager->create('cron');

			$task = new CronTask(MANIFEST . '/cron/' . $this->_context[0]);
			
			header('Content-Type: text/plain');
			
			echo $task->last_output;
			exit();
		}
		
	}
	

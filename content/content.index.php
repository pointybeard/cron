<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionCronIndex extends AdministrationPage{
		
		private static function __minutesToHumanReadable($minutes){
			
			$string = NULL;
			
			// WEEKS
			if($minutes >= (7 * 24 * 60)){
				$value = floor((float)$minutes * (1 / (7 * 24 * 60)));
				$string = $value . ' week' . ($value == 1 ? NULL : 's');
				
				$minutes -= ($value * 60 * 24 * 7);
			}
			
			// DAYS
			if($minutes >= (24 * 60)){
				$value = floor((float)$minutes * (1 / (24 * 60)));
				$string .= ' ' . $value . ' day' . ($value == 1 ? NULL : 's');
				
				$minutes -= ($value * 60 * 24);
			}

			// HOURS
			if($minutes >= 60){
				$value = floor((float)$minutes * (1 / 60));
				$string .= ' ' .  $value . ' hour' . ($value == 1 ? NULL : 's');
				
				$minutes -= ($value * 60);
			}

			$string .= ' ' . $minutes . ' minute' . ($minutes == 1 ? NULL : 's');
			
			return trim($string);
			
		}
		
		public function view(){
			$this->setPageType('table');	
			$this->setTitle('Symphony &ndash; Cron');

			$this->appendSubheading('Cron Tasks', Widget::Anchor(
				__('Create New'), $this->_Parent->getCurrentPageURL() . 'new/',
				'Create a cron task', 'create button'
			));			
			
			
			$driver = Administration::instance()->ExtensionManager->create('cron');
			
			$iterator = new CronTaskIterator(MANIFEST . '/cron');

			$count = 0;
			foreach($iterator as $c){
				$count++;
			}
			
			$iterator->rewind();
			
			//$this->Form->setAttribute('action', URL . '/symphony/system/extensions/');

			$aTableHead = array(
				array('Name', 'col'),
				array('Description', 'col'),				
				array('Enabled', 'col'),
				array('Last Executed', 'col'),
				array('Next Execution', 'col'),
				array('Last Output', 'col'),
			);	

			$aTableBody = array();

			if($count == 0){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
								);
			}

			else{
				foreach($iterator as $task){

					$td1 = Widget::TableData(Widget::Anchor($task->name, sprintf('%sedit/%s/', Administration::instance()->getCurrentPageURL(), $task->filename)));
					
					$td2 = Widget::TableData((is_null($task->description) ? 'None' : $task->description));
					if(is_null($task->description)) $td2->setAttribute('class', 'inactive');

					$td3 = Widget::TableData(($task->enabledReal() == true ? 'Yes' : 'No'));
					if($task->enabled == false) $td3->setAttribute('class', 'inactive');
					
					$td4 = Widget::TableData(
						(!is_null($task->last_executed) ? DateTimeObj::get(__SYM_DATETIME_FORMAT__, $task->last_executed): 'Unknown')
					);
					if(is_null($task->last_executed)) $td4->setAttribute('class', 'inactive');

					$td5 = Widget::TableData(
						(!is_null($task->nextExecution()) ? self::__minutesToHumanReadable(ceil($task->nextExecution() * (1/60))) : 'Unknown')
					);
					if(is_null($task->nextExecution()) || $task->enabledReal() == false) $td5->setAttribute('class', 'inactive');
					
					if(is_null($task->last_output)){
						$td6 = Widget::TableData('None', 'inactive');
					}
					else{
						$td6 = Widget::TableData(Widget::Anchor('view', sprintf('%slog/%s/', Administration::instance()->getCurrentPageURL(), $task->filename)));
					}
					
					$td6->appendChild(Widget::Input('items['.$task->filename.']', 'on', 'checkbox'));
					
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4, $td5, $td6));

				}
			}

			$table = Widget::Table(
								Widget::TableHead($aTableHead), 
								NULL, 
								Widget::TableBody($aTableBody)
						);

			$this->Form->appendChild($table);
			
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('enable', false, __('Enable')),
				array('disable', false, __('Disable')),
				array('remove', false, __('Remove'), 'confirm'),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			$this->Form->appendChild($tableActions);			
			
			
		}

		public function action(){
			$checked = @array_keys($_POST['items']);

			if(isset($_POST['with-selected']) && is_array($checked) && !empty($checked)){

				$action = $_POST['with-selected'];

				switch($action){

					case 'enable':	
					
						foreach($checked as $c){
							$sql = sprintf("INSERT INTO `tbl_cron` VALUES ('%s', NULL, 1, NULL) ON DUPLICATE KEY UPDATE `enabled` = 1", $c);
							Symphony::Database()->query($sql);							
						}
						
						break;

					case 'disable':		
						$sql = sprintf("UPDATE `tbl_cron` SET `enabled` = 0 WHERE `name` IN ('%s')", implode("', '", $checked));
						Symphony::Database()->query($sql);
						break;
						
					case 'remove':		
						$sql = sprintf("DELETE FROM `tbl_cron` WHERE `name` IN ('%s')", implode("', '", $checked));
						Symphony::Database()->query($sql);
						
						foreach($checked as $c){
							General::deleteFile(MANIFEST . '/cron/' . $c);
						}
						
						break;						

				}		

				redirect(Administration::instance()->getCurrentPageURL());
			}			
		}
	
	}
	

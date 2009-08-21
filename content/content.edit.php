<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionCronEdit extends AdministrationPage{

		private $_driver;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->_driver = Administration::instance()->ExtensionManager->create('cron');
		}
		
		public function view(){			

			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/cron/assets/styles.css', 'screen', 70);

			if(!file_exists(MANIFEST . '/cron/' . $this->_context[0])){
				throw new SymphonyErrorPage('The cron task <code>' . $this->_context[0] . '</code> could not be found.', 'Task Not Found');
			}

			$task = new CronTask(MANIFEST . '/cron/' . $this->_context[0]);
			
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);

			if(isset($this->_context[1])){
				switch($this->_context[1]){
					
					case 'saved':
						$this->pageAlert(
							__(
								'Cron Task updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Cron Tasks</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/extension/cron/new/', 
									URL . '/symphony/extension/cron/' 
								)
							), 
							Alert::SUCCESS);
						break;
						
					case 'created':
						$this->pageAlert(
							__(
								'Cron Task created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all Cron Tasks</a>', 
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
									URL . '/symphony/extension/cron/new/', 
									URL . '/symphony/extension/cron/'
								)
							), 
							Alert::SUCCESS);
						break;
					
				}
			}
		
			$this->setTitle('Symphony &ndash; Cron &ndash; ' . $task->name);
			$this->appendSubheading($task->name);
			
			if(!empty($_POST)) $fields = $_POST['fields'];
			else{
				$fields = array(
					'name' => General::sanitize($task->name),
					'command' => General::sanitize($task->command),					
					'description' => General::sanitize($task->description),
					'interval' => $task->interval,
					'interval-type' => $task->{'interval-type'},
					'start' => (!is_null($task->start) ? DateTimeObj::get('Y-m-d H:i:s', $task->start) : NULL),
					'finish' => (!is_null($task->finish) ? DateTimeObj::get('Y-m-d H:i:s', $task->finish) : NULL),
					'enabled' => $task->enabled
				);
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');
			
			$label = Widget::Label('Name');
			$label->appendChild(Widget::Input('fields[name]', $fields['name']));
			$fieldset->appendChild((isset($this->_errors['name']) ? $this->wrapFormElementWithError($label, $this->_errors['name']) : $label));

			$label = Widget::Label('Command');
			$label->appendChild(Widget::Input('fields[command]', $fields['command']));
			$fieldset->appendChild((isset($this->_errors['command']) ? $this->wrapFormElementWithError($label, $this->_errors['command']) : $label));

			$label = Widget::Label('Description <i>Optional</i>');
			$label->appendChild(Widget::Input('fields[description]', $fields['description']));
			$fieldset->appendChild((isset($this->_errors['description']) ? $this->wrapFormElementWithError($label, $this->_errors['description']) : $label));
			
			$group = new XMLElement('div', NULL, array('class' => 'group'));

			$label = Widget::Label('Start Date <i>Optional</i>');
			$label->appendChild(Widget::Input('fields[start]', $fields['start']));
			$group->appendChild((isset($this->_errors['start']) ? $this->wrapFormElementWithError($label, $this->_errors['start']) : $label));
			
			$label = Widget::Label('Finish Date <i>Optional</i>');
			$label->appendChild(Widget::Input('fields[finish]', $fields['finish']));
			$group->appendChild((isset($this->_errors['finish']) ? $this->wrapFormElementWithError($label, $this->_errors['finish']) : $label));			

			$fieldset->appendChild($group);
			
			$p = new XMLElement('p', 'This task will be not run until after the <strong>start date</strong>, and will cease to trigger beyond the <strong>finish date</strong>.');
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);
			
			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'secondary');

			$label = Widget::Label();
			$input = Widget::Input('fields[interval]', (int)max(1, $fields['interval']), NULL, array('size' => '6'));
			$options = array(
				array('minute', ($fields['interval-type'] == 'minute'), 'minutes'),
				array('hour', ($fields['interval-type'] == 'hour'), 'hours'),
				array('day', ($fields['interval-type'] == 'day'), 'days'),
				array('week', ($fields['interval-type'] == 'week'), 'weeks')				
			);
			$select = Widget::Select('fields[interval-type]', $options, array('class' => 'inline'));
			
			$label->setValue(__('Run this task every %s %s', array($input->generate(false), $select->generate(false))));
			if(isset($this->_errors['interval'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['interval']));
			else $div->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('fields[enabled]', 'yes', 'checkbox', (isset($fields['enabled']) && (bool)$fields['enabled'] == true ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Enable this task', array($input->generate(false))));
			$div->appendChild($label);

			$p = new XMLElement('p', '&uarr; Unless a <strong>start date</strong> has been specified, this task will be executed once the current date plus the interval specified has passed.');
			$p->setAttribute('class', 'help');
			$div->appendChild($p);
						
			$this->Form->appendChild($div);
		
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', 'Save Changes', 'submit', array('accesskey' => 's')));

			$button = new XMLElement('button', __('Delete'));
			$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => 'Delete this task'));
			$div->appendChild($button);

			$this->Form->appendChild($div);
				
		}
		
		public function action(){
			
			if(array_key_exists('save', $_POST['action']) || array_key_exists('done', $_POST['action'])){

				$fields = $_POST['fields'];

				$this->_errors = array();

				if(!isset($fields['name']) || strlen(trim($fields['name'])) == 0) $this->_errors['name'] = 'Name is a required field.';
				else{
					
					$filename = strtolower(Lang::createFilename($fields['name']));
					$file = MANIFEST . '/cron/' . $filename;

					##Duplicate
					if($file != MANIFEST . '/cron/' . $this->_context[0] && file_exists($file)){
						$this->_errors['name'] = __('A task with that name already exists. Please choose another.');
					}
				}
				
				if(!isset($fields['command']) || strlen(trim($fields['command'])) == 0) $this->_errors['command'] = 'Command is a required field.';
				
				if(!isset($fields['interval']) || strlen(trim($fields['interval'])) == 0) $this->_errors['interval'] = 'Interval is a required field.';
				elseif(!is_numeric($fields['interval']) || (int)$fields['interval'] == 0){
					$this->_errors['interval'] = 'Interval must be a positive integer value.';
				}			
				
				if(isset($fields['start']) && strlen(trim($fields['start'])) > 0){
					
					$time = strtotime($fields['start']);

					$info = getdate($time);

					if($time == false || $info == false || !checkdate($info['mon'], $info['mday'], $info['year'])){
						$this->_errors['start'] = 'Start Date is invalid.';
					}
					
				}

				if(isset($fields['finish']) && strlen(trim($fields['finish'])) > 0){

					$time = strtotime($fields['finish']);

					$info = getdate($time);

					if($time == false || $info === false || !checkdate($info['mon'], $info['mday'], $info['year'])){
						$this->_errors['finish'] = 'Finish Date is invalid.';
					}
					elseif(!isset($this->_errors['start']) && isset($fields['start']) && strlen(trim($fields['start'])) > 0){
						if(strtotime($fields['finish']) <= strtotime($fields['start'])){
							$this->_errors['finish'] = 'Finish Date must occur <strong>after</strong> Start Date.';
						}
					}
					
				}
				
				if(empty($this->_errors)){
					
					$task = new CronTask;
					 
					$task->path = $file;
					$task->filename = $filename;
					$task->name = $fields['name'];					
					$task->command = $fields['command'];
					$task->setInterval($fields['interval'], $fields['interval-type']);
					$task->start = (strlen(trim($fields['start'])) > 0 ? strtotime($fields['start']) : NULL);
					$task->finish = (strlen(trim($fields['finish'])) > 0 ? strtotime($fields['finish']) : NULL);
					$task->description = $fields['description'];
					$task->enabled = (isset($fields['enabled']) ? true : false);
										
					##Write the file	
					if(!$write = General::writeFile($file, (string)$task, Symphony::Configuration()->get('write_mode', 'file')))
						$this->pageAlert(__('Task could not be written to disk. Please check permissions on <code>/manifest/cron</code>.'), Alert::ERROR);

					##Write Successful, add record to the database
					else{
						
						if($file != MANIFEST . '/cron/' . $this->_context[0]){
						
							$sql = sprintf("DELETE FROM `tbl_cron` WHERE `name` = '%s' LIMIT 1", $this->_context[0]);
							Symphony::Database()->query($sql);
						
							General::deleteFile(MANIFEST . '/cron/' . $this->_context[0]);
						}
						
						$sql = sprintf("INSERT INTO `tbl_cron` VALUES ('%s', NULL, %d, NULL) ON DUPLICATE KEY UPDATE `enabled` = %2\$d", $task->filename, (int)$task->enabled);
						Symphony::Database()->query($sql);
						
						redirect(URL . '/symphony/extension/cron/edit/' . $filename . '/saved/');

					}
				}
			}
			elseif(@array_key_exists('delete', $_POST['action'])){
		
				$sql = sprintf("DELETE FROM `tbl_cron` WHERE `name` = '%s' LIMIT 1", $this->_context[0]);
				Symphony::Database()->query($sql);

			    General::deleteFile(MANIFEST . '/cron/' . $this->_context[0]);
				
		    	redirect(URL . '/symphony/extension/cron/');	
		  	}			
			
		}
	}
	

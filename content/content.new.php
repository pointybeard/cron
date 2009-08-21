<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionCronNew extends AdministrationPage{

		private $_driver;

		public function __construct(&$parent){
			parent::__construct($parent);
			$this->setPageType('form');
			$this->_driver = Administration::instance()->ExtensionManager->create('cron');
		}
		
		public function view(){			
			
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/cron/assets/styles.css', 'screen', 70);
			
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));			
			if($formHasErrors) $this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
		
			$this->setTitle('Symphony &ndash; Cron &ndash; New');
			$this->appendSubheading(__('Untitled'));
			
			if(!empty($_POST)) $fields = $_POST['fields'];
			else{
				$fields = array(
					'interval' => 60
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
				array('minute', ($fields['interval'] == 'minute'), 'minutes'),
				array('hour', ($fields['interval'] == 'hour'), 'hours'),
				array('day', ($fields['interval'] == 'day'), 'days'),
				array('week', ($fields['interval'] == 'week'), 'weeks')				
			);
			$select = Widget::Select('fields[interval-type]', $options, array('class' => 'inline'));
			
			$label->setValue(__('Run this task every %s %s', array($input->generate(false), $select->generate(false))));
			if(isset($this->_errors['interval'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['interval']));
			else $div->appendChild($label);

			$label = Widget::Label();
			$input = Widget::Input('fields[enabled]', 'yes', 'checkbox', (isset($fields['enabled']) ? array('checked' => 'checked') : NULL));
			$label->setValue(__('%s Enable this task', array($input->generate(false))));
			$div->appendChild($label);

			$p = new XMLElement('p', '&uarr; Unless a <strong>start date</strong> has been specified, this task will be executed once the current date plus the interval specified has passed.');
			$p->setAttribute('class', 'help');
			$div->appendChild($p);
						
			$this->Form->appendChild($div);
		
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', 'Create', 'submit', array('accesskey' => 's')));

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
					if(file_exists($file)){
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
						
						$sql = sprintf("INSERT INTO `tbl_cron` VALUES ('%s', NULL, %d, NULL)", $task->filename, (int)$task->enabled);
						Symphony::Database()->query($sql);
						
						redirect(URL . '/symphony/extension/cron/edit/' . $filename . '/created/');

					}
				}
			}
		}
	}
	

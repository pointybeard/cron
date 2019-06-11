<?php

use pointybeard\Symphony\Extensions\Cron;

class contentExtensionCronNew extends AdministrationPage
{
    public function __switchboard($type = 'view')
    {
        $this->_type = $type;
        if (!isset($this->_context[0]) || in_array(trim($this->_context[0]), ['', 'saved', 'completed'])) {
            $this->_function = 'index';
        } else {
            $this->_function = $this->_context[0];
        }
        parent::__switchboard($type);
    }

    public function __viewIndex()
    {
        $this->setPageType('form');
        $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(__("New"), __('Cron'), __('Symphony'))));
        $this->appendSubheading(__('Untitled'));

        $formHasErrors = (boolean) (is_array($this->_errors) && !empty($this->_errors));

        if ($formHasErrors) {
            $this->pageAlert(
                __('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
                Alert::ERROR
            );
        }

        // Essentials
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Essentials')));

        if (!empty($_POST)) {
            $fields = $_POST['fields'];
        } else {
            $fields = ['interval' => 60];
        }

        $label = Widget::Label('Name');
        $label->appendChild(Widget::Input('fields[name]', $fields['name']));
        $fieldset->appendChild((isset($this->_errors['name']) ? Widget::Error($label, $this->_errors['name']) : $label));

        $label = Widget::Label('Command');
        $label->appendChild(Widget::Input('fields[command]', $fields['command']));
        $fieldset->appendChild((isset($this->_errors['command']) ? Widget::Error($label, $this->_errors['command']) : $label));

        $p = new XMLElement('p', '&uarr; This is any command that can be executed from the command line.');
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $label = Widget::Label('Description <i>Optional</i>');
        $label->appendChild(Widget::Input('fields[description]', $fields['description']));
        $fieldset->appendChild((isset($this->_errors['description']) ? Widget::Error($label, $this->_errors['description']) : $label));

        $label = Widget::Label();
        $input = Widget::Input('fields[interval]', (string) max(1, $fields['interval']), null, array('size' => '6'));
        $options = [
            ['minute', ($fields['interval'] == 'minute'), 'minutes'],
            ['hour', ($fields['interval'] == 'hour'), 'hours'],
            ['day', ($fields['interval'] == 'day'), 'days'],
            ['week', ($fields['interval'] == 'week'), 'weeks'],
        ];
        $select = Widget::Select('fields[interval-type]', $options, ['class' => 'inline', 'style' => 'display: inline; width: auto;']);

        $label->setValue(__('Run this task every %s %s', [$input->generate(false), $select->generate(false)]));

        if (isset($this->_errors['interval'])) {
            $fieldset->appendChild(Widget::Error($label, $this->_errors['interval']));
        } else {
            $fieldset->appendChild($label);
        }

        $label = Widget::Label();
        $input = Widget::Input('fields[enabled]', 'yes', 'checkbox', (isset($fields['enabled']) ? array('checked' => 'checked') : null));
        $label->setValue(__('%s Enable this task', array($input->generate(false))));
        $fieldset->appendChild($label);

        $p = new XMLElement('p', '&uarr; Unless a <strong>start date</strong> has been specified, this task will be executed once the current date plus the interval specified has passed.');
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $this->Form->appendChild($fieldset);

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Timing')));

        $group = new XMLElement('div', null, array('class' => 'group'));

        $label = Widget::Label('Start Date <i>Optional</i>');
        $label->appendChild(Widget::Input('fields[start]', $fields['start']));
        $group->appendChild((isset($this->_errors['start']) ? Widget::Error($label, $this->_errors['start']) : $label));

        $label = Widget::Label('Finish Date <i>Optional</i>');
        $label->appendChild(Widget::Input('fields[finish]', $fields['finish']));
        $group->appendChild((isset($this->_errors['finish']) ? Widget::Error($label, $this->_errors['finish']) : $label));

        $fieldset->appendChild($group);

        $p = new XMLElement('p', 'This task will be not run until after the <strong>start date</strong>, and will cease to trigger beyond the <strong>finish date</strong>.');
        $p->setAttribute('class', 'help');
        $fieldset->appendChild($p);

        $this->Form->appendChild($fieldset);

        $div = new XMLElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Input('action[save]', 'Create', 'submit', array('accesskey' => 's')));

        $this->Form->appendChild($div);
    }

    public function action()
    {
        if (!array_key_exists('save', $_POST['action']) && !array_key_exists('done', $_POST['action'])) {
            return;
        }

        $fields = $_POST['fields'];

        $this->_errors = array();

        if (!isset($fields['name']) || strlen(trim($fields['name'])) == 0) {
            $this->_errors['name'] = 'Name is a required field.';
        } else {
            $filename = strtolower(Lang::createFilename($fields['name'].'.task'));
            $file = realpath(MANIFEST.'/cron').'/'.$filename;

            ##Duplicate
            if (file_exists($file)) {
                $this->_errors['name'] = __('A task with that name already exists. Please choose another.');
            }
        }

        if (!isset($fields['command']) || strlen(trim($fields['command'])) == 0) {
            $this->_errors['command'] = 'Command is a required field.';
        }

        if (!isset($fields['interval']) || strlen(trim($fields['interval'])) == 0) {
            $this->_errors['interval'] = 'Interval is a required field.';
        } elseif (!is_numeric($fields['interval']) || (int) $fields['interval'] == 0) {
            $this->_errors['interval'] = 'Interval must be a positive integer value.';
        }

        if (isset($fields['start']) && strlen(trim($fields['start'])) > 0) {
            $time = strtotime($fields['start']);

            $info = getdate($time);

            if ($time == false || $info == false || !checkdate($info['mon'], $info['mday'], $info['year'])) {
                $this->_errors['start'] = 'Start Date is invalid.';
            }
        }

        if (isset($fields['finish']) && strlen(trim($fields['finish'])) > 0) {
            $time = strtotime($fields['finish']);

            $info = getdate($time);

            if ($time == false || $info === false || !checkdate($info['mon'], $info['mday'], $info['year'])) {
                $this->_errors['finish'] = 'Finish Date is invalid.';
            } elseif (!isset($this->_errors['start']) && isset($fields['start']) && strlen(trim($fields['start'])) > 0) {
                if (strtotime($fields['finish']) <= strtotime($fields['start'])) {
                    $this->_errors['finish'] = 'Finish Date must occur <strong>after</strong> Start Date.';
                }
            }
        }

        if (empty($this->_errors)) {

            $task = (new Cron\Task)
                ->path($file)
                ->filename($filename)
                ->name($fields['name'])
                ->description($fields['description'])
                ->setInterval(
                    $fields['interval'],
                    $fields['interval-type']
                )
                ->command($fields['command'])
                ->enabled(
                    (isset($fields['enabled'])
                    ? Cron\Task::ENABLED
                    : Cron\Task::DISABLED)
                )
            ;

            if(strlen(trim($fields['start'])) > 0){
                $task->start(strtotime($fields['start']));
            }

            if(strlen(trim($fields['finish'])) > 0){
                $task->finish(strtotime($fields['finish']));
            }

            try {
                $task->save();

                redirect(sprintf(
                    "%sedit/%s/created/",
                    preg_replace('/new\/$/', '', Administration::instance()->getCurrentPageURL()),
                    $filename
                ));
            } catch (\Exception $e) {
                $this->pageAlert($e->getMessage());
            }
        }
    }
}

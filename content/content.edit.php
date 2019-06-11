<?php
use pointybeard\Symphony\Extensions\Cron;

class contentExtensionCronEdit extends AdministrationPage
{
    public function View()
    {
        if (!file_exists(realpath(MANIFEST.'/cron').'/'.$this->_context[0])) {
            throw new SymphonyErrorPage(
                'The cron task <code>'.$this->_context[0].'</code> could not be found.',
                'Task Not Found'
            );
        }
        $task = Cron\Task::load(
            realpath(MANIFEST.'/cron').'/'.$this->_context[0]
        );

        $formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

        if ($formHasErrors) {
            $this->pageAlert(
                __('An error occurred while processing this form. See below for details.'),
                Alert::ERROR
            );

            // These alerts are only valid if the form doesn't have errors
        } elseif (isset($this->_context[1])) {
            $time = Widget::Time();

            switch ($this->_context[1]) {
                case 'saved':
                    $message = __('Cron task updated at %s.', array($time->generate()));
                    break;

                case 'created':
                    $message = __('Cron task created at %s.', array($time->generate()));
            }

            $this->pageAlert($message, Alert::SUCCESS);
        }

        $this->setPageType('form');
        $this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [(string)$task->name, __('Cron'), __('Symphony')]));
        $this->appendSubheading((string)$task->name);

        if (!empty($_POST)) {
            $fields = $_POST['fields'];
        } else {
            $fields = [
                'name' => General::sanitize((string)$task->name),
                'command' => General::sanitize((string)$task->command),
                'description' => General::sanitize((string)$task->description),
                'interval' => (int)$task->interval->value->duration->value,
                'interval-type' => (string)$task->interval->value->type,
                'start' => (!is_null($task->start->value) ? DateTimeObj::get('Y-m-d H:i:s', (string)$task->start) : null),
                'finish' => (!is_null($task->finish->value) ? DateTimeObj::get('Y-m-d H:i:s', (string)$task->finish) : null),
            ];

            if ($task->enabled->value == Cron\Task::ENABLED) {
                $fields['enabled'] = 'yes';
            }
        }

        // Essentials
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Essentials')));

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
            ['minute', ($fields['interval-type'] == 'minute'), 'minutes'],
            ['hour', ($fields['interval-type'] == 'hour'), 'hours'],
            ['day', ($fields['interval-type'] == 'day'), 'days'],
            ['week', ($fields['interval-type'] == 'week'), 'weeks'],
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
        $div->appendChild(Widget::Input('action[save]', 'Save', 'submit', ['accesskey' => 's']));

        $button = new XMLElement('button', __('Delete'));
        $button->setAttributeArray([
            'name' => 'action[delete]',
            'class' => 'confirm delete',
            'title' => 'Delete this task',
        ]);
        $div->appendChild($button);

        $this->Form->appendChild($div);
    }

    public function action()
    {
        if (array_key_exists('save', $_POST['action']) || array_key_exists('done', $_POST['action'])) {
            $fields = $_POST['fields'];

            $this->_errors = array();

            if (!isset($fields['name']) || strlen(trim($fields['name'])) == 0) {
                $this->_errors['name'] = 'Name is a required field.';
            } else {
                $filename = strtolower(Lang::createFilename($fields['name'].'.task'));
                $file = realpath(MANIFEST.'/cron').'/'.$filename;

                ##Duplicate
                if ($file != realpath(MANIFEST.'/cron').'/'.$this->_context[0] && file_exists($file)) {
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
                try {

                    $task = Cron\Task::load(
                        realpath(MANIFEST.'/cron') . '/' . $this->_context[0]
                    );

                    $task
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

                    $task->save();

                    $oldFile = realpath(MANIFEST.'/cron').'/'.$this->_context[0];

                    if ($file != $oldFile) {
                        Symphony::Database()->query(sprintf(
                            "DELETE FROM `tbl_cron` WHERE `name` = '%s' LIMIT 1",
                            $this->_context[0]
                        ));
                        General::deleteFile($oldFile);
                    }

                    redirect(sprintf(
                        "%s/%s/saved/",
                        preg_replace('/cron\/edit.+/', 'cron/edit', Administration::instance()->getCurrentPageURL()),
                        $filename
                    ));

                } catch (\Exception $e) {
                    $this->pageAlert($e->getMessage());
                }
            }
        } elseif (@array_key_exists('delete', $_POST['action'])) {

            Cron\Task::load(
                realpath(MANIFEST.'/cron').'/'.$this->_context[0]
            )->delete();

            redirect(preg_replace('/cron\/edit.+/', 'cron/', Administration::instance()->getCurrentPageURL()));

            return;
        }
    }
}

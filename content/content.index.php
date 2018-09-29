<?php

use Cron\Lib;

class contentExtensionCronIndex extends AdministrationPage
{
    private static function __minutesToHumanReadable($minutes)
    {
        $string = null;

        // WEEKS
        if ($minutes >= (7 * 24 * 60)) {
            $value = floor((float) $minutes * (1 / (7 * 24 * 60)));
            $string = $value.' week'.($value == 1 ? null : 's');

            $minutes -= ($value * 60 * 24 * 7);
        }

        // DAYS
        if ($minutes >= (24 * 60)) {
            $value = floor((float) $minutes * (1 / (24 * 60)));
            $string .= ' '.$value.' day'.($value == 1 ? null : 's');

            $minutes -= ($value * 60 * 24);
        }

        // HOURS
        if ($minutes >= 60) {
            $value = floor((float) $minutes * (1 / 60));
            $string .= ' '.$value.' hour'.($value == 1 ? null : 's');

            $minutes -= ($value * 60);
        }

        $string .= ' '.$minutes.' minute'.($minutes == 1 ? null : 's');

        return trim($string);
    }

    public function view()
    {
        $this->setPageType('table');
        $this->setTitle('Symphony &ndash; Cron');

        $this->appendSubheading('Cron Tasks', [
            Widget::Anchor(
                __('Create New'),
                Administration::instance()->getCurrentPageURL().'new/',
                __('Create a cron task'),
                'create button',
                null,
                ['accesskey' => 'c']
            )
        ]);

        Extension_Cron::init();

        $tasks = Extension_Cron::getSortedTaskList(Extension_Cron::SORT_ASCENDING);

        $aTableHead = [
            ['Name', 'col'],
            ['Description', 'col'],
            ['Enabled', 'col'],
            ['Last Executed', 'col'],
            ['Next Execution', 'col'],
            ['Last Output', 'col'],
        ];

        $aTableBody = [];

        if (count($tasks) == 0) {
            $aTableBody = [
                Widget::TableRow([
                    Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead)),
                ], 'odd'),
            ];
        } else {
            $ii = -1;
            foreach ($tasks as $task) {
                $ii++;
                $td1 = Widget::TableData(Widget::Anchor(
                    (string)$task->name,
                    sprintf('%sedit/%s/', Administration::instance()->getCurrentPageURL(), (string)$task->filename)
                ));
                $td1->appendChild(Widget::Label(__('Select Task %s', [(string)$task->filename]), null, 'accessible', null, array(
                    'for' => 'task-'.$ii,
                )));
                $td1->appendChild(Widget::Input('items['.(string)$task->filename.']', 'on', 'checkbox', array(
                    'id' => 'task-'.$ii,
                )));

                $td2 = Widget::TableData(strlen(trim((string)$task->description)) <= 0 ? 'None' : (string)$task->description);
                if (strlen(trim((string)$task->description)) <= 0) {
                    $td2->setAttribute('class', 'inactive');
                }

                $td3 = Widget::TableData(($task->enabledReal() == true ? 'Yes' : 'No'));

                $td4 = Widget::TableData(
                    (!is_null($task->getLastExecutionTimestamp()) ? DateTimeObj::get(__SYM_DATETIME_FORMAT__, $task->getLastExecutionTimestamp()) : 'Unknown')
                );
                if (is_null($task->getLastExecutionTimestamp())) {
                    $td4->setAttribute('class', 'inactive');
                }

                $nextExecutionTime = (!is_null($task->nextExecution()) ? self::__minutesToHumanReadable(ceil($task->nextExecution() * (1/60))) : 'None');

                if((string)$task->force == Lib\CronTask::FORCE_EXECUTE_YES) {
                    $nextExecutionTime .= " (forced)";
                }

                $td5 = Widget::TableData(
                    $nextExecutionTime
                );
                if (is_null($task->nextExecution()) || $task->enabledReal() == false) {
                    $td5->setAttribute('class', 'inactive');
                }

                if (is_null($task->getLog())) {
                    $td6 = Widget::TableData('None', 'inactive');
                } else {
                    $td6 = Widget::TableData(Widget::Anchor('view', sprintf('%slog/%s/', Administration::instance()->getCurrentPageURL(), (string)$task->filename)));
                }

                $aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4, $td5, $td6));
            }
        }

        $table = Widget::Table(
            Widget::TableHead($aTableHead),
            null,
            Widget::TableBody($aTableBody),
            'selectable',
            null,
            array('role' => 'directory', 'aria-labelledby' => 'symphony-subheading', 'data-interactive' => 'data-interactive')
        );
        $this->Form->appendChild($table);

        $tableActions = new XMLElement('div');
        $tableActions->setAttribute('class', 'actions');

        $options = [
            [null, false, __('With Selected...')],
            ['delete', false, __('Delete'), 'confirm', null, ['data-message' => __('Are you sure you want to delete the selected tasks?')]],
            ['force', false, __('Force Execute'), 'confirm', null, ['data-message' => __('Selected tasks will be forced to run during the next Cron Run Tasks event, ignoring their Next Execution time. Are you sure?')]],
            ['duplicate', false, __('Duplicate')],
            [
                "label" => "Enabled",
                "options" => [
                    ['enable', false, 'Yes'],
                    ['disable', false, 'No', 'confirm', null, ['data-message' => __('Are you sure you want to disable the selected tasks?')]]
                ]
            ],
        ];

        $tableActions->appendChild(Widget::Apply($options));
        $this->Form->appendChild($tableActions);
    }

    public function action()
    {
        $checked = (
            isset($_POST['items']) && is_array($_POST['items'])
                ? array_keys($_POST['items'])
                : []
        );

        $action = $_POST['with-selected'];

        if (!empty($checked)) {
            foreach ($checked as $taskFilename) {
                $task = Lib\CronTask::load(
                    realpath(MANIFEST.'/cron').'/'.$taskFilename
                );

                try{
                    switch($action) {
                        case 'enable':
                            $task
                                ->enabled(Lib\CronTask::ENABLED)
                                ->save()
                            ;
                            break;

                        case 'disable':
                            $task
                                ->enabled(Lib\CronTask::DISABLED)
                                ->force(Lib\CronTask::FORCE_EXECUTE_NO)
                                ->save()
                            ;
                            break;

                        case 'delete':
                            $task->delete();
                            break;

                        case 'force':
                            $task
                                ->force(Lib\CronTask::FORCE_EXECUTE_YES)
                                ->enabled(Lib\CronTask::ENABLED)
                                ->save()
                            ;
                            break;

                        case 'duplicate':
                            $path = preg_replace("@\.task$@", "-copy.task", $task->path);
                            $task
                                ->name($task->name() . " Copy")
                                ->path($path)
                                ->filename(basename($path))
                                ->save(Lib\CronTask::SAVE_MODE_FILE_ONLY)
                            ;
                            //print "<pre>"; print_r($task); die;
                            break;
                    }

                } catch (Exception $ex) {
                    // Failed to save or delete
                    $this->pageAlert(__('There was a problem completing the request action on task "%s": %s', array(
                            $task->name, $ex->getMessage()
                        )),
                        Alert::ERROR
                    );
                    return;
                }
            }
        }

        redirect(Administration::instance()->getCurrentPageURL());
    }
}

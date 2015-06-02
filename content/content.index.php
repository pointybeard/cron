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

            $iterator = new Lib\CronTaskIterator(realpath(MANIFEST.'/cron'), Symphony::Database());

            $aTableHead = [
                ['Name', 'col'],
                ['Description', 'col'],
                ['Enabled', 'col'],
                ['Last Executed', 'col'],
                ['Next Execution', 'col'],
                ['Last Output', 'col'],
            ];

            $aTableBody = [];

            if ($iterator->count() == 0) {
                $aTableBody = [
                    Widget::TableRow([
                        Widget::TableData(__('None found.'), 'inactive', null, count($aTableHead)),
                    ], 'odd'),
                ];
            } else {
                foreach ($iterator as $ii => $task) {
                    $td1 = Widget::TableData(Widget::Anchor(
                        $task->name,
                        sprintf('%sedit/%s/', Administration::instance()->getCurrentPageURL(), $task->filename)
                    ));
                    $td1->appendChild(Widget::Label(__('Select Task %s', [$task->filename]), null, 'accessible', null, array(
                        'for' => 'task-'.$ii,
                    )));
                    $td1->appendChild(Widget::Input('items['.$task->filename.']', 'on', 'checkbox', array(
                        'id' => 'task-'.$ii,
                    )));

                    $td2 = Widget::TableData((is_null($task->description) ? 'None' : $task->description));
                    if (is_null($task->description)) {
                        $td2->setAttribute('class', 'inactive');
                    }

                    $td3 = Widget::TableData(($task->enabledReal() == true ? 'Yes' : 'No'));
                    if ($task->enabled == false) {
                        $td3->setAttribute('class', 'inactive');
                    }

                    $td4 = Widget::TableData(
                        (!is_null($task->getLastExecutionTimestamp()) ? DateTimeObj::get(__SYM_DATETIME_FORMAT__, $task->getLastExecutionTimestamp()) : 'Unknown')
                    );
                    if (is_null($task->getLastExecutionTimestamp())) {
                        $td4->setAttribute('class', 'inactive');
                    }

                    $td5 = Widget::TableData(
                        (!is_null($task->nextExecution()) ? self::__minutesToHumanReadable(ceil($task->nextExecution() * (1/60))) : 'Unknown')
                    );
                    if (is_null($task->nextExecution()) || $task->enabledReal() == false) {
                        $td5->setAttribute('class', 'inactive');
                    }

                    if (is_null($task->getLog())) {
                        $td6 = Widget::TableData('None', 'inactive');
                    } else {
                        $td6 = Widget::TableData(Widget::Anchor('view', sprintf('%slog/%s/', Administration::instance()->getCurrentPageURL(), $task->filename)));
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
                ['enable', false, __('Enable')],
                ['disable', false, __('Disable')],
                ['delete', false, __('Delete'), 'confirm', null, ['data-message' => __('Are you sure you want to delete the selected tasks?')]],
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

            // Sanity check! Make sure the selected action is valid
            if (empty($checked) || !in_array($action, ['delete', 'enable', 'disable'])) {
                return;
            }

            foreach ($checked as $taskFilename) {
                $task = (new Lib\CronTask(Symphony::Database()))
                    ->load(realpath(MANIFEST.'/cron').'/'.$taskFilename);
                $task->$action();
            }

            redirect(Administration::instance()->getCurrentPageURL());
        }
    }

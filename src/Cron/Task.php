<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Cron;

use SymphonyPDO;
use pointybeard\PropertyBag;

class Task extends PropertyBag\Lib\PropertyBag
{
    const FORCE_EXECUTE_YES = 'yes';
    const FORCE_EXECUTE_NO = 'no';

    const DURATION_MINUTE = 'minute';
    const DURATION_DAY = 'day';
    const DURATION_HOUR = 'hour';
    const DURATION_WEEK = 'week';

    const ENABLED = 1;
    const DISABLED = 0;

    const SAVE_MODE_DATABASE_ONLY = 0;
    const SAVE_MODE_FILE_ONLY = 1;
    const SAVE_MODE_BOTH = 2;

    public function __construct()
    {
        // IMPORTANT: Must initialise the entire propertybag object
        // otherwise using the __get and __set methods in this class
        // with fail.
        $this
            ->path(null)
            ->filename(null)
            ->name(null)
            ->description(null)
            ->interval(
                (new PropertyBag\Lib\PropertyBag())
                    ->type(self::DURATION_MINUTE)
                    ->duration(null)
            )
            ->command(null)
            ->enabled(self::DISABLED)
            ->force(self::FORCE_EXECUTE_NO)
            ->lastExecuted(null)
            ->lastOutput(null)
            ->start(null)
            ->finish(null)
        ;
    }

    public static function load($path)
    {
        $path = realpath($path);

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exceptions\LoadingTaskFailedException($path, 'File does not exist or is not readable.');
        }

        $data = json_decode(file_get_contents($path));
        if (null === $data) {
            throw new Exceptions\LoadingTaskFailedException($path, 'Task is invalid JSON and cannot be read.');
        }

        $task = (new self())
            ->path($path)
            ->filename(basename($path))
            ->name($data->name)
            ->interval(
                (new PropertyBag\Lib\PropertyBag())
                    ->type($data->interval->type)
                    ->duration($data->interval->duration)
            )
            ->command($data->command)
        ;

        if (isset($data->description)) {
            $task->description($data->description);
        }

        if (isset($data->start)) {
            $task->start($data->start);
        }

        if (isset($data->finish)) {
            $task->finish($data->finish);
        }

        $query = SymphonyPDO\Loader::instance()->prepare(
            'SELECT * FROM `tbl_cron` WHERE `name` = :name LIMIT 1'
        );

        $taskName = (string) $task->filename; //PDO complains if we do this directly below
        $query->bindParam(':name', $taskName, \PDO::PARAM_STR);

        $query->execute();
        $result = $query->fetchObject();

        if (false !== $result) {
            $task
                ->lastExecuted($result->last_executed)
                ->lastOutput($result->last_output)
                ->force($result->force_execution)
                ->enabled('yes' == (string) $result->enabled ? self::ENABLED : self::DISABLED)
            ;
        }

        return $task;
    }

    public function getLog()
    {
        return $this->lastOutput->value;
    }

    public function getLastExecutionTimestamp()
    {
        return $this->lastExecuted->value;
    }

    public function run()
    {
        if (true !== $this->enabledReal() || 0 != $this->nextExecution()) {
            return;
        }

        $this
            ->lastOutput(shell_exec((string) $this->command))
            ->lastExecuted(time())
            ->force(self::FORCE_EXECUTE_NO)
            ->save(self::SAVE_MODE_DATABASE_ONLY)
        ;
    }

    public function enabledReal()
    {
        return
            self::ENABLED == $this->enabled->value &&
            null !== $this->nextExecution()
        ;
    }

    public function intervalReal()
    {
        $value = (int) $this->interval->value->duration->value;

        switch ((string) $this->interval->value->type) {
            case self::DURATION_WEEK:
                $value *= 7;

                // no break
            case self::DURATION_DAY:
                $value *= 24;

                // no break
            case self::DURATION_HOUR:
                $value *= 60;
                break;
        }

        return $value;
    }

    public function setInterval($value, $type = self::DURATION_MINUTE)
    {
        $this->interval->value->type = $type;
        $this->interval->value->duration = $value;

        return $this;
    }

    public function nextExecution()
    {
        $nextExecution = null;

        if (self::ENABLED == $this->enabled->value) {
            if (self::FORCE_EXECUTE_YES == $this->force->value) {
                $nextExecution = 0;
            } elseif (null !== $this->finish->value && time() >= $this->finish->value) {
                $nextExecution = null;
            } elseif (null !== $this->start->value && $this->start->value > time()) {
                $nextExecution = $this->start->value - time();
            } elseif (null === $this->lastExecuted->value) {
                $nextExecution = 0;
            } else {
                $nextExecution = max(0, ($this->lastExecuted->value + ($this->intervalReal() * 60)) - time());
            }
        }

        return $nextExecution;
    }

    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    public function delete()
    {
        if (!\General::deleteFile((string) $this->path)) {
            throw new Exceptions\CronException('Task `'.(string) $this->path.'` could not be deleted');
        }
        SymphonyPDO\Loader::instance()->delete(
            'tbl_cron',
            sprintf("`name` = '%s' LIMIT 1", $this->filename)
        );
    }

    public function save($saveMode = self::SAVE_MODE_BOTH, \Closure $writeFunction = null)
    {
        // Create a default write function in case one is not provided
        if (null === $writeFunction) {
            $writeFunction = function ($file, $data) {
                return @file_put_contents($file, $data);
            };
        }

        $data = $this->toArray();

        $query = SymphonyPDO\Loader::instance()->prepare(
            'INSERT INTO `tbl_cron`
            (
                `name`,
                `last_executed`,
                `enabled`,
                `last_output`,
                `force_execution`

            ) VALUES (
                :filename,
                :lastExecuted,
                :enabled,
                :lastOutput,
                :forceExecution
            )

            ON DUPLICATE KEY UPDATE
                `last_executed` = :lastExecuted,
                `last_output` = :lastOutput,
                `enabled` = :enabled,
                `force_execution` = :forceExecution'
        );

        $enabled = (self::ENABLED == $data['enabled'] ? 'yes' : 'no');

        $query->bindParam(':filename', $data['filename'], \PDO::PARAM_STR);
        $query->bindParam(':lastExecuted', $data['lastExecuted'], \PDO::PARAM_STR);
        $query->bindParam(':lastOutput', $data['lastOutput'], \PDO::PARAM_STR);
        $query->bindParam(':enabled', $enabled, \PDO::PARAM_STR);
        $query->bindParam(':forceExecution', $data['force'], \PDO::PARAM_STR);

        // This data should not be going into the file since it is
        // specific to this installation
        unset($data['path']);
        unset($data['filename']);
        unset($data['lastExecuted']);
        unset($data['lastOutput']);
        unset($data['enabled']);
        unset($data['force']);

        if (self::SAVE_MODE_DATABASE_ONLY != $saveMode) {
            if (false === $writeFunction($this->path, json_encode($data, JSON_PRETTY_PRINT))) {
                throw new Exceptions\WritingTaskFailedException($this->path);
            }
        }

        // File written. Now update the database
        if (self::SAVE_MODE_FILE_ONLY != $saveMode) {
            $result = $query->execute();
        }

        return true;
    }
}

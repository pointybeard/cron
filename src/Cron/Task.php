<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Cron;

use SymphonyPDO;
use pointybeard\PropertyBag;
use pointybeard\Helpers\Functions\Json;

class Task extends PropertyBag\Lib\PropertyBag
{
    public const FORCE_EXECUTE_YES = 'yes';
    public const FORCE_EXECUTE_NO = 'no';

    public const DURATION_MINUTE = 'minute';
    public const DURATION_DAY = 'day';
    public const DURATION_HOUR = 'hour';
    public const DURATION_WEEK = 'week';

    public const ENABLED = 1;
    public const DISABLED = 0;

    public const SAVE_MODE_DATABASE_ONLY = 0x0001;
    public const SAVE_MODE_FILE_ONLY = 0x0002;
    public const SAVE_MODE_BOTH = 0x0004;

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

    public static function load(string $path): self
    {
        $pathAbsolute = realpath($path);

        if (false == $pathAbsolute || !is_readable($path)) {
            throw new Exceptions\LoadingFailedException($path, 'File does not exist or is not readable.');
        }

        try {
            $data = Json\json_decode_file($path);
        } catch (\JsonException $ex) {
            throw new Exceptions\LoadingFailedException($path, 'Task is not a valid JSON document', 0, $ex);
        }

        $task = (new self())
            ->path($pathAbsolute)
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

    public function run(bool $force = false): void
    {
        if (false == $force && (true !== $this->enabledReal() || 0 != $this->nextExecution())) {
            throw new Exceptions\FailedToRunException((string) $this->path, 'Not enabled or due to be run yet.');
        }

        $outputLastLine = exec(
            // Add '2>&1' to the command to make sure errors are redirected
            // to stdout and therefore captured by $output
            sprintf('%s 2>&1', (string) $this->command),
            $output,
            $return_var
        );

        $this
            ->lastOutput(implode(PHP_EOL, $output))
            ->lastExecuted(time())
            ->force(self::FORCE_EXECUTE_NO)
            ->save(self::SAVE_MODE_DATABASE_ONLY)
        ;

        if (0 !== $return_var) {
            throw new Exceptions\FailedToRunException((string) $this->path, $outputLastLine);
        }
    }

    public function enabledReal(): bool
    {
        return
            self::ENABLED == $this->enabled->value &&
            null !== $this->nextExecution()
        ;
    }

    public function intervalReal(): int
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

    public function setInterval($value, string $type = self::DURATION_MINUTE): self
    {
        $this->interval->value->type = $type;
        $this->interval->value->duration = $value;

        return $this;
    }

    public function nextExecution(): ?int
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

    public function delete(): void
    {
        if (!\General::deleteFile((string) $this->path)) {
            throw new Exceptions\CronException('Task `'.(string) $this->path.'` could not be deleted');
        }
        SymphonyPDO\Loader::instance()->delete(
            'tbl_cron',
            sprintf("`name` = '%s' LIMIT 1", $this->filename)
        );
    }

    public function save(int $saveMode = self::SAVE_MODE_BOTH, \Closure $writeFunction = null): bool
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
            if (false === $writeFunction((string) $this->path, json_encode($data, JSON_PRETTY_PRINT))) {
                throw new Exceptions\WritingFailedException((string) $this->path);
            }
        }

        // File written. Now update the database
        if (self::SAVE_MODE_FILE_ONLY != $saveMode) {
            $result = $query->execute();
        }

        return true;
    }
}

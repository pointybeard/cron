<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Cron\Exceptions;

class FailedToRunException extends CronException
{
    public function __construct(string $path, string $message, int $code = 0, \Exception $previous = null)
    {
        parent::__construct('Task '.basename($path).' failed to run. Returned: '.$message, $code, $previous);
    }
}

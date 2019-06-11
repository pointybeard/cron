<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Cron\Exceptions;

class LoadingTaskFailedException extends CronException
{
    public function __construct($path, $message, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf(
            "Unable to load task '%s' - %s",
            $path,
            $message
        ), $code, $previous);
    }
}

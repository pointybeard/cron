<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Cron\Exceptions;

class WritingTaskFailedException extends CronException
{
    public function __construct($path, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf(
            "Unable to write task '%s'",
            $path
        ), $code, $previous);
    }
}

<?php

namespace pointybeard\Symphony\Extensions\Cron\Exceptions;

class CronException extends \Exception
{
    public function __construct($path, $message, $code = 0, \Exception $previous = null) {
        parent::__construct(sprintf(
            "Unable to load task '%s' - %s", $path, $message
        ), $code, $previous);
    }
}

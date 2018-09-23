<?php

namespace Cron\Lib\Exceptions;

class WritingTaskFailedException extends \Exception
{
    public function __construct($path, $code = 0, \Exception $previous = null) {
        parent::__construct(sprintf(
            "Unable to write task '%s'", $path
        ), $code, $previous);
    }
}

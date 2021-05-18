<?php

declare(strict_types=1);

/*
 * This file is part of the "Cron Tasks Extension for Symphony CMS" repository.
 *
 * Copyright 2009-2018 Alannah Kearney, Allen Chang
 * Copyright 2019-2021 Alannah Kearney
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Cron\Exceptions;

class LoadingFailedException extends CronException
{
    public function __construct(string $path, string $message, int $code = 0, \Exception $previous = null)
    {
        parent::__construct('Task '.basename($path).' could not be loaded. Returned: '.$message, $code, $previous);
    }
}

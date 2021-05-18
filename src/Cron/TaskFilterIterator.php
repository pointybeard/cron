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

namespace pointybeard\Symphony\Extensions\Cron;

class TaskFilterIterator extends \FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new \DirectoryIterator($path));
    }

    public function accept(): bool
    {
        $current = $this->current();

        return
            (
                $current->isDot() ||
                $current->isDir() ||
                '.' == substr($current->getFilename(), 0, 1)
            ) ? false : true
        ;
    }
}

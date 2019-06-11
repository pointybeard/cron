<?php

declare(strict_types=1);

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

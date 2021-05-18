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

final class TaskIterator implements \Iterator, \Countable
{
    private $iterator = null;

    private $count = null;

    public function __construct(string $directory)
    {
        $this->iterator = new TaskFilterIterator($directory);
    }

    public function current(): Task
    {
        return Task::load(
            $this->iterator->current()->getPathname()
        );
    }

    public function innerIterator(): TaskFilterIterator
    {
        return $this->iterator;
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function position()
    {
        throw new Exceptions\CronException('TaskIterator::position() cannot be called.');
    }

    public function length()
    {
        throw new Exceptions\CronException('TaskIterator::length() cannot be called.');
    }

    public function count(): int
    {
        if (null === $this->count && $this->iterator instanceof TaskFilterIterator) {
            $this->count = iterator_count($this->iterator);
            $this->iterator->rewind();
        }

        return $this->count;
    }
}

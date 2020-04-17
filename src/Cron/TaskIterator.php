<?php

declare(strict_types=1);

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

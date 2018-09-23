<?php
namespace Cron\Lib;

final class CronTaskIterator implements \Iterator, \Countable
{
    private $iterator = null;
    private $count = null;

    public function __construct($directory)
    {
        $this->iterator = new CronTaskFilterIterator($directory);
    }

    public function current()
    {
        return CronTask::load(
            $this->iterator->current()->getPathname()
        );
    }

    public function innerIterator()
    {
        return $this->iterator;
    }

    public function next()
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

    public function rewind()
    {
        $this->iterator->rewind();
    }

    public function position()
    {
        throw new \Exception('CronTaskIterator::position() cannot be called.');
    }

    public function length()
    {
        throw new \Exception('CronTaskIterator::length() cannot be called.');
    }

    public function count()
    {
        if (is_null($this->count) && $this->iterator instanceof CronTaskFilterIterator) {
            $this->count = iterator_count($this->iterator);
            $this->iterator->rewind();
        }

        return $this->count;
    }
}

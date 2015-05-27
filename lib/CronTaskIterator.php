<?php
namespace Cron\Lib;

final class CronTaskIterator implements \Iterator, \Countable
{
    private $_iterator = null;
    private $count = null;
    private $db = null;
    
    public function __construct($directory, $db)
    {
        $this->db = $db;
        $this->_iterator = new CronTaskFilterIterator($directory);
    }

    public function current()
    {
        $this->_current = $this->_iterator->current();

        return (new CronTask($this->db))->load($this->_current->getPathname());
    }

    public function innerIterator()
    {
        return $this->_iterator;
    }

    public function next()
    {
        $this->_iterator->next();
    }

    public function key()
    {
        return $this->_iterator->key();
    }

    public function valid()
    {
        return $this->_iterator->valid();
    }

    public function rewind()
    {
        $this->_iterator->rewind();
    }

    public function position()
    {
        throw new Exception('CronTaskIterator::position() cannot be called.');
    }

    public function length()
    {
        throw new Exception('CronTaskIterator::length() cannot be called.');
    }
    
    public function count()
    {
        if(is_null($this->count) && $this->_iterator instanceof CronTaskFilterIterator){
            $this->count = iterator_count($this->_iterator);
            $this->_iterator->rewind();
        }
        return $this->count;
    }
}

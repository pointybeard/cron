<?php
namespace Cron\Lib;

class CronTaskFilterIterator extends \FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new \DirectoryIterator($path));
    }

    public function accept()
    {
        $current = $this->current();
        return ($current->isDot() || $current->isDir() || substr($current->getFilename(), 0, 1) == '.' ? false : true);
    }
}

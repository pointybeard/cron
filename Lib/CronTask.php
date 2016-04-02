<?php
namespace Cron\Lib;

final class CronTask
{
    private $db;

    private $_properties = [
        'path' => NULL,
        'filename' => NULL,
        'name' => NULL,
        'description' => NULL,
        'interval' => NULL,
        'interval-type' => 'minute',
        'command' => NULL,
        'enabled' => false,
        'last_executed' => NULL,
        'last_output' => NULL,
        'start' => NULL,
        'finish' => NULL,
    ];

    public function load($path)
    {
        if (!file_exists($path)) {
            throw new \Exception('Task could not be written to `'.$path.'`');
        }

        $this->path = $path;
        $doc = new \SimpleXMLElement($this->path, null, true);

        $this->filename = basename($this->path);
        $this->name = (string) $doc->name;
        if (isset($doc->description) && strlen($doc->description) > 0) {
            $this->description = (string) $doc->description;
        }
        if (isset($doc->start) && !is_null($doc->start)) {
            $this->start = strtotime($doc->start);
        }
        if (isset($doc->finish) && !is_null($doc->finish)) {
            $this->finish = strtotime($doc->finish);
        }

        $this->setInterval((int) $doc->interval, (isset($doc->interval->attributes()->type) ? (string) $doc->interval->attributes()->type : 'minute'));

        $this->command = (string) $doc->command;

        $this->enabled = false;

        $this->last_executed = $this->last_output = null;
        $this->enabled = false;

        $row = $this->db->fetchRow(0, sprintf(
            "SELECT * FROM `tbl_cron` WHERE `name` = '%s' LIMIT 1",
            $this->filename
        ));

        if (is_array($row) && !empty($row)) {
            $this->last_executed = (!is_null($row['last_executed']) ? strtotime($row['last_executed']) : null);
            $this->last_output = $row['last_output'];
            $this->enabled = (bool) $row['enabled'];
        }

        return $this;
    }

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getLog()
    {
        return $this->last_output;
    }

    public function getLastExecutionTimestamp()
    {
        return $this->last_executed;
    }

    public function run()
    {
        if ($this->enabledReal() !== true || $this->nextExecution() != 0) {
            return;
        }

        $this->last_output = shell_exec($this->command);
        $this->last_executed = time();

        $sql = sprintf(
                "UPDATE `tbl_cron` SET `last_output` = '%s', `last_executed` = '%s' WHERE `name` = '%s' LIMIT 1",
                $this->db->cleanValue($this->last_output),
                \DateTimeObj::get('Y-m-d H:i:s', $this->last_executed),
                $this->filename
            );

        $this->db->query($sql);
    }

    public function enabledReal()
    {
        return ($this->enabled == true && !is_null($this->nextExecution()));
    }

    public function intervalReal()
    {
        $value = $this->interval;

        switch ($this->{'interval-type'}) {

                case 'week':
                    $value *= 7;

                case 'day':
                    $value *= 24;

                case 'hour':
                    $value *= 60;
                    break;

            }

        return $value;
    }

    public function setInterval($value, $type = 'minute')
    {
        $this->{'interval-type'} = $type;
        $this->interval = $value;
    }

    public function nextExecution()
    {
        $next_execution = null;

        if ($this->enabled == true) {
            if (!is_null($this->finish) && time() >= $this->finish) {
                $next_execution = null;
            } elseif (!is_null($this->start) && $this->start > time()) {
                $next_execution = $this->start - time();
            } elseif (is_null($this->last_executed)) {
                $next_execution = 0;
            } else {
                $next_execution = max(0, ($this->last_executed + ($this->intervalReal() * 60)) - time());
            }
        }

        return $next_execution;
    }

    public function __toString()
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $doc->preserveWhitespace = false;

        $root = $doc->createElement('task');
        $doc->appendChild($root);

        $root->appendChild($doc->createElement('command', $this->command));
        $root->appendChild($doc->createElement('name', $this->name));

        if (!is_null($this->description)) {
            $root->appendChild($doc->createElement('description', $this->description));
        }

        if (!is_null($this->start)) {
            $root->appendChild($doc->createElement('start', \DateTimeObj::get('Y-m-d H:i:s', $this->start)));
        }
        if (!is_null($this->finish)) {
            $root->appendChild($doc->createElement('finish', \DateTimeObj::get('Y-m-d H:i:s', $this->finish)));
        }

        $interval = $doc->createElement('interval', $this->interval);
        $interval->setAttribute('type', $this->{'interval-type'});
        $root->appendChild($interval);

        return $doc->saveXML();
    }

    public function __set($name, $value)
    {
        $this->_properties[$name] = $value;
    }

    public function __get($name)
    {
        return $this->_properties[$name];
    }

    public function enable()
    {
        return $this->db->query(sprintf(
            "INSERT INTO `tbl_cron` VALUES ('%s', NULL, 1, NULL) ON DUPLICATE KEY UPDATE `enabled` = 1",
            $this->db->cleanValue($this->filename)
        ));
    }

    public function disable()
    {
        return $this->db->query(sprintf(
            "INSERT INTO `tbl_cron` VALUES ('%s', NULL, 0, NULL) ON DUPLICATE KEY UPDATE `enabled` = 0",
            $this->db->cleanValue($this->filename)
        ));
    }

    public function delete()
    {
        if (!\General::deleteFile($this->path)) {
            throw new \Exception('Task `'.$this->path.'` could not be deleted');
        }

        return $this->db->query(sprintf(
            "DELETE FROM `tbl_cron` WHERE `name` = '%s' LIMIT 1",
            $this->db->cleanValue($this->filename)
        ));
    }

    public function save(\Closure $fileWriteFunc)
    {
        if (!$fileWriteFunc($this->path, (string) $this)) {
            throw new \Exception('Task could not be written to `'.$this->path.'`');
        }

        return $this->db->query(sprintf(
            "INSERT INTO `tbl_cron` VALUES ('%s', NULL, %d, NULL) ON DUPLICATE KEY UPDATE `enabled` = %2\$d",
            $this->db->cleanValue($this->filename),
            (int) $this->enabled
        ));
    }
}

<?php
include __DIR__.'/vendor/autoload.php';

class Extension_Cron extends Extension
{
    private static $_isInit = false;

    public function fetchNavigation()
    {
        return [
            [
                'location' => 'System',
                'name' => 'Cron',
                'link' => '/',
            ],
        ];
    }

    public static function init()
    {
        if (self::$_isInit === true) {
            return;
        }
        self::$_isInit = true;
    }

    public function uninstall()
    {
        Symphony::Database()->query("DROP TABLE `tbl_cron`");
    }

    public function install()
    {
        return Symphony::Database()->query("CREATE TABLE `tbl_cron` (
              `name` varchar(100) NOT NULL,
              `last_executed` int(14) DEFAULT NULL,
              `enabled` set('yes','no') NOT NULL DEFAULT '',
              `last_output` text,
              `force_execution` set('yes','no') DEFAULT 'no',
              PRIMARY KEY (`name`)
            )"
        );
    }
}

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
        return Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_cron` (
          `name` varchar(100) NOT NULL,
          `last_executed` datetime default NULL,
          `enabled` tinyint(1) NOT NULL,
          `last_output` text,
          PRIMARY KEY (`name`)
        )");
    }
}

<?php

	require_once('lib/class.crontask.php');	
	require_once('lib/class.crontaskiterator.php');
	
	Class extension_Cron extends Extension{

		public function about(){
			return array('name' => 'Cron',
						 'version' => '0.1',
						 'release-date' => '2009-08-21',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://symphony-cms.com',
										   'email' => 'alistair@symphony-cms.com')
				 		);
		}
		
		public function fetchNavigation(){
			return array(

				array(
					'location' => 'System',
					'name' => 'Cron',
					'link' => '/'
				),		

			);			
		}

		
		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_cron`");
		}

		public function install(){
			return Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_cron` (
			  `name` varchar(100) NOT NULL,
			  `last_executed` datetime default NULL,
			  `enabled` tinyint(1) NOT NULL,
			  `last_output` text,
			  PRIMARY KEY (`name`)
			)");
		}

	}
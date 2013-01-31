rename table social_user_providers to social_provider_users;

CREATE TABLE `social_providers` (
  `id` int(11) NOT NULL auto_increment,
  `class_name` varchar(100) default NULL,
  `is_enabled` tinyint(4) default NULL,
  `config_data` text,
  `code` varchar(100) default NULL,
  PRIMARY KEY  (`id`),
  KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
CREATE TABLE `tx_simplepie_domain_model_feedsource` (
	`uid` int(11) unsigned NOT NULL auto_increment,
	`pid` int(11) NOT NULL DEFAULT '0',
	`tstamp` int(11) unsigned NOT NULL DEFAULT '0',
	`crdate` int(11) unsigned NOT NULL DEFAULT '0',
	`deleted` tinyint(4) unsigned NOT NULL DEFAULT '0',
	`hidden` tinyint(4) unsigned NOT NULL DEFAULT '0',
	`name` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`uid`),
	KEY pid (`pid`),
) ENGINE=MyISAM DEFAULT CHARSET=utf8;;
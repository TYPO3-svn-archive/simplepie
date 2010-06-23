CREATE TABLE tx_simplepie_domain_model_feedsource (
	uid INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	pid INT(11) DEFAULT '0' NOT NULL,
	
	tstamp INT(11) UNSIGNED DEFAULT '0' NOT NULL,
	crdate INT(11) UNSIGNED DEFAULT '0' NOT NULL,
	deleted TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
	hidden TINYINT(4) UNSIGNED DEFAULT '0' NOT NULL,
	
	name varchar(255) DEFAULT '' NOT NULL,
	url varchar(255) DEFAULT '' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY pid (pid),
);
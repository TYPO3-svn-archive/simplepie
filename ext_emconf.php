<?php

########################################################################
# Extension Manager/Repository config file for ext "simplepie".
#
# Auto generated 22-06-2010 16:21
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'SimplePie',
	'description' => 'Aggregates and displays various web feeds using SimplePie.',
	'category' => '',
	'shy' => 0,
	'version' => '1.0.0',
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Jochen Landvoigt',
	'author_email' => 'j.landvoigt@siwa.at',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.dev-4.4.99',
			'extbase' => '1.0.1-0.0.0',
			'fluid' => '1.0.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:19:{s:33:"class.tx_simplepie_flexhelper.php";s:4:"0def";s:12:"ext_icon.gif";s:4:"6cf0";s:17:"ext_localconf.php";s:4:"86f8";s:14:"ext_tables.php";s:4:"f871";s:14:"ext_tables.sql";s:4:"ac3f";s:31:"icon_tx_simplepie_rssconfig.gif";s:4:"475a";s:36:"Classes/Controller/RssController.php";s:4:"7b3d";s:34:"Classes/Domain/Model/RssConfig.php";s:4:"eb2a";s:33:"Classes/Domain/Model/RssEntry.php";s:4:"95a2";s:49:"Classes/Domain/Repository/RssConfigRepository.php";s:4:"0408";s:29:"Classes/Utility/Extension.php";s:4:"2050";s:41:"Configuration/FlexForms/flexform_list.xml";s:4:"0cb7";s:25:"Configuration/TCA/tca.php";s:4:"e80e";s:65:"Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php";s:4:"2b75";s:64:"Resources/Private/Backend/icon_tx_simplepie_feedlist_wizicon.gif";s:4:"70ca";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"910f";s:36:"Resources/Private/Libs/simplepie.inc";s:4:"3c6c";s:42:"Resources/Private/Templates/Rss/index.html";s:4:"d5d4";s:45:"Resources/Public/Javascript/simplepie_ajax.js";s:4:"a5ed";}',
	'suggests' => array(
	),
);

?>
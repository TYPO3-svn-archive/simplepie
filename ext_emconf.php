<?php

########################################################################
# Extension Manager/Repository config file for ext "simplepie".
#
# Auto generated 23-06-2010 10:59
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
	'_md5_values_when_last_written' => 'a:23:{s:33:"class.tx_simplepie_flexhelper.php";s:4:"0def";s:12:"ext_icon.gif";s:4:"6cf0";s:17:"ext_localconf.php";s:4:"be20";s:14:"ext_tables.php";s:4:"2bc2";s:14:"ext_tables.sql";s:4:"250c";s:32:"icon_tx_simplepie_feedsource.gif";s:4:"475a";s:37:"Classes/Controller/FeedController.php";s:4:"d259";s:34:"Classes/Domain/Model/FeedEntry.php";s:4:"fcb6";s:35:"Classes/Domain/Model/FeedSource.php";s:4:"ddfb";s:50:"Classes/Domain/Repository/FeedSourceRepository.php";s:4:"c294";s:29:"Classes/Utility/Extension.php";s:4:"2050";s:41:"Configuration/FlexForms/flexform_list.xml";s:4:"6b95";s:25:"Configuration/TCA/tca.php";s:4:"1501";s:38:"Configuration/TypoScript/constants.txt";s:4:"1743";s:34:"Configuration/TypoScript/setup.txt";s:4:"1c0c";s:65:"Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php";s:4:"2b75";s:64:"Resources/Private/Backend/icon_tx_simplepie_feedlist_wizicon.gif";s:4:"70ca";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"ef14";s:36:"Resources/Private/Libs/simplepie.inc";s:4:"3c6c";s:40:"Resources/Private/Partials/feedItem.html";s:4:"d5e1";s:42:"Resources/Private/Templates/Feed/ajax.html";s:4:"c94c";s:43:"Resources/Private/Templates/Feed/index.html";s:4:"57f1";s:45:"Resources/Public/Javascript/simplepie_ajax.js";s:4:"e328";}',
	'suggests' => array(
	),
);

?>
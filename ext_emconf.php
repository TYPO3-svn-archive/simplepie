<?php

########################################################################
# Extension Manager/Repository config file for ext "simplepie".
#
# Auto generated 09-06-2010 15:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Simplepie',
	'description' => 'RSS Plugin using Simplepie',
	'category' => '',
	'shy' => 0,
	'version' => '0.0.1',
	'dependencies' => 'extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
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
			'typo3' => '4.3.dev-4.3.99',
			'extbase' => '1.0.1-0.0.0',
			'fluid' => '1.0.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:33:"class.tx_simplepie_flexhelper.php";s:4:"0def";s:17:"ext_localconf.php";s:4:"86f8";s:14:"ext_tables.php";s:4:"6fb5";s:14:"ext_tables.sql";s:4:"ac3f";s:36:"Classes/Controller/RssController.php";s:4:"f64c";s:34:"Classes/Domain/Model/RssConfig.php";s:4:"c731";s:33:"Classes/Domain/Model/RssEntry.php";s:4:"2221";s:49:"Classes/Domain/Repository/RssConfigRepository.php";s:4:"0408";s:29:"Classes/Utility/Extension.php";s:4:"2050";s:41:"Configuration/FlexForms/flexform_list.xml";s:4:"1103";s:25:"Configuration/TCA/tca.php";s:4:"e80e";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"ee29";s:36:"Resources/Private/Libs/simplepie.inc";s:4:"2b65";s:42:"Resources/Private/Templates/Rss/index.html";s:4:"0c5f";}',
	'suggests' => array(
	),
);

?>
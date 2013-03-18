<?php

########################################################################
# Extension Manager/Repository config file for ext "simplepie".
#
# Auto generated 23-01-2012 15:50
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
	'version' => '1.1.0',
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
			'typo3' => '4.3.dev-4.6.99',
			'extbase' => '1.0.1-0.0.0',
			'fluid' => '1.0.1-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:41:{s:33:"class.tx_simplepie_flexhelper.php";s:4:"0def";s:12:"ext_icon.gif";s:4:"6cf0";s:17:"ext_localconf.php";s:4:"b52c";s:14:"ext_tables.php";s:4:"0c08";s:14:"ext_tables.sql";s:4:"542c";s:37:"Classes/Controller/FeedController.php";s:4:"4ddc";s:37:"Classes/Controller/FeedItemParser.php";s:4:"473a";s:37:"Classes/Controller/SimplePie_Sort.php";s:4:"201f";s:33:"Classes/Domain/Model/FeedItem.php";s:4:"31ac";s:35:"Classes/Domain/Model/FeedSource.php";s:4:"933f";s:50:"Classes/Domain/Repository/FeedSourceRepository.php";s:4:"c294";s:29:"Classes/Utility/Extension.php";s:4:"2050";s:41:"Classes/ViewHelpers/CommentViewHelper.php";s:4:"8317";s:37:"Classes/ViewHelpers/ForViewHelper.php";s:4:"0e3c";s:38:"Classes/ViewHelpers/NullViewHelper.php";s:4:"009c";s:40:"Classes/ViewHelpers/StrlenViewHelper.php";s:4:"0dfc";s:50:"Classes/ViewHelpers/Embed/JavaScriptViewHelper.php";s:4:"17a9";s:50:"Classes/ViewHelpers/Format/TimeframeViewHelper.php";s:4:"9935";s:36:"Configuration/FlexForms/FeedList.xml";s:4:"644b";s:25:"Configuration/TCA/TCA.php";s:4:"947e";s:38:"Configuration/TypoScript/constants.txt";s:4:"d26a";s:34:"Configuration/TypoScript/setup.txt";s:4:"dc76";s:50:"Documentation/Manual/DocBook/en/Administration.xml";s:4:"b4b9";s:49:"Documentation/Manual/DocBook/en/Configuration.xml";s:4:"c1ec";s:41:"Documentation/Manual/DocBook/en/Index.xml";s:4:"4e8a";s:48:"Documentation/Manual/DocBook/en/Introduction.xml";s:4:"69f2";s:50:"Documentation/Manual/DocBook/en/Known_problems.xml";s:4:"c109";s:41:"Documentation/Manual/DocBook/en/To-Do.xml";s:4:"c684";s:48:"Documentation/Manual/DocBook/en/Users_manual.xml";s:4:"6484";s:65:"Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php";s:4:"2b75";s:64:"Resources/Private/Backend/icon_tx_simplepie_feedlist_wizicon.gif";s:4:"70ca";s:58:"Resources/Private/Backend/icon_tx_simplepie_feedsource.gif";s:4:"3edf";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"d0c3";s:36:"Resources/Private/Libs/simplepie.php";s:4:"d2b0";s:46:"Resources/Private/Partials/AjaxJavascript.html";s:4:"345e";s:40:"Resources/Private/Partials/FeedItem.html";s:4:"b8e3";s:42:"Resources/Private/Templates/Feed/Ajax.html";s:4:"0197";s:43:"Resources/Private/Templates/Feed/Index.html";s:4:"ccf6";s:26:"Tests/Feedlist/Feed_01.xml";s:4:"76f5";s:26:"Tests/Feedlist/Feed_02.xml";s:4:"fd4f";s:26:"Tests/Feedlist/Feed_03.xml";s:4:"0e00";}',
	'suggests' => array(
	),
);

?>
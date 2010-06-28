<?php
if(!defined('TYPO3_MODE')) Die ('Access denied.');

$lPath = 'LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml';
$ePath = t3lib_extMgm::extPath($_EXTKEY);

t3lib_extMgm::allowTableOnStandardPages('tx_simplepie_domain_model_feedsource');
$TCA['tx_simplepie_domain_model_feedsource'] = Array (
	'ctrl' => array (
		'title' => $lPath . ':tx_simplepie_domain_model_feedsource',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'delete' => 'deleted',
		'enablecolumns' => Array ( 'disabled' => 'hidden' ),
		'dynamicConfigFile' => $ePath . 'Configuration/TCA/tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Private/Backend/icon_tx_simplepie_feedsource.gif',
	),
);

t3lib_extMgm::addStaticFile ($_EXTKEY, 'Configuration/TypoScript', 'Simplepie');

Tx_Extbase_Utility_Extension::registerPlugin ($_EXTKEY, 'Pi1', $lPath . ':feedlist.cewiz.title');

include_once($ePath . 'class.tx_simplepie_flexhelper.php');

$extensionName = t3lib_div::underscoredToUpperCamelCase($_EXTKEY);
$pluginSignature = strtolower($extensionName) . '_pi1';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/flexform_list.xml');

if (TYPO3_MODE == 'BE') {
	// Adds a wizard icon to the content element wizard.
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_simplepie_feedlist_wizicon'] = $ePath . 'Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php';
}

?>
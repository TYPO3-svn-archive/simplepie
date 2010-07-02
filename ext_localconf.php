<?php
If(!defined('TYPO3_MODE')) Die ('Access denied.');

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Pi1',
	array(
		'Feed' => 'index,ajax',
		'Timeset' => 'new'
	),
	array(
		'Feed' => 'index,ajax',
		'Timeset' => 'new'
	)
);
?>
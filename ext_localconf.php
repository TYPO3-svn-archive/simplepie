<?php
If(!defined('TYPO3_MODE')) Die ('Access denied.');

Tx_Extbase_Utility_Extension::configurePlugin (
	$_EXTKEY,
	'Pi1',
	Array ( 'Rss' => 'index,ajax',
			'Timeset' => 'new' ),
	Array ( 'Rss' => 'index,ajax',
			'Timeset' => 'new' )
);
?>
<?php
If(!defined('TYPO3_MODE')) Die ('Access denied.');

$TCA['tx_simplepie_domain_model_feedsource'] = array(
	'ctrl' => $TCA['tx_simplepie_domain_model_feedsource']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,name,url'
	),
	'columns' => array(
		'hidden' => array(
			'exclude'	=> 0,
			'label'		=> 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'	=> array(
				'type'		=> 'check',
				'default'	=> '0'
			)
		),
		'name' => array(
			'exclude'	=> 0,
			'label'		=> 'LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml:tx_simplepie_domain_model_feedsource.name',
			'config'	=> array(
				'type'		=> 'input',
				'size'		=> '30',
				'eval'		=> 'trim',
			)
		),
		'url' => array(
			'exclude'	=> 0,
			'label'		=> 'LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml:tx_simplepie_domain_model_feedsource.url',
			'config'	=> Array (
				'type'		=> 'input',
				'size'		=> '30',
				'eval'		=> 'trim',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden, name, url')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

?>
<?php
If(!defined('TYPO3_MODE')) Die ('Access denied.');

$TCA["tx_simplepie_domain_model_rssconfig"] = array (
	"ctrl" => $TCA["tx_simplepie_domain_model_rssconfig"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,name,url,anzahl"
	),
	"columns" => array (
		'hidden' => array (		
			'exclude' => 0,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml:tx_simplepie_domain_model_rssconfig.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "trim",
			)
		),
		"url" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml:tx_simplepie_domain_model_rssconfig.url",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "trim",
			)
		),
		"anzahl" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:simplepie/Resources/Private/Language/locallang_db.xml:tx_simplepie_domain_model_rssconfig.anzahl",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"eval" => "trim",
			)
		),
	),
	"types" => array (
		"0" => array('showitem' => 'hidden, name, url, anzahl')
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);

?>
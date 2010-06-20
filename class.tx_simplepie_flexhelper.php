<?php

class tx_simplepie_flexhelper {
  
  function addFields ($config) {
    $optionList = array();
    // add first option
    $optionList[0] = array(0 => 'option1', 1 => 'value1');
    // add second option
    $optionList[1] = array(0 => 'option2', 1 => 'value2');
    $config['items'] = array_merge($config['items'],$optionList);
	
	$records = tx_rnbase_util_DB::doSelect('round_name, uid', 'tx_t3sportsbet_betsets', $options, 0);
	foreach($records As $record) {
		$config['items'][] = array_values($record);
	}

	
    return $config;
  }
}

?>
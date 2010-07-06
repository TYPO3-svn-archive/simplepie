<?php

class Tx_Simplepie_Domain_Model_FeedSource extends Tx_Extbase_DomainObject_AbstractEntity {
	
	/**
	* 
	* @var string
	*/
	protected $name = '';
	/**
	* 
	* @var string
	*/
	protected $url = '';
	
	public function getName() {
		return $this->name;
	}
	
	public function getUrl() {
		return $this->url;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function setUrl($url) {
		$this->url = $url;
	}
}
?>
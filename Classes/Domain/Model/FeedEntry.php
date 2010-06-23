<?php

class Tx_Simplepie_Domain_Model_FeedEntry extends Tx_Extbase_DomainObject_AbstractEntity {

	protected $title = '';
	protected $date = '';
	protected $author = '';
	protected $copyright = '';
	protected $description = '';
	protected $permalink = '';
	protected $content = '';
	protected $items = '';
	protected $feedImageUrl = '';
	protected $feedTitle = '';
	protected $timestamp = 0;
	
	function getTitle() {
		return $this->title;
	}
	
	function getDate() {
		return $this->date;
	}
	
	function getAuthor() {
		return $this->author;
	}
	
	function getCopyright() {
		return $this->copyright();
	}
	
	function getDescription() {
		return $this->description;
	}
	
	function getPermalink() {
		return $this->permalink;
	}
	
	function getContent() {
		return $this->content;
	}
	
	function getItems() {
		return $this->items;
	}
	
	function getFeedImageUrl() {
		return $this->feedImageUrl;
	}
	
	function getFeedTitle() {
		return $this->feedTitle;
	}
	
	function getTimestamp() {
		return $this->timestamp;
	}
	
	function setTitle($title) {
		$this->title = $title;
	}
	
	function setDate($date) {
		$this->date = $date;
	}
	
	function setAuthor($author) {
		$this->author = $author;
	}
	
	function setCopyright($copyright) {
		$this->copyright = $copyright;
	}
	
	function setDescription($description) {
		$this->description = $description;
	}
	
	function setPermalink($permalink) {
		$this->permalink = $permalink;
	}
	
	function setContent($content) {
		$this->content = $content;
	}
	
	function setItems($items) {
		$this->items = $items;
	}
	
	function setFeedImageUrl($feedImageUrl) {
		$this->feedImageUrl = $feedImageUrl;
	}
	
	function setFeedTitle($feedTitle) {
		$this->feedTitle = $feedTitle;
	}

	function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}
	
	static function compareDesc($a, $b) {
		$ats = $a->getTimestamp();
		$bts = $b->getTimestamp();
		
		if ($ats == $bts)
			return 0;
		if ($ats > $bts) 
			return -1;
		if ($ats < $bts)
			return +1;
	}
	
	static function compareAsc($a, $b) {
		$ats = $a->getTimestamp();
		$bts = $b->getTimestamp();
		
		if ($ats == $bts)
			return 0;
		if ($ats < $bts) 
			return -1;
		if ($ats > $bts)
			return +1;
	}
	
}
?>
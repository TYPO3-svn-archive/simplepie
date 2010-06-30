<?php

class Tx_Simplepie_Domain_Model_FeedItem extends Tx_Extbase_DomainObject_AbstractEntity {

	protected $author = '';
	protected $content = '';
	protected $copyright = '';
	protected $date = '';
	protected $description = '';
	protected $enclosures = array();
	protected $feedImageUrl = '';
	protected $feedTitle = '';
	protected $permalink = '';
	protected $rating = array();
	protected $statistics = array();
	protected $timestamp = 0;
	protected $title = '';
	protected $type = 'unknown';


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

	function getEnclosures() {
		return $this->enclosures;
	}

	function getFeedImageUrl() {
		return $this->feedImageUrl;
	}

	function getFeedTitle() {
		return $this->feedTitle;
	}

	function getRating() {
		return $this->rating;
	}

	function getStatistics() {
		return $this->statistics;
	}

	function getTimestamp() {
		return $this->timestamp;
	}

	function getType() {
		return $this->type;
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

	function setEnclosures($enclosures) {
		$this->enclosures = $enclosures;
	}

	function setFeedImageUrl($feedImageUrl) {
		$this->feedImageUrl = $feedImageUrl;
	}

	function setFeedTitle($feedTitle) {
		$this->feedTitle = $feedTitle;
	}

	function setRating($rating) {
		$this->rating = $rating;
	}

	function setStatistics($statistics) {
		$this->statistics = $statistics;
	}

	function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}

	function setType($type) {
		$this->type = $type;
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

	static function compareRandom($a, $b) {
		$randvalue = rand(0,20);
		
		if (randvalue < 10)
			return -1;
		if (randvalue > 10)
			return +1;
	}

}
?>
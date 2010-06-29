<?php

Class Tx_Simplepie_Controller_FeedController_FeedItemParser {

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
	protected $type = 'unknown';

	protected $feedItem;


	/**
	 * Parse a SimplePie item object and correct values
	 *
	 * $item	object	SimplePie item object
	 */
	Public Function parseObject($item) {

		$this->feedItem = $item;

		// set SiplePie values
		$this->author = $item->get_author();
		$this->title = $item->get_title();
		$this->date = $item->get_date();
		$this->copyright = $item->get_copyright();
		$this->description = $item->get_description();
		$this->permalink = $item->get_permalink();
		$this->content = $item->get_content();
		$this->timestamp = $item->get_date('U');
		$this->type = $this->getItemType();

		// do some basic cleanup
		if ($this->content == $this->description) {
			$this->content = false;
		}
		if ($this->description == $this->title || html_entity_decode($this->description) == $this->title) {
			$this->description = false;
		}

		// special feed types
		switch ($this->type) {
			case 'facebook':
				break;
			case 'flickr':
				$this->parseFlickrItem();
				break;
			case 'twitter':
				break;
			case 'youtube':
				break;
		}

		// decode entities
		$this->author->name = html_entity_decode($this->author->name);
		$this->description = html_entity_decode($this->description);
		$this->content = html_entity_decode($this->content);

		// create FeedEntry object
		$feedEntry = new Tx_Simplepie_Domain_Model_FeedEntry();
		$feedEntry->setAuthor($this->author->name);
		$feedEntry->setTitle($this->title);
		$feedEntry->setDate($this->date);
		$feedEntry->setCopyright($this->copyright);
		$feedEntry->setDescription($this->description);
		$feedEntry->setPermalink($this->permalink);
		$feedEntry->setContent($this->content);
		$feedEntry->setTimestamp($this->date);
		$feedEntry->setType($this->type);

		return $feedEntry;
	}

	/**
	 * checks if a feed item comes from a known source as YouTube, Flickr & Co.
	 */
	Private function getItemType() {
		$type = 'unknown';

		$author = $this->feedItem->get_item_tags('', 'author');
		// Flickr
		if (isset($author[0]['attribs']['urn:flickr:']['profile'])) {
			$type = 'flickr';
		}

		return $type;
	}

	Private Function parseFacebookItem() {
		
	}

	/**
	 * corrects Flickr feed items
	 */
	Private Function parseFlickrItem() {

		// correct name/email: nobody@flickr.com ([Flickr Account Name])
		if (preg_match('/^nobody@flickr\.com/', $this->author->email)) {
			$this->author->name = preg_replace('/^([^(]*)\((.*)\)$/', "$2", $this->author->email);
			$this->author->email = '';
		}
		// use <media:description> instead of <description>
		$description = $this->feedItem->get_item_tags('http://search.yahoo.com/mrss/', 'description');
		if (isset($description[0]['data']) && $description[0]['data'] != '') {
			$this->description = $description[0]['data'];
		} else {
			// <media:description> is not set in feed when no description is given
			$this->description = false;
		}
		// use <link> to set permalink
		$link = $this->feedItem->get_item_tags('', 'link');
		if (isset($link[0]['data']) && $link[0]['data'] != '') {
			$this->permalink = $link[0]['data'];
		}
	}

	Private Function parseTwitterItem() {
		
	}

	Private Function parseYouTubeItem() {
		
	}

}

?>
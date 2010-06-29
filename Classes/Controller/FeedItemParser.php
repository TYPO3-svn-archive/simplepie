<?php

Class Tx_Simplepie_Controller_FeedController_FeedItemParser {

	/**
	 * Parse a SimplePie item object and correct values
	 *
	 * $item	object	SimplePie item object
	 */
	Public Function parseObject($item) {
		// echo '<h3>parseObject</h3><pre>';
		// print_r($item->get_author());

		// FeedEntry object
		$feedEntry = new Tx_Simplepie_Domain_Model_FeedEntry();
		$feedEntry->setAuthor($item->get_author());
		$feedEntry->setTitle($item->get_title());
		$feedEntry->setDate($item->get_date());
		$feedEntry->setCopyright($item->get_copyright());
		$feedEntry->setDescription($item->get_description());
		$feedEntry->setPermalink($item->get_permalink());
		$feedEntry->setContent($item->get_content());
		$feedEntry->setTimestamp($item->get_date('U'));
		$feedEntry->setType($this->getItemType($item));

		return $feedEntry;
	}

	/**
	 * checks if a feed item comes from a known source as YouTube, Flickr & Co.
	 */
	Private function getItemType($item) {
		$type = 'unknown';

		$author = $item->get_item_tags('', 'author');
		if (isset($author[0]['attribs']['urn:flickr:']['profile'])) {
			$type = 'flickr';
		}

		return $type;
	}

	Private Function parseFlickrItem() {
		
	}

	Private Function parseTwitterItem() {
		
	}

	Private Function parseFacebookItem() {
		
	}

	Private Function parseYouTubeItem() {
		
	}

}

?>
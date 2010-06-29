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
	protected $viewCount = false;
	protected $enclosures = array();

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
		// enclosures
		$enclosureCount = 0;
		foreach ($item->get_enclosures() as $enclosure) {
			$enclosureData = array(
				'duration' => $enclosure->get_duration(),
				'medium' => $enclosure->get_medium(),
				'src' => html_entity_decode($enclosure->get_link()),
				'thumbnail' => array('src' => html_entity_decode($enclosure->get_thumbnail())),
				'title' => html_entity_decode($enclosure->get_title()),
				'type' => $enclosure->get_real_type(),
			);
			$this->enclosures[$enclosureCount] = $enclosureData;
			$enclosureCount++;
		}

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
				$this->parseYouTubeItem();
				break;
		}

		// create FeedItem object
		$feedItem = new Tx_Simplepie_Domain_Model_FeedItem();
		$feedItem->setAuthor($this->author->name);
		$feedItem->setTitle($this->title);
		$feedItem->setDate($this->date);
		$feedItem->setCopyright($this->copyright);
		$feedItem->setDescription($this->description);
		$feedItem->setPermalink($this->permalink);
		$feedItem->setContent($this->content);
		$feedItem->setTimestamp($this->timestamp);
		$feedItem->setType($this->type);
		$feedItem->setEnclosures($this->enclosures);

		return $feedItem;
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

		$id = $this->feedItem->get_id();
		/**
		 * YouTube
		 *
		 * ATTENTION: To retrieve usable data from YouTube you have to use the proper gdata api!
		 * If you use http://gdata.youtube.com/feeds/base/users/[YouTube user]/uploads as feed source
		 * you only get a messy bunch of html code.
		 * Use http://gdata.youtube.com/feeds/api/videos?author=[YouTube user] to get all the <yt:> and <media:> tags.
		 * See http://gdata.youtube.com/demo/ for more options.
		 */
		if ($id && stripos($id, 'http://gdata.youtube.com/feeds/api/videos/') === 0) {
			$type = 'youtube';
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
		if (isset($description[0]['data']) && strlen($description[0]['data']) > 0) {
			$this->description = $description[0]['data'];
		} else {
			// <media:description> is not set in feed when no description is given
			$this->description = false;
		}

		// use <link> to set permalink
		$link = $this->feedItem->get_item_tags('', 'link');
		if (isset($link[0]['data']) && strlen($link[0]['data']) > 0) {
			$this->permalink = $link[0]['data'];
		}

		// enclosures: set medium=image
		$this->enclosures[0]['medium'] = 'image';

		// enclosures: set thumbnail size
		// TO-DO: set by TS
		$thumbSize = 'small';
		$thumbUrl = $this->enclosures[0]['thumbnail']['src'];
		switch (strtolower($thumbSize)) {
			case 'square':
				$thumbUrl = preg_replace('/(_[m|t|s|b]){0,1}\.jpg$/', '_s.jpg', $thumbUrl);
				break;
			case 'thumbnail':
				$thumbUrl = preg_replace('/(_[m|t|s|b]){0,1}\.jpg$/', '_t.jpg', $thumbUrl);
				break;
			case 'small':
				$thumbUrl = preg_replace('/(_[m|t|s|b]){0,1}\.jpg$/', '_m.jpg', $thumbUrl);
				break;
			case 'medium':
				$thumbUrl = preg_replace('/(_[m|t|s|b]){0,1}\.jpg$/', '.jpg', $thumbUrl);
				break;
			case 'large':
				$thumbUrl = preg_replace('/(_[m|t|s|b]){0,1}\.jpg$/', '_b.jpg', $thumbUrl);
				break;
		}
		$this->enclosures[0]['thumbnail']['src'] = $thumbUrl;

		// decode entities
		$this->author->name = html_entity_decode($this->author->name);
		$this->description = html_entity_decode($this->description);
		$this->content = html_entity_decode($this->content);
	}

	Private Function parseTwitterItem() {
		
	}

	/**
	 * corrects YouTube feed items
	 */
	Private Function parseYouTubeItem() {

		// enclosures: use biggest thumbnail if specified
		$enclosure = $this->feedItem->get_enclosure();
		if ($thumbnails = $enclosure->get_thumbnails()) {
			if (isset($thumbnails[3]) && strlen($thumbnails[3]) > 0) {
				for ($i=0; $i<count($this->enclosures); $i++) {
					$this->enclosures[$i]['thumbnail']['src'] = $thumbnails[3];
				}
			}
		}

		// TO-DO: Add viewCount
	}

}

?>
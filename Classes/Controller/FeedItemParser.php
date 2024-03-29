<?php

Class Tx_Simplepie_Controller_FeedController_FeedItemParser {

	protected $author = '';
	protected $content = '';
	protected $copyright = '';
	protected $date = '';
	protected $description = '';
	protected $enclosures = array();
	protected $feedImageUrl = '';
	protected $feedTitle = '';
	protected $items = '';
	protected $permalink = '';
	protected $rating = array();
	protected $settings = '';
	protected $statistics = array();
	protected $timestamp = 0;
	protected $title = '';
	protected $type = 'unknown';
	protected $viewCount = false;


	protected $feedItem;


	/**
	 * Parse a SimplePie item object and correct values
	 *
	 * $item	object	SimplePie item object
	 */
	Public Function parseObject($item, $settings) {

		$this->feedItem = $item;
		$this->settings = $settings;

		// set SiplePie values
		$this->author = $item->get_author();
		$this->title = trim(html_entity_decode($item->get_title()));
		$this->date = $item->get_date();
		$this->copyright = trim($item->get_copyright());
		$this->description = trim($item->get_description());
		$this->permalink = trim($item->get_permalink());
		$this->content = trim($item->get_content());
		$this->timestamp = $item->get_date('U');
		$this->type = $this->getItemType();
		// enclosures
		$enclosureCount = 0;
		$enclosures = $item->get_enclosures();
		if (is_array($enclosures)) {
			foreach ($enclosures as $enclosure) {
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
		}
		unset($enclosures);

		// do some basic cleanup
		if ($this->content == $this->description) {
			$this->content = false;
		}
		if ($this->description == $this->title || html_entity_decode($this->description) == $this->title) {
			$this->description = false;
		}

		// check URIs
		$this->permalink = preg_replace('/&amp;/', '&', html_entity_decode($this->permalink));

		// special feed types
		switch ($this->type) {
			case 'facebook_page':
				$this->parseFacebookPageItem();
				break;
			case 'flickr':
				$this->parseFlickrItem();
				break;
			case 'twitter_static':
				$this->parseTwitterItem('static');
				break;
			case 'twitter_api':
				$this->parseTwitterItem('api');
				break;
			case 'youtube':
				$this->parseYouTubeItem();
				break;
		}

		// remove multiple white spaces
		$this->title = trim(preg_replace('/\s+/', ' ', $this->title));
		$this->description = trim(preg_replace('/\s+/', ' ', $this->description));
		$this->content = trim(preg_replace('/\s+/', ' ', $this->content));
		$this->author->name = trim(preg_replace('/\s+/', ' ', $this->author->name));
		$this->author->realName = trim(preg_replace('/\s+/', ' ', $this->author->realName));

		// no title?
		if ($this->title == '') {
			$this->title = trim(strip_tags(preg_replace('/<br[\s\/]*>/i', ' ', (html_entity_decode($this->description)))));
			$this->description = false;
		}

		// set feed logo as author thumbnail
		if (!$this->author->thumbnail['src']) {
			$feed = $this->feedItem->get_feed();
			$feedImageUrl = $feed->get_image_url();
			if ($feedImageUrl && strlen($feedImageUrl) > 0) {
				$this->author->thumbnail['src'] = $feedImageUrl;
			}
		}

		// create FeedItem object
		$feedItem = new Tx_Simplepie_Domain_Model_FeedItem();
		$feedItem->setAuthor(array_filter(array(
			'e-mail' => $this->author->email,
			'link' => $this->author->link,
			'name' => $this->author->name,
			'realName' => $this->author->realName,
			'thumbnail' => $this->author->thumbnail
		)));
		$feedItem->setTitle($this->title);
		$feedItem->setDate($this->date);
		$feedItem->setCopyright($this->copyright);
		$feedItem->setDescription(array(
			'html' => $this->description,
			'plain' => trim(strip_tags(preg_replace('/<br[\s\/]*>/i', ' ', (html_entity_decode($this->description)))))
		));
		$feedItem->setPermalink($this->permalink);
		$feedItem->setContent($this->content);
		$feedItem->setTimestamp($this->timestamp);
		$feedItem->setType($this->type);
		$feedItem->setEnclosures($this->enclosures);
		$feedItem->setStatistics($this->statistics);
		$feedItem->setRating($this->rating);

		return $feedItem;
	}

	/**
	 * checks if a feed item comes from a known source as YouTube, Flickr & Co.
	 */
	Private function getItemType() {
		$type = 'unknown';

		$author = $this->feedItem->get_item_tags('', 'author');
		$id = $this->feedItem->get_id();
		$feed = $this->feedItem->get_feed();
		$feedBase = $feed->get_base();

		// Flickr
		if (
			isset($author[0]['attribs']['urn:flickr:']['profile']) ||
			isset($author[0]['attribs']['urn:flickr:user']['profile'])
		) {
			return 'flickr';
		}

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
			return 'youtube';
		}

		/**
		 * Twitter
		 *
		 * ATTENTION: Calls to the twitter search api (http://search.twitter.com/search.atom?q=from%3A[twitter user]
		 * are limited (see http://apiwiki.twitter.com/Rate-limiting) and sometimes only deliver tweets of the
		 * last few days. So older tweets cannot be accessed trough the api.
		 * The 'static' feed (http://twitter.com/statuses/user_timeline/[twitter user id].rss works perfectly!
		 */
		$feedTitle = $feed->get_title();
		$feedDescription = $feed->get_description();
		if (strpos($feedDescription, 'Twitter updates from') === 0 && strpos($feedTitle, 'Twitter / ') === 0) {
			return 'twitter_static';
		}
		if ($id && stripos($id, 'tag:search.twitter.com') === 0) {
			return 'twitter_api';
		}

		/**
		 * Facebook
		 *
		 * http://www.facebook.com/feeds/page.php?id=[...]&format=rss20
		 * language specific versions like http://de-de.facebook.com/feeds/page.php?id=[...]&format=atom10
		 * may work. To get best results use www.facebook.com!
		 */
		if (isset($feedBase) && preg_match('#http://(.*)\.facebook\.com/#', $feedBase)) {
			$type = 'facebook';
			$linkRelSelf = $feed->get_links('self'); // only works for ...&format=atom10
			if (isset($linkRelSelf[0]) && preg_match('#http://(.*)\.facebook\.com/feeds/page\.php\?#', $linkRelSelf[0])) {
				$type = 'facebook_page';
			}
		}
		return $type;
	}

	/**
	 * Function that looks through the given string and extracts image urls
	 *
	 * @param	string	data to find images in
	 * @return	array	urls of images found
	 */
	Private Function findImages($data) {

		preg_match_all('/<img [^>]*src="([^"]*)"([^>]*)>/i', $data, $imageUrls);

		if (is_array($imageUrls[1])) {
			for ($i=0; $i<count($imageUrls[1]);$i++) {
				$imageUrls[1][$i] = html_entity_decode($imageUrls[1][$i]);
			}

			return $imageUrls[1];
		} else {
			return false;
		}
	}

	Private Function parseFacebookPageItem() {
		// kill useless html crap
		$this->description = preg_replace('/^(\s*(<br(\s*\/)?>)\s*)*/i', '', $this->description);
		$this->description = preg_replace('/((\s)*(title|target|id|onclick|style)=""(\s)*)+/i', ' ', $this->description);
		$this->description = preg_replace('/(<img([^>])*)(\s)*><\/img>/i', "$1 />", $this->description);
		$this->description = preg_replace('/\s*(onmousedown="UntrustedLink\.bootstrap\(\$\(this\), ")[^"]*", event\);"\s*/i', ' ', $this->description);
		$this->description = preg_replace('/\s*target="_blank"\s*/i', ' ', $this->description);

		// profile image
		$feed = $this->feedItem->get_feed();
		$feedLogo = $feed->get_feed_tags('http://www.w3.org/2005/Atom', 'logo');
		if (isset($feedLogo[0]['data']) && strlen($feedLogo[0]['data']) > 0) {
			$this->author->thumbnail['src'] = $feedLogo[0]['data'];
		}

		/*
		 author link
			formerly:	http://www.facebook.com/posted.php?id={FacebookID}
			current:	http://www.facebook.com/{FacebookID}
		*/
		$selfLink = html_entity_decode($feed->subscribe_url());
		parse_str(parse_url($selfLink, PHP_URL_QUERY), $selfLinkParams);
		if (isset($selfLinkParams['id']) && $selfLinkParams['id'] > 0) {
			$this->author->link = str_replace('{id}', $selfLinkParams['id'], $this->settings['feedItem']['enclosure']['facebook_page']['authorLinkUrl']);
		}

		// set images as enclosures
		$thumbUrls = $this->findImages($this->description);
		foreach ($thumbUrls as $thumbUrl) {
			$imageUrl = preg_replace('/(_[n|s])\.jpg$/', '_n.jpg', $thumbUrl);
			$this->enclosures[] = array(
				'thumbnail' => array('src' => $thumbUrl),
				'src' => $imageUrl,
				'medium' => 'image',
				'type' => 'image/jpeg',
			);
		}

		$this->permalink = trim(html_entity_decode($this->permalink));

		// Correct title
		$this->title = trim(html_entity_decode($this->title));
		// if no title extract it from description
		if ($this->title == '') {
			$description = trim(preg_replace('/\s+/', ' ', (html_entity_decode($this->description))));
			// remove linked image at beginning
			if (preg_match('#^<a href="[^>]*><img [^>]*></a>#', $description)) {
				$description = preg_replace('#^<a href="[^>]*><img [^>]*></a>#', '', $description);
				$description = preg_replace('#^(\s*(<br(\s*\/)?>)\s*)*#', '', $description);
			}
			// remove anchor having an image as link text
			if (preg_match('#^<a href="[^>]+>http(s){0,1}://[^<]+\.(jpg|jpeg|gif|png){1}</a>#', $description)) {
				$description = preg_replace('#^<a href="[^>]+>http(s){0,1}://[^<]+\.(jpg|jpeg|gif|png){1}</a>#', '', $description);
				$description = preg_replace('#^(\s*(<br(\s*\/)?>)\s*)*#', '', $description);
			}
			// if a link at the beginning is left, use it as title
			if (preg_match('#^<a href="[^>]*>([^<]*)</a>#', $description, $title)) {
				$this->title = $title[1];
				$description = preg_replace('#^<a href="[^>]*>[^<]*</a>#', '', $description);
				$description = preg_replace('#^(\s*(<br(\s*\/)?>)\s*)*#', '', $description);
			}
			$this->description = $description;
		}
		// if title in description, extract it
		if ($this->description != '' && $this->title != '' && strpos($this->description, $this->title) === 0) {
			$this->description = str_replace($this->title, '', $this->description);
			// remove trailing spaces and breaks
			$this->description = preg_replace('/^(\s*(<br(\s*\/)?>)\s*)*/i', '', $this->description);
		}
		$this->description = trim($this->description);
	}

	/**
	 * corrects Flickr feed items
	 */
	Private Function parseFlickrItem() {
		// correct name/email: nobody@flickr.com ([Real Name])
		if (preg_match('/^nobody@flickr\.com/', $this->author->email)) {
			$this->author->name = preg_replace('/^([^(]*)\((.*)\)$/', "$2", $this->author->email);
			$this->author->email = '';
		}

		// set author link
		$authorLink = $this->feedItem->get_item_tags('', 'author');
		if (isset($authorLink[0]['attribs']['urn:flickr:']['profile']) && strlen($authorLink[0]['attribs']['urn:flickr:']['profile']) > 0) {
			$this->author->link = $authorLink[0]['attribs']['urn:flickr:']['profile'];
		}
		// xmlns:flickr="urn:flickr:user" version="2.0"
		if (isset($authorLink[0]['attribs']['urn:flickr:user']['profile']) && strlen($authorLink[0]['attribs']['urn:flickr:user']['profile']) > 0) {
			$this->author->link = $authorLink[0]['attribs']['urn:flickr:user']['profile'];
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

	Private Function parseTwitterItem($type='static') {
		switch (strtolower($type)) {
			case 'static':
				// extract realName from title
				$author = '';
				preg_match('/^([^:]*):/', $this->title, $author);
				if (is_array($author) && strlen($author[1]) > 1) {
					$this->author->name = trim($author[1]);
					$this->author->link = 'http://twitter.com/' . $this->author->name;
					$this->title = trim(preg_replace('/^([^:]*):/', '', $this->title));
				}
				// extract author realName from feed.description
				$feed = $this->feedItem->get_feed();
				$feedDescription = $feed->get_description();
				if (strlen($feedDescription) > 0) {
					$this->author->realName = trim(preg_replace('/Twitter updates from ([^\/]*)\/(.*)/', '$1', $feedDescription));
				}
				// set description
				$target = $this->settings['feedItem']['linkTarget'];
				$this->description = $this->title;
				$this->description = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"" . $target . "\">\\2</a>", $this->description);
				$this->description = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"" . $target . "\">\\2</a>", $this->description);
				$this->description = preg_replace('/@(\S+)/', '<a href="http://www.twitter.com/$1" target="' . $target . '">@$1</a>', $this->description);
				$this->description = preg_replace('/#(\S+)/', '<a href="http://search.twitter.com/search?q=$1" target="' . $target . '">#$1</a>', $this->description);
				break;
			case 'api':
				// get profile image
				$twitterImage = $this->feedItem->get_item_tags('http://www.w3.org/2005/Atom', 'link');
				if (is_array($twitterImage)) {
					foreach ($twitterImage as $image) {
						if (isset($image['attribs']['']['rel']) && $image['attribs']['']['rel'] == 'image') {
							$this->author->thumbnail['src'] = $image['attribs']['']['href'];
							break;
						}
					}
				}
				// set realName
				preg_match('/([^(]*)\(([^)]*)\)/', $this->author->name, $author);
				if (is_array($author) && strlen($author[1]) > 1) {
					$this->author->name = trim($author[1]);
					$this->author->realName = trim($author[2]);
				}
				break;
		}
		// set default author.thumbnail
		if (
			(!isset($this->author->thumbnail['src']) || $this->author->thumbnail['src'] == '')
			&& isset($this->settings['feedItem']['author']['defaultImage'])
			&& strlen($this->settings['feedItem']['author']['defaultImage']) > 0
		) {
			$this->author->thumbnail['src'] = $this->settings['feedItem']['author']['defaultImage'];
		}
		// set defined author.thumbnail
		if (
			is_array($this->settings['feedItem']['author']['flickr']['profileImages'])
			&& count($this->settings['feedItem']['author']['flickr']['profileImages']) > 0
		) {
			foreach($this->settings['feedItem']['author']['flickr']['profileImages'] as $accountName => $profileImage) {
				if(strtolower($accountName) == strtolower($this->author->name)) {
					$this->author->thumbnail['src'] = $profileImage;
					break;
				}
			}
		}
	}

	/**
	 * corrects YouTube feed items
	 */
	Private Function parseYouTubeItem() {

		// enclosures: use biggest thumbnail if specified
		$enclosure = $this->feedItem->get_enclosure();
		if ($enclosure && $thumbnails = $enclosure->get_thumbnails()) {
			if (isset($thumbnails[3]) && strlen($thumbnails[3]) > 0) {
				for ($i=0; $i<count($this->enclosures); $i++) {
					$this->enclosures[$i]['thumbnail']['src'] = $thumbnails[3];
				}
			}
		}

		// decode permalink
		$this->permalink = html_entity_decode($this->permalink);

		// ratings
		$rating = $this->feedItem->get_item_tags('http://schemas.google.com/g/2005', 'rating');
		if (is_array($rating[0]['attribs'][''])) {
			$this->rating = $rating[0]['attribs'][''];
		}

		// set author link
		$this->author->link = 'http://youtube.com/' . $this->author->name;

		// statistics
		$statistics = $this->feedItem->get_item_tags('http://gdata.youtube.com/schemas/2007', 'statistics');
		if (isset($statistics[0]['attribs']['']['favoriteCount']) && strlen($statistics[0]['attribs']['']['favoriteCount']) > 0) {
			$this->statistics['favoriteCount'] = round($statistics[0]['attribs']['']['favoriteCount']);
		}
		if (isset($statistics[0]['attribs']['']['viewCount']) && strlen($statistics[0]['attribs']['']['viewCount']) > 0) {
			$this->statistics['viewCount'] = round($statistics[0]['attribs']['']['viewCount']);
		}
	}

}

?>
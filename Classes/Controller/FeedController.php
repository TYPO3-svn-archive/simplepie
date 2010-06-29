<?php

require_once(t3lib_extMgm::extPath('simplepie', 'Resources/Private/Libs/simplepie.php'));
require_once(t3lib_extMgm::extPath('simplepie', 'Classes/Controller/FeedItemParser.php'));
require_once('Zend/Http/Client.php');

Class Tx_Simplepie_Controller_FeedController
	Extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	* @var Tx_Simplepie_Domain_Repository_FeedSourceRepository
	*/
	Protected $feedSourceRepository;

	/**
	 * An instance of tslib_cObj
	 *
	 * @var        tslib_cObj
	 */
	protected $contentObject;

	var $thumbnailCachePath = 'typo3temp/simplepie_thumbnails/';

	Public Function initializeAction() {
		$this->feedSourceRepository =& t3lib_div::makeInstance ('Tx_Simplepie_Domain_Repository_FeedSourceRepository');
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	Public Function indexAction() {
		$feedEntrys = $this->getAllFeedElements();

		$feedEntrysResult = array();
		$feedEntrysResult[] = $feedEntrys[0];
		//$feedEntrysResult = $feedEntrys;

		$this->view->assign('feedEntrys', $feedEntrysResult);
	}
	
	Public Function ajaxAction() {
		$this->jsonArray['content'] = $this->getAjaxContent();
		$content = json_encode($this->jsonArray);
		print $content;
		exit;
	}

	Private function getAllFeedElements() {
		$feedEntrys = array();

		$feedurls = explode(',', $this->settings['feedSelection']);

		$itemcount = 0;
		foreach ($feedurls as $urlid) {
			$feedSource = $this->feedSourceRepository->findByUid((int)$urlid);
			$feed = new SimplePie($feedSource->getUrl());
			$feed->enable_order_by_date(true);

			// enable/disable caching
			if ($this->settings['cacheDuration'] > 0) {
				$feed->set_cache_location('typo3temp/simplepie_thumbnails/');
				$feed->set_cache_duration($this->settings['cacheDuration']);
				$feed->enable_cache(true);
			} else {
				$feed->enable_cache(false);
			}

			$feed->init();

			$feed->handle_content_type();
			$this->view->assign('feedtitle', $feed->get_title() . ' - ' . $feedSource->getUrl());

			foreach ($feed->get_items() as $item) {
				/**
				 * TODO: über Typoscript Variable setzen
				 */

				$itemParser = new Tx_Simplepie_Controller_FeedController_FeedItemParser();
				$itemParser->parseObject($item);

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

				$feedItems = array();
				if ($enclosure = $item->get_enclosure()) {
					$tempFeedItem = array(
						'description' => $enclosure->get_description(),
						'extension' => $enclosure->get_extension(),
						'link' => str_replace('&amp;', '&', $enclosure->get_link()),
						'thumbnail' => $enclosure->get_thumbnail(),
						'title' => html_entity_decode($enclosure->get_title()),
						'type' => $enclosure->get_real_type(),
					);
					if (strlen($tempFeedItem['link']) > 0) {
						$filename = $this->handleCacheImage($tempFeedItem['link']);
						$tempFeedItem['link'] = $this->getResizedImageLink($filename);
					}
					$feedItems[] = $tempFeedItem;
				}
				$feedEntry->setItems($feedItems);

				if (strlen($feed->get_image_url()) > 0) {
					$filename = $this->handleCacheImage($feed->get_image_url());
					$feedEntry->setFeedImageUrl($this->getResizedFeedImageLink($filename));
				}
				$feedEntry->setFeedTitle($feed->get_title());

				$feedEntrys[] = $feedEntry;
				$itemcount++;
			}
		}

		if ($this->settings['sorting'] == 'DESC') {
			usort($feedEntrys, array("Tx_Simplepie_Domain_Model_feedEntry", "compareDesc"));
		}
		if ($this->settings['sorting'] == 'ASC') {
			usort($feedEntrys, array("Tx_Simplepie_Domain_Model_feedEntry", "compareAsc"));
		}

		return $feedEntrys;
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

	Private function getAjaxContent() {
		$nextItem = t3lib_div::GPvar('item');

		$feedEntrys = $this->getAllFeedElements();
		$entry = $feedEntrys[$nextItem];
		//print sizeof($feedEntrys);

		$this->view->assign('feedEntrys', array($entry));
		return $this->view->render();
		//return "test";
	}

	Private function handleCacheImage($imgUrl) {
		//http://farm2.static.flickr.com/1271/4699389396_545152349a_s.jpg

		if (!file_exists($this->thumbnailCachePath)) {
			mkdir($this->thumbnailCachePath);
		}

		$parsedUrl = parse_url($imgUrl);

		$client = new Zend_Http_Client($imgUrl, array('maxredirects' => 0,'timeout' => 30));
		$filename = $this->thumbnailCachePath . md5($imgUrl) . '.jpg';
		if (!file_exists($filename)) {
			$client->setStream($filename)->request('GET');
		}

		return $filename;
	}

	Private function getResizedImageLink($filename) {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		$ts['img.']['file.']['maxH'] = $this->settings['feedEntryImageHeight'];
		$ts['img.']['file.']['maxW'] = $this->settings['feedEntryImageWidth'];
		$img = $this->contentObject->IMG_RESOURCE($ts['img.']);
		return $img;
	}

	Private function getResizedFeedImageLink($filename) {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		$ts['img.']['file.']['maxH'] = $this->settings['feedImageHeight'];
		$ts['img.']['file.']['maxW'] = $this->settings['feedImageWidth'];
		$img = $this->contentObject->IMG_RESOURCE($ts['img.']);
		return $img;
	}

	Private function getImageTS() {
		$ts['img'] = 'IMAGE';
		$ts['img.'] = $GLOBALS['TSFE']->tmpl->setup['tt_content.']['image.']['20.']['1.'];
		unset($ts['img.']['file.']['import.']);
		$ts['img.']['format'] = 'jpg';
		unset($ts['img.']['altText.']);
		$ts['img.']['altText'] = $actalt;
		unset($ts['img.']['titleText.']);
		$ts['img.']['titleText'] = $acttitle;
		unset($ts['img.']['imageLinkWrap.']);
		return $ts;
	}
}
?>
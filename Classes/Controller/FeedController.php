<?php

require_once(t3lib_extMgm::extPath('simplepie', 'Resources/Private/Libs/simplepie.php'));
require_once(t3lib_extMgm::extPath('simplepie', 'Classes/Controller/SimplePie_Sort.php'));
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
		$feedEntrys = $this->getFeedElements();

		$feedEntrysResult = array();
		//$feedEntrysResult[] = $feedEntrys[0];
		$feedEntrysResult = $feedEntrys;

		$this->view->assign('feedEntrys', $feedEntrysResult);
	}
	
	Public Function ajaxAction() {
		$this->jsonArray['content'] = $this->getAjaxContent();
		$content = json_encode($this->jsonArray);
		print $content;
		exit;
	}

	Private function getAllFeedElements() {
		return $this->getFeedElements(true);
	}
	
	Private function getFeedElements($disableItemCount = false, $elementfrom = 0, $elementcount = 0) {
		$feedEntrys = array();
		$rawFeedItems = array();
		
		if ($this->settings['feedMaxItems'] > 0 && $elementcount == 0)
			$elementcount = $this->settings['feedMaxItems'];
		
		$feedurls = explode(',', $this->settings['feedSelection']);
		$itemsperfeed = explode(',', $this->settings['feedItemsCount']);
		
		$itemcount = 0;
		for ($i = 0; $i < count($feedurls); $i++ ) {
			$urlid = $feedurls[$i];
			$feedSource = $this->feedSourceRepository->findByUid((int)$urlid);
			
			$feed = new Tx_Simplepie_Controller_FeedController_SimplePie_Sort($feedSource->getUrl());
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

			if ($this->settings['sorting'] == 'REVERSEFEED') {
				$rawitems = array_reverse($feed->get_items());
			}
			else {
				$rawitems = $feed->get_items();
			}
			
			$feeditemcount = 0;
			foreach ($rawitems as $item) {
				if (!$disableItemCount && $i <= count($itemsperfeed) && $feeditemcount >= $itemsperfeed[$i] && $itemsperfeed[$i] > 0 ) {
					break;
				}
				
				$rawFeedItems[] = $item;
				$itemcount++;
				$feeditemcount++;	
			}
		}

		/* sorting */
		if ($this->settings['sorting'] == 'DESC') {
			usort($rawFeedItems, array("Tx_Simplepie_Controller_FeedController_SimplePie_Sort", "compareDesc"));
		}
		if ($this->settings['sorting'] == 'ASC') {
			usort($rawFeedItems, array("Tx_Simplepie_Controller_FeedController_SimplePie_Sort", "compareAsc"));
		}
		
		
		/* max items check */
		if (!$disableItemCount && $elementcount > 0) {
			$rawFeedItems = array_slice($rawFeedItems, $elementfrom, $elementcount);
		}
		
		/* item parsing */
		foreach($rawFeedItems as $item) {
			$itemParser = new Tx_Simplepie_Controller_FeedController_FeedItemParser();
			$feedEntry = $itemParser->parseObject($item);

/*
			TO-DO:
			- move enclosures to itemParser
			- then check if enclosure is present an chache thumbnails
			- get dimensions of thumbnails

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
*/
			if (strlen($feed->get_image_url()) > 0) {
				$filename = $this->handleCacheImage($feed->get_image_url());
				$feedEntry->setFeedImageUrl($this->getResizedFeedImageLink($filename));
			}
			$feedEntry->setFeedTitle($feed->get_title());

			$feedEntrys[] = $feedEntry;
		}
		return $feedEntrys;
	}

	Private function getAjaxContent() {
		$nextItem = t3lib_div::GPvar('item');

		$entrys = array();
		if ($this->settings['ajaxMode'] == 'SINGLE') {
			$feedEntrys = $this->getFeedElements(false,$nextItem,1);
			$entry = $feedEntrys[0];
			$entrys[] = $entry;
		}
		
		if ($this->settings['ajaxMode'] == 'PAGING') {
			$page = t3lib_div::GPvar('item');
			$pageitems = $this->settings['feedMaxItems'];
			$startitem = $page * $pageitems;
			$entrys = $this->getFeedElements(false,$startitem,$pageitems);
			//$entrys = array_slice($feedEntrys, $startitem, $pageitems);
		}
		$this->view->assign('feedEntrys', $entrys);
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
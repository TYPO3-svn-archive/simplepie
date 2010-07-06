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
		$feedItems = $this->getFeedItems();
		$this->view->assign('feedItems', $feedItems);
		$this->view->assign('pid', $GLOBALS['TSFE']->id);
		$this->view->assign('fluidTest', 'hello<br /> w<b>o</b>rld!');
	}

	Public Function ajaxAction() {
		$this->jsonArray['content'] = $this->getAjaxContent();
		$content = json_encode($this->jsonArray);
		print $content;
		exit;
	}

	Private function getAllFeedItems() {
		return $this->getFeedItems(true);
	}

	Private function getFeedItems($disableItemCount = false, $elementfrom = 0, $elementcount = 0) {
		$feedItems = array();
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
			$this->view->assign(
				'feed', array(
					'title' => $feed->get_title(),
					'source' => $feedSource->getUrl(),
					'sorting' => $this->settings['sorting'],
				)
			);

			if ($this->settings['sorting'] == 'REVERSEFEED') {
				$rawitems = array_reverse($feed->get_items());
			} else {
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
			$feedItem = $itemParser->parseObject($item);

/*
			TO-DO:
			- get dimensions of resized thumbnails
*/

			// cache author thumbnail
			$author = $feedItem->getAuthor();
			if (isset($author['thumbnail']['src']) && strlen($author['thumbnail']['src']) > 0) {
				$filename = $this->handleCacheImage($author['thumbnail']['src']);
				$author['thumbnail']['src'] = $this->getResizedItemAuthorImageLink($filename);
				$feedItem->setAuthor($author);
			}

			// cache enclosures
			$enclosures = $feedItem->getEnclosures();
			for ($i=0; $i<count($enclosures); $i++) {
				if (isset($enclosures[$i]['thumbnail']['src']) && strlen($enclosures[$i]['thumbnail']['src']) > 0) {
					$filename = $this->handleCacheImage($enclosures[$i]['thumbnail']['src']);
					$enclosures[$i]['thumbnail']['src'] = $this->getResizedItemImageLink($filename);
				}
			}
			$feedItem->setEnclosures($enclosures);

			$feedItems[] = $feedItem;
		}
		return $feedItems;
	}

	Private function getAjaxContent() {
		$nextItem = t3lib_div::_GP('item');

		$items = array();
		if ($this->settings['ajaxMode'] == 'SINGLE') {
			$feedItems = $this->getFeedItems(false,$nextItem,1);
			$item = $feedItems[0];
			$items[] = $item;
		}

		if ($this->settings['ajaxMode'] == 'PAGING') {
			$page = t3lib_div::GPvar('item');
			$pageitems = $this->settings['feedMaxItems'];
			$startitem = $page * $pageitems;
			$items = $this->getFeedItems(false,$startitem,$pageitems);
		}
		$this->view->assign('feedItems', $items);
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

	Private function getResizedItemAuthorImageLink($filename) {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		switch (strtolower($this->settings['feedItem']['author']['imageScaleMode'])) {
			case 'crop':
				$ts['img.']['file.']['height'] = $this->settings['feedItem']['author']['imageHeight'] . 'c';
				$ts['img.']['file.']['width'] = $this->settings['feedItem']['author']['imageHeight'] . 'c';
				break;
			case 'disproportionally':
				$ts['img.']['file.']['minH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['minW'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['author']['imageHeight'];
				break;
			default:
				// proportionally
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['author']['imageHeight'];
				break;
		}
		$img = $this->contentObject->IMG_RESOURCE($ts['img.']);
		return $img;
	}

	Private function getResizedItemImageLink($filename) {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		switch (strtolower($this->settings['feedItem']['enclosure']['imageScaleMode'])) {
			case 'crop':
				$ts['img.']['file.']['height'] = $this->settings['feedItem']['enclosure']['imageHeight'] . 'c';
				$ts['img.']['file.']['width'] = $this->settings['feedItem']['enclosure']['imageHeight'] . 'c';
				break;
			case 'disproportionally':
				$ts['img.']['file.']['minH'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				$ts['img.']['file.']['minW'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				break;
			default:
				// proportionally
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['enclosure']['imageHeight'];
				break;
		}
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
		unset($ts['img.']['file.']['width.']);
		unset($ts['img.']['file.']['height.']);
		return $ts;
	}
}
?>
<?php

require_once(t3lib_extMgm::extPath('simplepie', 'Resources/Private/Libs/simplepie.inc'));
require_once('Zend/Http/Client.php');

Class Tx_Simplepie_Controller_RssController
	Extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	* @var Tx_Simplepie_Domain_Repository_RssconfigRepository
	*/
	Protected $rssConfigRepository;

	/**
	 * An instance of tslib_cObj
	 *
	 * @var        tslib_cObj
	 */
	protected $contentObject;

	var $thumbnailCachePath = 'typo3temp/simplepie_thumbnails/';

	Public Function initializeAction() {
		$this->rssConfigRepository =& t3lib_div::makeInstance ('Tx_Simplepie_Domain_Repository_RssConfigRepository');
		$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
	}

	Public Function indexAction() {
		if ($this->settings['type'] == 'ajax') {
			$this->jsonArray['content'] = $this->getAjaxContent();
			$content = json_encode($this->jsonArray);

			return $content;
		} else {
			$rssEntrys = $this->getAllFeedElements();

			$rssEntrysResult = array();
			$rssEntrysResult[] = $rssEntrys[0];

			//$newRssEntry = new Tx_Simplepie_Domain_Model_RssEntry();
			//$newRssEntry->setRsscontent($feedcontent);
			$this->view->assign('rssEntrys', $rssEntrysResult);
		}
	}

	Private function getAllFeedElements() {
		$rssEntrys = array();

		$feedurls = explode(',', $this->settings['feedSelection']);

		$itemcount = 0;
		foreach ($feedurls as $urlid) {
			$rssConfig = $this->rssConfigRepository->findByUid((int)$urlid);
			$feed = new SimplePie($rssConfig->getUrl());
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
			$this->view->assign('feedtitle', $feed->get_title() . ' - ' . $rssConfig->getUrl());

			foreach ($feed->get_items() as $item) {
				/* ueber Typoscript Variable setzen */
				$rssEntry = new Tx_Simplepie_Domain_Model_RssEntry();
				$rssEntry->setAuthor($item->get_author());
				$rssEntry->setTitle($item->get_title());
				$rssEntry->setDate($item->get_date());
				$rssEntry->setCopyright($item->get_copyright());
				$rssEntry->setDescription($item->get_description());
				$rssEntry->setPermalink($item->get_permalink());
				$rssEntry->setContent($item->get_content());
				$rssEntry->setTimestamp($item->get_date('U'));

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
				$rssEntry->setItems($feedItems);

				if (strlen($feed->get_image_url()) > 0) {
					$filename = $this->handleCacheImage($feed->get_image_url());
					$rssEntry->setFeedImageUrl($this->getResizedFeedImageLink($filename));
				}
				$rssEntry->setFeedTitle($feed->get_title());

				$rssEntrys[] = $rssEntry;
				$itemcount++;
			}
		}
		if ($this->settings['sorting'] == 'DESC') {
			usort($rssEntrys, array("Tx_Simplepie_Domain_Model_RssEntry", "compareDesc"));
		}
		if ($this->settings['sorting'] == 'ASC') {
			usort($rssEntrys, array("Tx_Simplepie_Domain_Model_RssEntry", "compareAsc"));
		}

		return $rssEntrys;
	}

	Private function getAjaxContent() {
		$nextItem = t3lib_div::GPvar('item');

		$rssEntrys = $this->getAllFeedElements();
		$entry = $rssEntrys[$nextItem];

		$content = '
			FeedTitle: ' . $entry->getFeedTitle() . '<br />
			FeedImage: <img src="' . $entry->getFeedImageUrl() . '"><br /><br />
			Title: ' . $entry->getTitle() . '<br />
			Link: ' . $entry->getPermalink() . '<br />
		';

		$this->view->assign('rssEntrys', array($entry));
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
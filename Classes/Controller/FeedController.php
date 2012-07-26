<?php

require_once(t3lib_extMgm::extPath('simplepie', 'Resources/Private/Libs/simplepie.php'));
require_once(t3lib_extMgm::extPath('simplepie', 'Classes/Controller/SimplePie_Sort.php'));
require_once(t3lib_extMgm::extPath('simplepie', 'Classes/Controller/FeedItemParser.php'));
//require_once('Zend/Http/Client.php');

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
		$this->initTyposcript();
		$this->prepareSettings();
	}

	Public Function indexAction() {
		$feedItems = $this->getFeedItems(false, 0, 0, 1);
		$this->view->assign('feedItems', $feedItems);
		$this->view->assign('pid', $GLOBALS['TSFE']->id);

		$this->setViewParameters();
	}

	Public Function ajaxAction() {
		$cObj = $this->request->getContentObjectData();
		if ($cObj['uid'] == t3lib_div::_GET('ajaxuid')) {
			$this->setViewParameters();
			$this->jsonArray['content'] = $this->getAjaxContent();
			$content = json_encode($this->jsonArray);
			print $content;
			exit;
		}
	}

	Private function getAllFeedItems() {
		return $this->getFeedItems(true);
	}

	Private function getFeedItems($disableItemCount = false, $elementfrom = 0, $elementcount = 0, $cache = 0) {
		$feedItems = array();
		$rawFeedItems = array();

		if ($this->settings['flexform']['controllers']['Feed']['itemsPerPage'] > 0 && $elementcount == 0) {
			$elementcount = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
		}

		$feedurls = explode(',', $this->settings['flexform']['controllers']['Feed']['feedSelection']);
		$itemsperfeed = explode(',', $this->settings['flexform']['controllers']['Feed']['itemsPerFeed']);
		$beginafteritem = explode(',', $this->settings['flexform']['controllers']['Feed']['beginAfterItem']);

		/*
		 * Load RawFeedItems
		 */
		$cObj = $this->request->getContentObjectData();
		$tempPath = 'typo3temp/simplepie_cache/';
		if (!is_dir(PATH_site . $tempPath)) {
			t3lib_div::mkdir(PATH_site . $tempPath);
		}
		$cacheFile = PATH_site . $tempPath . md5($cObj['uid']);
		
		$cacheDuration = 0;
		if (file_exists($cacheFile)) {
			$cacheDuration = time() - filemtime($cacheFile); // seconds;
		}

		$rawFeedItems = false;
		if ($cache == 1 && $this->settings['controllers']['Feed']['cacheDuration'] > 0 && $cacheDuration < $this->settings['controllers']['Feed']['cacheDuration']) {
			$rawFeedItems = @unserialize(@file_get_contents($cacheFile));
		}
		if (!$rawFeedItems) {
			$itemcount = 0;
			if ($feedurls[0] != '') {
				for ($i = 0; $i < count($feedurls); $i++ ) {
					$urlid = $feedurls[$i];
					$feedSource = $this->feedSourceRepository->findByUid((int)$urlid);

					if ($feedSource == null) {
						break;
					}
					$feed = new Tx_Simplepie_Controller_FeedController_SimplePie_Sort(
						$feedSource->getUrl(),
						$this->thumbnailCachePath,
						$this->settings['controllers']['Feed']['cacheDuration']
					);
					//$feed->enable_order_by_date(true);
					$feed->enable_order_by_date(false);
					// enable/disable caching
					if ($this->settings['controllers']['Feed']['cacheDuration'] > 0) {
						$feed->enable_cache(true);
					} else {
						$feed->enable_cache(false);
					}
					$feed->init();
					$feed->handle_content_type();

					if ($this->settings['flexform']['controllers']['Feed']['sorting'] == 'REVERSEFEED') {
						$rawitems = array_reverse($feed->get_items());
					} else {
						$rawitems = $feed->get_items();
					}

					$feeditemcount = 0;
					foreach ($rawitems as $item) {
						if (count($beginafteritem) > 1 && $feeditemcount < $beginafteritem[$i]) {
							$itemcount++;
							$feeditemcount++;
							continue;
						}
						if (!$disableItemCount && $i <= count($itemsperfeed) && $feeditemcount >= $itemsperfeed[$i] && $itemsperfeed[$i] > 0 ) {
							break;
						}
						//debug("Kategorie:");
						if ($this->settings['flexform']['controllers']['Feed']['filter'] != "") {
							foreach ($item->get_categories() as $category) {
								//debug($category);
								if ($category->get_label() == $this->settings['flexform']['controllers']['Feed']['filter']) {
									$rawFeedItems[] = $item;
									$itemcount++;
									$feeditemcount++;
									break;
								}
							}
						} else {
							$rawFeedItems[] = $item;
							$itemcount++;
							$feeditemcount++;
						}
					}
				}
			}

			/* RawFeedItems persistieren */
			$rawFeedItemsObject = serialize($rawFeedItems);
			if($f = @fopen($cacheFile, "w")) {
				if(@fwrite($f, $rawFeedItemsObject)) {
					@fclose($f);
				}
			}
		}

		$this->view->assign(
			'feed', array(
				'styleClass' => $this->settings['flexform']['controllers']['Feed']['listStyleClass'],
			//	'title' => $feed->get_title(),
			//	'source' => $feedSource->getUrl(),
				'sorting' => $this->settings['flexform']['controllers']['Feed']['sorting'],
			)
		);

		/* sorting */
		if ($this->settings['flexform']['controllers']['Feed']['sorting'] == 'DESC') {
			usort($rawFeedItems, array("Tx_Simplepie_Controller_FeedController_SimplePie_Sort", "compareDesc"));
		}
		if ($this->settings['flexform']['controllers']['Feed']['sorting'] == 'ASC') {
			usort($rawFeedItems, array("Tx_Simplepie_Controller_FeedController_SimplePie_Sort", "compareAsc"));
		}

		/* start after item check */
		if (count($beginafteritem) == 1 && $this->settings['flexform']['controllers']['Feed']['beginAfterItem'] > 0) {
			$rawFeedItems = array_slice($rawFeedItems, $this->settings['flexform']['controllers']['Feed']['beginAfterItem']);
		}

		/* max items check */
		if (!$disableItemCount && $elementcount > 0) {
			$page = t3lib_div::GPvar('item');
			$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
			$startitem = $page * $pageitems;
			if ($startitem >= count($rawFeedItems)) {
				$elementfrom = ($elementfrom % count($rawFeedItems));
			}
			$rawFeedItems = array_slice($rawFeedItems, $elementfrom, $elementcount);
		}

		/* item parsing */
		foreach($rawFeedItems as $item) {
			$itemParser = new Tx_Simplepie_Controller_FeedController_FeedItemParser();
			$feedItem = $itemParser->parseObject($item, $this->settings);

			// cache author thumbnail
			$author = $feedItem->getAuthor();
			if (isset($author['thumbnail']['src']) && strlen($author['thumbnail']['src']) > 0) {
				$filename = $this->handleCacheImage($author['thumbnail']['src']);
				$resizedItemAuthorImageSrc = $this->getResizedItemAuthorImageLink($filename);
				if (is_array($GLOBALS['TSFE']->lastImgResourceInfo) && $GLOBALS['TSFE']->lastImgResourceInfo[0] > 0) {
					$author['thumbnail']['src'] = $resizedItemAuthorImageSrc;
					$author['thumbnail']['width'] = $GLOBALS['TSFE']->lastImgResourceInfo[0];
					$author['thumbnail']['height'] = $GLOBALS['TSFE']->lastImgResourceInfo[1];
				}
				$feedItem->setAuthor($author);
			}

			// cache enclosures
			$enclosures = $feedItem->getEnclosures();
			for ($i=0; $i<count($enclosures); $i++) {
				if (isset($enclosures[$i]['thumbnail']['src']) && strlen($enclosures[$i]['thumbnail']['src']) > 0) {
					$filename = $this->handleCacheImage($enclosures[$i]['thumbnail']['src']);
					$enclosures[$i]['thumbnail']['src'] = $this->getResizedItemImageLink($filename, $feedItem->getType());
					if (is_array($GLOBALS['TSFE']->lastImgResourceInfo) && count($GLOBALS['TSFE']->lastImgResourceInfo) > 0) {
						$enclosures[$i]['thumbnail']['width'] = $GLOBALS['TSFE']->lastImgResourceInfo[0];
						$enclosures[$i]['thumbnail']['height'] = $GLOBALS['TSFE']->lastImgResourceInfo[1];
					}
				}
			}
			$feedItem->setEnclosures($enclosures);

			$feedItems[] = $feedItem;
		}
		return $feedItems;
	}

	Private function getAjaxContent() {
		$nextItem = t3lib_div::_GET('item');

		$items = array();
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'SINGLE') {
			$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
			$feedItems = $this->getFeedItems(false,$nextItem,1);
			$item = $feedItems[0];
			$items[] = $item;
		}

		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGING' 
			|| $this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGINGSLIDEEFFECTHORIZONTAL'
			|| $this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGINGSLIDEEFFECTVERTICAL') {
			$page = t3lib_div::GPvar('item');
			$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
			$startitem = $page * $pageitems;
			$items = $this->getFeedItems(false,$startitem,$pageitems);
		}
		$this->view->assign('feedItems', $items);
		return $this->view->render();
	}

	Private function handleCacheImage($imgUrl) {
		if (!file_exists($this->thumbnailCachePath)) {
			mkdir($this->thumbnailCachePath);
		}
		$parsedUrl = parse_url($imgUrl);
		//$client = new Zend_Http_Client($imgUrl, array('maxredirects' => 0,'timeout' => 30));
		$filename = $this->thumbnailCachePath . md5($imgUrl) . '.jpg';
		if (!getimagesize($filename)) {
			//$client->setStream($filename)->request('GET');
			$ch = curl_init($imgUrl);
			$fp = fopen($filename, "w");
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}
		return $filename;
	}

	Private function getResizedItemAuthorImageLink($filename, $type = 'default') {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		switch (strtolower($this->settings['feedItem']['author']['imageScaleMode'])) {
			case 'crop':
				$ts['img.']['file.']['height'] = $this->settings['feedItem']['author']['imageHeight'] . 'c';
				$ts['img.']['file.']['width'] = $this->settings['feedItem']['author']['imageWidth'] . 'c';
				break;
			case 'disproportionally':
				$ts['img.']['file.']['minH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['minW'] = $this->settings['feedItem']['author']['imageWidth'];
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['author']['imageWidth'];
				break;
			default:
				// proportionally
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['author']['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['author']['imageWidth'];
				break;
		}
		$img = $this->contentObject->IMG_RESOURCE($ts['img.']);
		return $img;
	}

	Private function getResizedItemImageLink($filename, $type = 'default') {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		switch (strtolower($this->settings['feedItem']['enclosure'][$type]['imageScaleMode'])) {
			case 'crop':
				$ts['img.']['file.']['height'] = $this->settings['feedItem']['enclosure'][$type]['imageHeight'] . 'c';
				$ts['img.']['file.']['width'] = $this->settings['feedItem']['enclosure'][$type]['imageWidth'] . 'c';
				break;
			case 'disproportionally':
				$ts['img.']['file.']['minH'] = $this->settings['feedItem']['enclosure'][$type]['imageHeight'];
				$ts['img.']['file.']['minW'] = $this->settings['feedItem']['enclosure'][$type]['imageWidth'];
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['enclosure'][$type]['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['enclosure'][$type]['imageWidth'];
				break;
			default:
				// proportionally
				$ts['img.']['file.']['maxH'] = $this->settings['feedItem']['enclosure'][$type]['imageHeight'];
				$ts['img.']['file.']['maxW'] = $this->settings['feedItem']['enclosure'][$type]['imageWidth'];
				break;
		}
		$img = $this->contentObject->IMG_RESOURCE($ts['img.']);
		return $img;
	}

	Private function getResizedFeedImageLink($filename, $type = 'default') {
		$ts = $this->getImageTS();
		$ts['img.']['file'] = $filename;
		$ts['img.']['file.']['maxH'] = $this->settings['flexform']['controllers']['Feed']['feedImageHeight'];
		$ts['img.']['file.']['maxW'] = $this->settings['flexform']['controllers']['Feed']['feedImageWidth'];
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

	Private Function initTyposcript() {
		$flexformTyposcript = $this->settings['flexform']['controllers']['Feed']['tsconfig'];
		if ($flexformTyposcript) {
			require_once(PATH_t3lib . 'class.t3lib_tsparser.php');
			$tsparser = t3lib_div::makeInstance('t3lib_tsparser');
			$typoscriptextbase = t3lib_div::makeInstance('Tx_Extbase_Utility_TypoScript');
			// Copy conf into existing setup
			//$tsparser->setup = $this->settings;
			// Parse the new Typoscript
			$tsparser->parse($flexformTyposcript);
			// Copy the resulting setup back into conf
			$settings = $tsparser->setup;

			$this->settings = $this->convertTypoScriptArrayToPlainArray($settings, $this->settings);
		}
	}

	Private Function convertTypoScriptArrayToPlainArray(array $settings, array $globalSettings) {
		foreach ($settings as $key => &$value) {
			if (substr($key, -1) === '.') {
				$keyWithoutDot = substr($key, 0, -1);
				if (is_array($value) && is_array($globalSettings[$keyWithoutDot])) {
					$globalSettings[$keyWithoutDot] = self::convertTypoScriptArrayToPlainArray($value, $globalSettings[$keyWithoutDot]);
				}
			} else {
				$globalSettings[$key] = $value;
			}
		}
		return $globalSettings;
	}

	Private function prepareSettings() {
		if (strlen($this->settings['controllers']['Feed']['sorting']) > 0 && $this->settings['flexform']['controllers']['Feed']['sorting'] == 'DEFAULT') {
			$this->settings['flexform']['controllers']['Feed']['sorting'] = $this->settings['controllers']['Feed']['sorting'];
		}
		if ($this->settings['controllers']['Feed']['itemsPerPage'] > 0 && strlen($this->settings['flexform']['controllers']['Feed']['itemsPerPage']) == 0) {
			$this->settings['flexform']['controllers']['Feed']['itemsPerPage'] = $this->settings['controllers']['Feed']['itemsPerPage'];
		}
		if ($this->settings['flexform']['controllers']['Feed']['cacheDuration'] > 0) {
			$this->settings['controllers']['Feed']['cacheDuration'] = $this->settings['flexform']['controllers']['Feed']['cacheDuration'];
		}
		if (strlen($this->settings['flexform']['controllers']['Feed']['listStyleClass']) > 0) {
			$this->settings['flexform']['controllers']['Feed']['listStyleClass'] = trim($this->settings['flexform']['controllers']['Feed']['listStyleClass']);
		}
		if (strlen($this->settings['flexform']['controllers']['Feed']['feedItem']['linkTarget']) < 1) {
			$this->settings['flexform']['controllers']['Feed']['feedItem']['linkTarget'] = '_self';
		}
	}

	Private function setViewParameters() {
		$cObj = $this->request->getContentObjectData();
		$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
		$this->view->assign('ajaxuid', $cObj['uid']);
		$this->view->assign('ajaxPageType', $this->settings['ajaxPageType']);
		$this->view->assign('jQueryDisable', $this->settings['jQueryDisable']);
		// Ajax disabled by default
		$ajaxMode = 0;
		$startnextitem = 0;
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'SINGLE') {
			$ajaxMode = 1;
			$startnextitem = $pageitems - 1;
		}
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGING') {
			$ajaxMode = 2;
			$startnextitem = 0;
		}
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGINGSLIDEEFFECTHORIZONTAL') {
			$ajaxMode = 3;
			$startnextitem = 0;
			$styleOuterContainer = "position:relative;overflow:hidden;width: 540px;height: 225px;";
			$styleInnerContainer = "width:20000em;position:absolute;";
			$styleItems = "float:left;";
		}
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGINGSLIDEEFFECTVERTICAL') {
			$ajaxMode = 3;
			$startnextitem = 0;
			$styleOuterContainer = "position:relative;overflow:hidden;width: 540px;height: 450px;";
			$styleInnerContainer = "height:20000em;position:absolute;";
			$vertical = '{ vertical: true }';
		}

		if ($this->settings['jQueryEffect'] == 'NONE') {
			$jQueryEffect = 0;
		}
		if ($this->settings['jQueryEffect'] == 'SLIDEHORIZONTAL') {
			$jQueryEffect = 1;
		}
		$this->view->assign('jQueryEffect', $jQueryEffect);
		$this->settings['controllers']['Feed']['ajaxMode'] = $ajaxMode;
		$this->view->assign('pageitems', $pageitems);
		$this->view->assign('startnextitem', $startnextitem);
		$this->view->assign('pid', $GLOBALS['TSFE']->id);
		$this->view->assign('settings', $this->settings);
		$this->view->assign('vertical', $vertical);
		$this->view->assign('styleOuterContainer', $styleOuterContainer);
		$this->view->assign('styleInnerContainer', $styleInnerContainer);
		$this->view->assign('styleItems', $styleItems);
	}
}
?>
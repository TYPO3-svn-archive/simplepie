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
		$this->prepareSettings();
	}

	Public Function indexAction() {
		if ($this->settings['jQueryDisable'] == 0) {
			$GLOBALS['TSFE']->additionalHeaderData['multicontent'] .= $this->javascriptInclude();
		}
		
		$cObj = $this->request->getContentObjectData();
		$feedItems = $this->getFeedItems();
		$this->view->assign('feedItems', $feedItems);
		$this->view->assign('pid', $GLOBALS['TSFE']->id);
		//$this->view->assign('ajaxuid', $this->settings['ajaxUid']);
		$this->view->assign('ajaxuid', $cObj['uid']);
		$this->view->assign('jQueryDisable', $this->settings['jQueryDisable']);
		$this->view->assign('fluidTest', 'hello<br /> w<b>o</b>rld!');
		//print "UID:" . print_r($this->settings['tuid'], true);
		//print($cObj['uid']);		
	}

	Public Function ajaxAction() {
		$cObj = $this->request->getContentObjectData();
		if ($cObj['uid'] == t3lib_div::_GET('ajaxuid')) {
			$this->jsonArray['content'] = $this->getAjaxContent();
			$content = json_encode($this->jsonArray);
			print $content;
			exit;
		}
	}

	Private function getAllFeedItems() {
		return $this->getFeedItems(true);
	}

	Private function getFeedItems($disableItemCount = false, $elementfrom = 0, $elementcount = 0) {
		$feedItems = array();
		$rawFeedItems = array();

		if ($this->settings['flexform']['controllers']['Feed']['itemsPerPage'] > 0 && $elementcount == 0)
			$elementcount = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];

		$feedurls = explode(',', $this->settings['flexform']['controllers']['Feed']['feedSelection']);
		$itemsperfeed = explode(',', $this->settings['flexform']['controllers']['Feed']['itemsPerFeed']);
		$beginafteritem = explode(',', $this->settings['flexform']['controllers']['Feed']['beginAfterItem']);

		$itemcount = 0;
		if ($feedurls[0] != '') {
			for ($i = 0; $i < count($feedurls); $i++ ) {
				$urlid = $feedurls[$i];
				$feedSource = $this->feedSourceRepository->findByUid((int)$urlid);

				if ($feedSource == null) {
					break;
				}
				$feed = new Tx_Simplepie_Controller_FeedController_SimplePie_Sort($feedSource->getUrl());
				//$feed->enable_order_by_date(true);
				$feed->enable_order_by_date(false);
				// enable/disable caching
				if ($this->settings['flexform']['controllers']['Feed']['cacheDuration'] > 0) {
					$feed->set_cache_location('typo3temp/simplepie_thumbnails/');
					$feed->set_cache_duration($this->settings['flexform']['controllers']['Feed']['cacheDuration']);
					$feed->enable_cache(true);
				} else {
					$feed->enable_cache(false);
				}
				$feed->init();
				$feed->handle_content_type();
				$this->view->assign(
					'feed', array(
						'styleClass' => $this->settings['flexform']['controllers']['Feed']['listStyleClass'],
						'title' => $feed->get_title(),
						'source' => $feedSource->getUrl(),
						'sorting' => $this->settings['flexform']['controllers']['Feed']['sorting'],
					)
				);

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

					$rawFeedItems[] = $item;
					$itemcount++;
					$feeditemcount++;
				}
			}
		}

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
				$author['thumbnail']['src'] = $this->getResizedItemAuthorImageLink($filename);
				if (is_array($GLOBALS['TSFE']->lastImgResourceInfo) && count($GLOBALS['TSFE']->lastImgResourceInfo) > 0) {
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

		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGING') {
			$page = t3lib_div::GPvar('item');
			$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
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
	
	Private function prepareSettings() {
		if (strlen($this->settings['controllers']['Feed']['sorting']) > 0 && $this->settings['flexform']['controllers']['Feed']['sorting'] == 'DEFAULT') {
			$this->settings['flexform']['controllers']['Feed']['sorting'] = $this->settings['controllers']['Feed']['sorting'];
		}
		if ($this->settings['controllers']['Feed']['itemsPerPage'] > 0 && strlen($this->settings['flexform']['controllers']['Feed']['itemsPerPage']) == 0) {
			$this->settings['flexform']['controllers']['Feed']['itemsPerPage'] = $this->settings['controllers']['Feed']['itemsPerPage'];
		}
		if ($this->settings['controllers']['Feed']['cacheDuration'] > 0 && strlen($this->settings['flexform']['controllers']['Feed']['cacheDuration']) == 0) {
			$this->settings['flexform']['controllers']['Feed']['cacheDuration'] = $this->settings['controllers']['Feed']['cacheDuration'];
		}
		if (strlen($this->settings['flexform']['controllers']['Feed']['listStyleClass']) > 0) {
			$this->settings['flexform']['controllers']['Feed']['listStyleClass'] = trim($this->settings['flexform']['controllers']['Feed']['listStyleClass']);
		}
		if (strlen($this->settings['flexform']['controllers']['Feed']['feedItem']['linkTarget']) < 1) {
			$this->settings['flexform']['controllers']['Feed']['feedItem']['linkTarget'] = '_self';
		}
	}
	
	Private function javascriptInclude() {
		$cObj = $this->request->getContentObjectData();
		$pageitems = $this->settings['flexform']['controllers']['Feed']['itemsPerPage'];
		$ajaxuid = $cObj['uid'];
		//$ajaxuid = $this->settings['ajaxUid'];
		$pid = $GLOBALS['TSFE']->id;
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'SINGLE') {
			$jsnavfunc = '
				jQuery(".simplepie_ajax_next'. $ajaxuid . '").click(function(){
					if (dir'. $ajaxuid . ' == "prev") {
						nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' + pageitems'. $ajaxuid . ';
					} else {
						nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' + 1;
					}
					dir'. $ajaxuid . ' = "next";
					getNextSingleItem'. $ajaxuid . '();
				});
				jQuery(".simplepie_ajax_prev'. $ajaxuid . '").click(function(){
					if (dir'. $ajaxuid . ' == "next") {
						nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' - pageitems'. $ajaxuid . ';
					}
					else {
						nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' - 1;
					}
					dir'. $ajaxuid . ' = "prev";
					getPrevSingleItem'. $ajaxuid . '();
				});';
			$startnextitem = $pageitems - 1;
		}
		if ($this->settings['flexform']['controllers']['Feed']['ajaxMode'] == 'PAGING') {
			$jsnavfunc = '
				jQuery(".simplepie_ajax_next'. $ajaxuid . '").click(function(){
					nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' + 1;
					getItem'. $ajaxuid . '();
				});
				jQuery(".simplepie_ajax_prev'. $ajaxuid . '").click(function(){
					nextItem'. $ajaxuid . ' = nextItem'. $ajaxuid . ' - 1;
					getItem'. $ajaxuid . '();
				});';
			$startnextitem = 0;
		}
		$jscontent = '<script type="text/javascript">
			var dir'. $ajaxuid . ' = "next";
			var pageitems'. $ajaxuid . ' = ' . $pageitems . ';
			var nextItem'. $ajaxuid . ' = ' . $startnextitem . ';
			jQuery(document).ready(function(){
				// click on next/prev link
				' . $jsnavfunc . '
			});

			function getItem'. $ajaxuid . '() {
				jQuery(".simplepie_ajax_loading'. $ajaxuid . '").fadeIn();
				jQuery.ajax({
					url: "index.php",
					processData: "false",
					data: "id=' . $pid . '&type=4711&item=" + nextItem'. $ajaxuid . ' + "&no_cache=1&tx_simplepie_pi1[action]=ajax&tx_simplepie_pi1[controller]=Feed&ajaxuid='. $ajaxuid . '",
					dataType: "json",
					success: function(ret){
						// place new content
						jQuery(".simplepie_ajax_content'. $ajaxuid . '").html(ret.content);
					}
				});
				jQuery(".simplepie_ajax_loading'. $ajaxuid . '").fadeOut();
			}
			
			function getPrevSingleItem'. $ajaxuid . '() {
				jQuery.ajax({
					url: "index.php",
					processData: "false",
					data: "id=' . $pid . '&type=4711&item=" + nextItem'. $ajaxuid . ' +"&no_cache=1&tx_simplepie_pi1[action]=ajax&tx_simplepie_pi1[controller]=Feed&ajaxuid='. $ajaxuid . '",
					dataType: "json",
					success: function(ret){
						// place new content
						// jQuery(".simplepie_ajax_content'. $ajaxuid . '").html(ret.content);
						var $elf = jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:first");
						//var $ell = jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:last");
						jQuery(".simplepie_ajax_content'. $ajaxuid . '").prepend(ret.content);
						jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:last").remove();
						//jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:first").replaceWith($ell);
					}
				});
			}
			
			function getNextSingleItem'. $ajaxuid . '() {
				jQuery.ajax({
					url: "index.php",
					processData: "false",
					data: "id=' . $pid . '&type=4711&item=" + nextItem'. $ajaxuid . ' +"&no_cache=1&tx_simplepie_pi1[action]=ajax&tx_simplepie_pi1[controller]=Feed&ajaxuid='. $ajaxuid . '",
					dataType: "json",
					success: function(ret){
						var $elf = jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:last");
						jQuery(".simplepie_ajax_content'. $ajaxuid . '").append(ret.content);
						jQuery(".simplepie_ajax_content'. $ajaxuid . '").find("div:first").remove();
					}
				});
			}
			</script>';
			return $jscontent;
	}
}
?>
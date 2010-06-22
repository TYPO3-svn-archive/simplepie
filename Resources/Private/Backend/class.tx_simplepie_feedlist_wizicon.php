<?php
/**
 * Class that adds an entry to the "create new contentelement" wizard.
 */

/**
 * Class that adds the wizard icon.
 */
class tx_simplepie_feedlist_wizicon {

	/**
	 * Adds the newloginbox wizard icon
	 *
	 * @param	array		Input array with wizard items for plugins
	 * @return	array		Modified input array, having the item for newloginbox added.
	 */
	function proc($wizardItems) {
		global $LANG;

		// Get locallang values
		$LL = $this->includeLocalLang();
		
		// Set wizard item
		$wizardItems['plugins_tx_vpswfcontent_piswfheadline'] = array(
			'icon'			=> t3lib_extMgm::extRelPath('simplepie') . 'Resources/Private/Backend/icon_tx_simplepie_feedlist_wizicon.gif',
			'title'			=> $LANG->getLLL('feedlist.cewiz.title', $LL),
			'description'	=> $LANG->getLLL('feedlist.cewiz.description', $LL),
			'params'		=> '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=simplepie_pi1'
		);

		return $wizardItems;
	}

	/**
	 * Includes the locallang file for the 'simplepie' extension
	 *
	 * @return	array		The LOCAL_LANG array
	 */
	function includeLocalLang() {
		$llFile = t3lib_extMgm::extPath('simplepie') . 'Resources/Private/Language/locallang_db.xml';
		$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
		return $LOCAL_LANG;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplepie/Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/simplepie/Resources/Private/Backend/class.tx_simplepie_feedlist_wizicon.php']);
}

?>
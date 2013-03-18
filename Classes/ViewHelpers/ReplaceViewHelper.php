<?php

/**
 * Returns the length of the given string
 */
class Tx_Simplepie_ViewHelpers_ReplaceViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Returns the length of the given string
	 *
	 * @param string $originalString 
         * @param string $replaceString
	 * @return string Rendered result
	 */
	public function render($originalString, $replaceString) {
		$stringToFormat = $this->renderChildren();
                $output = str_replace($originalString, $replaceString, $stringToFormat);
                //print "TEST" . $output;
                return $output;
	}
}

?>
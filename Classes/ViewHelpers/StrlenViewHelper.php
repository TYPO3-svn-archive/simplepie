<?php

/**
 * Returns the length of the given string
 */
class Tx_Simplepie_ViewHelpers_StrlenViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Returns the length of the given string
	 *
	 * @param string $subject The string being measured for length
	 * @return string Rendered result
	 */
	public function render($subject) {
		return strlen($subject);
	}
}

?>
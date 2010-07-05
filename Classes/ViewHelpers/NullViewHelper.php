<?php

/**
 * View helper which return input as it is
 *
 * = Examples =
 *
 * <f:null>{anyString}</f:null>
 *
 */
class Tx_Simplepie_ViewHelpers_NullViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	protected $objectAccessorPostProcessorEnabled = FALSE;

	/**
	 * Render without processing
	 *
	 * @param string $content the output
	 * @return string
	 */
	public function render($content = NULL) {

		if ($content === NULL) {
			$content = html_entity_decode($this->renderChildren());
		}

		return $content;
	}
}

?>
<?php

/**
 * View helper which return nothing
 * Use it to to comment out code
 *
 * = Examples =
 *
 * <ns:comment>{anyString}</ns:comment>
 *
 */
class Tx_Simplepie_ViewHelpers_CommentViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	protected $objectAccessorPostProcessorEnabled = FALSE;

	/**
	 * @param boolean $debug If set to 'true' output is rendered
	 * @return string Rendered result
	 */
	public function render($debug = FALSE) {

		if ($debug == TRUE) {
			$content = $this->renderChildren();
			return $content;
		}

		return '';
	}
}

?>
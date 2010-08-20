<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Embed JavaScript view helper.
 * 
 * backported from http://forge.typo3.org/attachments/3302/3677_v1.patch
 *
 * @package Fluid
 * @subpackage ViewHelpers\Embed
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Simplepie_ViewHelpers_Embed_JavaScriptViewHelper extends Tx_Fluid_Core_ViewHelper_TagBasedViewHelper {
	/**
	 * @var string
	 */
	protected $tagName = 'script';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('charset', 'string', 'Specifies the character encoding used in an external script file');
		$this->registerTagAttribute('defer', 'boolean', 'Specifies that the execution of a script should be deferred (delayed) until after the page has been loaded');
		$this->registerTagAttribute('xml:space', 'string', 'Specifies whether whitespace in code should be preserved');
	}

	/**
	 * Render the link.
	 *
	 * @param string $src Specifies the URL of an external script file
	 * @param string $type Specifies the MIME type of a script. Usually "javascript/text" for JavaScript
	 * @return string The rendered script tag
	 * @author Steffen Ritter <info@rs-websystems.de>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render($src = NULL, $type = 'text/javascript') {
		$content = $this->renderChildren();
		if ($content !== NULL) {
			if ($src !== NULL) {
				throw new Tx_Fluid_Core_ViewHelper_Exception('It\'s not allowed to set src Attribute for an embed.javaScript ViewHelper that contains inline scripts' , 1277293873);
			}
			$content = '/*<![CDATA[*/' . $content . '/*]]>*/';
			$this->tag->setContent($content);
		} elseif ($src !== NULL) {
			$this->tag->addAttribute('src', $src);
		}
		$this->tag->forceClosingTag(TRUE);
		$this->tag->addAttribute('type', $type);

		return $this->tag->render();
	}
}

?>
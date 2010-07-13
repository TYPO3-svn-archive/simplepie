<?php
/**
 * Formats a timeframe
 *
 * Smarty function timeframe_format ported to Fluid
 * http://pear.11abacus.com/dev/Smarty/libs/plugins/function.timeframe_format.phps
 *
 * Format: fractions of timeframe
 * <ul>
 *  <li>%s :seconds (0-59)</li>
 *  <li>%m :minutes (0-59)</li>
 *  <li>%h :hours (0-23)</li>
 *  <li>%d :week days (0-6)</li>
 *  <li>%j :year days (0-365)</li>
 *  <li>%w :weeks (0-51)</li>
 *  <li>%y :years</li>
 * </ul>
 *
 * Format: timeframe in units
 * <ul>
 *  <li>%S :seconds</li>
 *  <li>%M :minutes</li>
 *  <li>%H :hours</li>
 *  <li>%D :days</li>
 *  <li>%W :weeks</li>
 *  <li>%Y :years</li>
 * </ul>
 *
 * For format uses the following syntax:
 * <ul>
 *  <li>() to mark conditional part of the format. The part will only be
 *         displayed if the first date value found inside is not zero</li>
 *  <li>[] to mark conditional and default part of the format. The part
 *         will be displayed if none of the other () conditional parts are
 *         displayed.</li>
 * </ul>
 *
 * Use backslash (\) to escape () [], %, @ and |
 *
 * Noun declination i.e. "0 years" vs. "1 year" vs. "2 years".
 * To decline nouns according to the value for the date part, start with
 * a @ followed by the name of the date part to work with, followed by |.
 * You then add the declinations using the format:
 * part value ":" declination "|"
 *
 * The last declination listed is used by default.
 *
 * For instance the 2 formats below are equivalent:
 * <ul>
 *  <li>%Y @Y|0:years|1:year|years|</li>
 *  <li>%Y @Y|1:year|years|</li>
 * </ul>
 *
 * For timeframes in units, you can specify a decimal precision using the
 * sprintf style. i.e. %.1H for total hours with 1 decimal. You can use
 * the decimal precision with the declination as well: @.1H
 *
 * Examples of format (first example is the default value):
 * <ul>
 *  <li>(%y @y|1:year|years| )(%w @w|1:week|weeks| )(%d @d|1:day|days| )(%h @h|1:hour|hours| )(%m @m|1:minute|minutes| )(%s @s|1:second|seconds|)[%s @s|1:second|seconds|]</li>
 *  <li>(%y year(s)) (%w week(s)) (%d day(s)) (%h hour(s)) (%m minute(s)) (%s second(s))[%s second(s)]</li>
 *  <li>(%d day\(s\))[Today in %h hour(s)]</li>
 *  <li>%.0D::%h::%m::%s</li>
 * </ul>
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.timeframe>142</f:format.timeframe>
 * </code>
 *
 * Output:
 * 2 minutes 22 seconds
 *
 * <code title="With all parameters">
 * <f:format.number decimals="1" decimalSeparator="," thousandsSeparator=".">423423.234</f:format.number>
 * </code>
 *
 * Output:
 * 423.423,2
 *
 * @package Fluid
 * @subpackage ViewHelpers\Format
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Simplepie_ViewHelpers_Format_TimeframeViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Formats an ammount of seconds with custom timeframe formats
	 *
	 * @param integer $precision How many most important date parts to display (this only applies to lowercase placeholders, and when conditional () formatting is used.)
	 * @param string $format How to format the string
	 * @return string The formatted timeframe
	 * @author Georg Leitner <georg.leitner@wien.oevp.at>
	 * @author Philippe Jausions <jausions@php.net>
	 * @api
	 */
	public function render($precision = 3, $format = '') {
		$seconds = $this->renderChildren();

		// check parameters
		$precision = round($precision);
		if ($precision < 1 && $precision > 6) {
			$precision = 3;
		}

		// Default format
		if ($format == '') {
			$format = '(%y @y|1:year|years| )(%w @w|1:week|weeks| )(%d @d|1:day|days| )(%h @h|1:hour|hours| )(%m @m|1:minute|minutes| )(%s @s|1:second|seconds|)[%s @s|1:second|seconds|]';
		}

		$parts['SECONDS']	= $seconds;
		$parts['MINUTES']	= $seconds / 60;
		$parts['HOURS']		= $seconds / 3600;
		$parts['DAYS']		= $seconds / 86400;
		$parts['WEEKS']		= $seconds / 604800;
		$parts['YEARS']		= $seconds / 31557600;

		$parts['seconds']	= $seconds % 60;
		$parts['minutes']	= floor(($seconds % 3600) / 60);
		$parts['hours']		= floor(($seconds % 86400) / 3600);
		$parts['days']		= floor(($seconds % 604800) / 86400);
		$parts['weeks']		= floor(($seconds % 31557600) / 604800);
		$parts['ydays']		= $parts['days'] + $parts['weeks'] * 7;
		$parts['years']		= floor($parts['YEARS']);

		$p = array(
				'%S' => 'SECONDS',
				'%M' => 'MINUTES',
				'%H' => 'HOURS',
				'%D' => 'DAYS',
				'%W' => 'WEEKS',
				'%Y' => 'YEARS',
				'%s' => 'seconds',
				'%m' => 'minutes',
				'%h' => 'hours',
				'%d' => 'days',
				'%w' => 'weeks',
				'%j' => 'ydays',
				'%y' => 'years',
		);

		// Only keep the most important date parts, per the "precision" parameter
		$partImportance = array('%y', '%w', '%d', '%h', '%m', '%s');

		// Find the first non-zero date part
		$i = 0;
		foreach ($partImportance as $i => $part) {
			if ($parts[$p[$part]] != 0) {
				break;
			}
		}
		// Keep "precision" date parts, and zero the remaining ones
		$i += $precision;
		for (; $i < 6; ++$i) {
			$parts[$p[$partImportance[$i]]] = 0;
		}

		// Parsing variables
		$output			= '';
		$var			= '';
		$inDeclination	= false; // Parsing noun declination options
		$inConditional	= false; // Parsing conditional format fragment
		$inIfNothing	= false; // Parsing the conditional fragment that would
								 // be displayed if all the other conditional
								 // fragments wouldn't otherwise.
		$inVar			= false; // Parsing a "variable"
		$inVarPrecision	= false; // Parsing a variable's precision
		$length			= strlen($format);
		$specials		= '([\\%@';
		$vars			= 'sSmMhHdDjwWyY';
		$markers		= array();
		$marker			= null;

		$debug = '';

		// Parse the format
		for ($i = 0; $i < $length; ++$i) {
			$char = $format[$i];

			// Currently parsing variable?
			if ($inVar) {
				$var .= $char;

				// Getting decimal precision?
				if ($inVarPrecision && is_numeric($char)) {
					continue;
				}
				// Allow the dot (.) if it hasn't already been parsed
				$_vars = $vars.(($inVarPrecision) ? '' : '.');

				// Expected decimal point or value type?
				if (strpos($_vars, $char) === false) {
					if ($inConditional || $inIfNothing) {
						$markers[$marker]['output'] .= $var;
					} else {
						$output .= $var;
					}
					$var = '';
					$inVar = false;
					$inVarPrecision = false;
					$inDeclination = false;
					continue;
				}
				if ($char == '.') {
					$inVarPrecision = true;
					continue;
				}
				// Get the value
				$f = '%'.substr($var, 1, -1).(($inVarPrecision) ? 'f' : 'd');
				$varValue = sprintf($f, $parts[$p['%'.$char]]);

				// Look for the declination matching the value, and use it
				// in place
				if ($inDeclination) {

					$_found = false;
					$text = '';
					if ($i + 3 < $length && $format[$i + 1] == '|') {
						$value = '';
						$inText = false;
						for ($k = $i + 2; $k < $length; ++$k) {
							$char = $format[$k];

							switch ($char) {
								case '|':
									if ($inText) {
										if ($value == abs($varValue)) {
											$_found = true;
											$i = $k;
										}
										$inText = false;
										$value = '';
									} else {
										// We found the last default declination
										// Use that text, if we didn't get a better
										// match before.
										if (!$_found) {
											$text = $value;
										}
										$_found = true;
										$i = $k;
										break 2;
									}
									break;
								case ':':
									if ($value == '') {
										break 2;
									}
									$inText = true;
									if (!$_found) {
										$text = '';
									}
									break;
								case '\\':
									$char = $format[++$k];
									// No BREAK here. This is intentional!
								default:
									if (!$_found) {
										if ($inText) {
											$text .= $char;
										} else {
											$value .= $char;
										}
									}
							}
						}
					}
					if (!$_found) {
						$varValue = $var;
					} else {
						$varValue = $text;
					}
				}

				// Place value in output
				if ($inConditional || $inIfNothing) {
					$markers[$marker]['output'] .= $varValue;
					// The first value found determines if the conditional
					// format will be output
					if ($markers[$marker]['condition'] == -1
						&& !$inDeclination) {
						if ($varValue) {
							$markers[$marker]['condition'] = 1;
						} else {
							$markers[$marker]['condition'] = 0;
						}
					}
				} else {
					$output .= $varValue;
				}
				$inVar = false;
				$inVarPrecision = false;
				$inDeclination = false;
				$var = '';
				continue;
			}

			// Special character at end of format?
			if (strpos($specials, $char) !== false) {
				if ($i + 1 == $length) {
					$output .= $char;
					break;
				}
			}

			switch ($char) {
				case '(':
				case '[':
					if ($inConditional || $inIfNothing) {
						break;
					}
					if ($char == '(') {
						$inConditional = true;
					} else {
						$inIfNothing = true;
					}
					$marker = count($markers);
					$markers[] = array(
									'position' => strlen($output),
									'type' => $char,
									'condition' => -1,
									'output' => ''
									);
					break;

				case ')':
				case ']':
					if ($inConditional) {
						$inConditional = false;
						$markers[$marker]['output'] .= $char;
						$char = '';
					} elseif ($inIfNothing) {
						$inIfNothing = false;
						$markers[$marker]['output'] .= $char;
						$char = '';
					}
					break;

				case '@':
					$inDeclination = true;
					// No BREAK here. This is intentional
				case '%':
					$inVar = true;
					$inVarPrecision = false;
					$var = $char;
					continue 2;

				case '\\':
					$char = $format[++$i];
					break;
			}
			if ($inConditional || $inIfNothing) {
				$markers[$marker]['output'] .= $char;
			} else {
				$output .= $char;
			}
		}

		// Insert the conditional strings back into the output
		$condition = false;
		$count = count($markers);
		for ($marker = 0; $marker < $count; ++$marker) {
			$data = $markers[$marker];

			// We'll take care of the "if nothing" later below
			if ($data['type'] == '[') {
				continue;
			}

			if ($data['condition'] == 0) {
				unset($markers[$marker]);
				continue;
			}
			if ($data['condition'] == 1) {
				// Trim brackets [] ()
				$data['output'] = substr($data['output'], 1, -1);
				// We have at least one conditional fragment met
				// so ifNothing are not needed
				$condition = true;
			}

			$output = substr_replace($output, $data['output'], $data['position'], 0);
			$offset = strlen($data['output']);
			unset($markers[$marker]);

			// We need to adjust position due to the "ifNothing" that
			// we will revisit later below
			for ($i = $marker + 1; $i < $count; ++$i) {
				$markers[$i]['position'] += $offset;
			}
		}

		// At last, if none of the conditional fragments were added,
		// insert the ifNothing blocks
		if (!$condition) {
			foreach ($markers as $marker => $data) {
				$data['output'] = substr($data['output'], 1, -1);
				$output = substr_replace($output, $data['output'],
										 $data['position'], 0);
				$offset = strlen($data['output']);
				for ($i = $marker + 1; $i < $count; ++$i) {
					if (isset($markers[$i])) {
						$markers[$i]['position'] += $offset;
					}
				}
			}
		}

		return $output;
	}
}

?>
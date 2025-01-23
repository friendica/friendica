<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Friendica\DI;
use SimpleXMLElement;

/**
 * This class contain methods to work with XML data
 */
class XML
{
	/**
	 * Creates an XML structure out of a given array
	 *
	 * @param array       $array         The array of the XML structure that will be generated
	 * @param object|null $xml           The created XML will be returned by reference
	 * @param bool        $remove_header Should the XML header be removed or not?
	 * @param array       $namespaces    List of namespaces
	 * @param bool        $root          interally used parameter. Mustn't be used from outside.
	 * @return string
	 * @throws \Exception
	 */
	public static function fromArray(array $array, object &$xml = null, bool $remove_header = false, array $namespaces = [], bool $root = true): string
	{
		if ($root) {
			foreach ($array as $key => $value) {
				foreach ($namespaces as $nskey => $nsvalue) {
					$key .= ' xmlns' . ($nskey == '' ? '' : ':') . $nskey . '="' . $nsvalue . '"';
				}

				if (is_array($value)) {
					$root = new SimpleXMLElement('<' . $key . '/>');
					self::fromArray($value, $root, $remove_header, $namespaces, false);
				} else {
					$root = new SimpleXMLElement('<' . $key . '>' . self::escape($value ?? '') . '</' . $key . '>');
				}

				$dom               = dom_import_simplexml($root)->ownerDocument;
				$dom->formatOutput = true;
				$xml               = $dom;

				$xml_text = $dom->saveXML();

				if ($remove_header) {
					$xml_text = trim(substr($xml_text, 21));
				}

				return $xml_text;
			}
		}

		$element = null;
		foreach ($array as $key => $value) {
			if (!isset($element) && isset($xml)) {
				$element = $xml;
			}

			if (is_integer($key)) {
				if (isset($element)) {
					if (is_scalar($value)) {
						$element[0] = $value;
					} else {
						/// @todo: handle nested array values
					}
				}
				continue;
			}

			$element_parts = explode(':', $key);
			if ((count($element_parts) > 1) && isset($namespaces[$element_parts[0]])) {
				$namespace = $namespaces[$element_parts[0]];
			} elseif (isset($namespaces[''])) {
				$namespace = $namespaces[''];
			} else {
				$namespace = null;
			}

			// Remove undefined namespaces from the key
			if ((count($element_parts) > 1) && is_null($namespace)) {
				$key = $element_parts[1];
			}

			if (substr($key, 0, 11) == '@attributes') {
				if (!isset($element) || !is_array($value)) {
					continue;
				}

				foreach ($value as $attr_key => $attr_value) {
					$element_parts = explode(':', $attr_key);
					if ((count($element_parts) > 1) && isset($namespaces[$element_parts[0]])) {
						$namespace = $namespaces[$element_parts[0]];
					} else {
						$namespace = null;
					}

					$element->addAttribute($attr_key, $attr_value ?? '', $namespace);
				}

				continue;
			}

			if (!is_array($value)) {
				$element = $xml->addChild($key, self::escape($value ?? ''), $namespace);
			} else {
				$element = $xml->addChild($key, null, $namespace);
				self::fromArray($value, $element, $remove_header, $namespaces, false);
			}
		}
		return '';
	}

	/**
	 * Copies an XML object
	 *
	 * @param object $source      The XML source
	 * @param object $target      The XML target
	 * @param string $elementname Name of the XML element of the target
	 * @return void
	 */
	public static function copy(&$source, &$target, $elementname)
	{
		if (count($source->children()) == 0) {
			$target->addChild($elementname, self::escape($source));
		} else {
			$child = $target->addChild($elementname);
			foreach ($source->children() as $childfield => $childentry) {
				self::copy($childentry, $child, $childfield);
			}
		}
	}

	/**
	 * Create an XML element
	 *
	 * @param DOMDocument $doc        XML root
	 * @param string       $element    XML element name
	 * @param string       $value      XML value
	 * @param array        $attributes array containing the attributes
	 *
	 * @return \DOMElement XML element object
	 */
	public static function createElement(DOMDocument $doc, string $element, string $value = '', array $attributes = []): DOMElement
	{
		$element = $doc->createElement($element, self::escape($value));

		foreach ($attributes as $key => $value) {
			$attribute        = $doc->createAttribute($key);
			$attribute->value = self::escape($value ?? '');
			$element->appendChild($attribute);
		}
		return $element;
	}

	/**
	 * Create an XML and append it to the parent object
	 *
	 * @param DOMDocument $doc        XML root
	 * @param DOMElement  $parent     parent object
	 * @param string      $element    XML element name
	 * @param string      $value      XML value
	 * @param array       $attributes Array containing the attributes
	 * @return void
	 */
	public static function addElement(DOMDocument $doc, DOMElement &$parent, string $element, string $value = null, array $attributes = [])
	{
		$element = self::createElement($doc, $element, $value ?? '', $attributes);
		$parent->appendChild($element);
	}

	/**
	 * Convert an XML document to a normalised, case-corrected array used by webfinger
	 *
	 * @param object  $xml_element     The XML document
	 * @param integer $recursion_depth recursion counter for internal use - default 0
	 *                                 internal use, recursion counter
	 *
	 * @return array | string The array from the xml element or the string
	 */
	public static function elementToArray($xml_element, int &$recursion_depth = 0)
	{
		// If we're getting too deep, bail out
		if ($recursion_depth > 512) {
			return null;
		}

		$xml_element_copy = '';
		if (!is_string($xml_element)
			&& !is_array($xml_element)
			&& (get_class($xml_element) == 'SimpleXMLElement')
		) {
			$xml_element_copy = $xml_element;
			$xml_element      = get_object_vars($xml_element);
		}

		if (is_array($xml_element)) {
			$result_array = [];
			if (count($xml_element) <= 0) {
				return trim(strval($xml_element_copy));
			}

			foreach ($xml_element as $key => $value) {
				$recursion_depth++;
				$result_array[strtolower($key)] = self::elementToArray($value, $recursion_depth);
				$recursion_depth--;
			}

			if ($recursion_depth == 0) {
				$temp_array   = $result_array;
				$result_array = [
					strtolower($xml_element_copy->getName()) => $temp_array,
				];
			}

			return $result_array;
		} else {
			return trim(strval($xml_element));
		}
	}

	/**
	 * Convert the given XML text to an array in the XML structure.
	 *
	 * Xml::toArray() will convert the given XML text to an array in the XML structure.
	 * Link: http://www.bin-co.com/php/scripts/xml2array/
	 * Portions significantly re-written by mike@macgirvin.com for Friendica
	 * (namespaces, lowercase tags, get_attribute default changed, more...)
	 *
	 * Examples: $array =  Xml::toArray(file_get_contents('feed.xml'));
	 *        $array =  Xml::toArray(file_get_contents('feed.xml', true, 1, 'attribute'));
	 *
	 * @param string  $contents         The XML text
	 * @param boolean $namespaces       True or false include namespace information
	 *                                  in the returned array as array elements.
	 * @param integer $get_attributes   1 or 0. If this is 1 the function will get the attributes as well as the tag values -
	 *                                  this results in a different array structure in the return value.
	 * @param string  $priority         Can be 'tag' or 'attribute'. This will change the way the resulting
	 *                                  array structure. For 'tag', the tags are given more importance.
	 *
	 * @return array The parsed XML in an array form. Use print_r() to see the resulting array structure.
	 * @throws \Exception
	 */
	public static function toArray(string $contents, bool $namespaces = true, int $get_attributes = 1, string $priority = 'attribute'): array
	{
		if (!$contents) {
			return [];
		}

		if (!function_exists('xml_parser_create')) {
			DI::logger()->error('Xml::toArray: parser function missing');
			return [];
		}

		$parent = [];

		libxml_use_internal_errors(true);
		libxml_clear_errors();

		if ($namespaces) {
			$parser = @xml_parser_create_ns("UTF-8", ':');
		} else {
			$parser = @xml_parser_create();
		}

		if (!$parser) {
			DI::logger()->warning('Xml::toArray: xml_parser_create: no resource');
			return [];
		}

		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		// http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		@xml_parse_into_struct($parser, trim($contents), $xml_values);
		@xml_parser_free($parser);

		if (! $xml_values) {
			DI::logger()->debug('Xml::toArray: libxml: parse error: ' . $contents);
			foreach (libxml_get_errors() as $err) {
				DI::logger()->debug('libxml: parse: ' . $err->code . ' at ' . $err->line . ':' . $err->column . ' : ' . $err->message);
			}
			libxml_clear_errors();
			return [];
		}

		//Initializations
		$xml_array = [];

		$current = &$xml_array; // Reference

		// Go through the tags.
		$repeated_tag_index = []; // Multiple tags with same name will be turned into an array
		foreach ($xml_values as $data) {
			$tag        = $data['tag'];
			$type       = $data['type'];
			$level      = $data['level'];
			$attributes = isset($data['attributes']) ? $data['attributes'] : null;
			$value      = isset($data['value']) ? $data['value'] : null;

			$result          = [];
			$attributes_data = [];

			if (isset($value)) {
				if ($priority == 'tag') {
					$result = $value;
				} else {
					$result['value'] = $value; // Put the value in a assoc array if we are in the 'Attribute' mode
				}
			}

			//Set the attributes too.
			if (isset($attributes) and $get_attributes) {
				foreach ($attributes as $attr => $val) {
					if ($priority == 'tag') {
						$attributes_data[$attr] = $val;
					} else {
						$result['@attributes'][$attr] = $val; // Set all the attributes in a array called 'attr'
					}
				}
			}

			// See tag status and do the needed.
			if ($namespaces && strpos($tag, ':')) {
				$namespc              = substr($tag, 0, strrpos($tag, ':'));
				$tag                  = strtolower(substr($tag, strlen($namespc) + 1));
				$result['@namespace'] = $namespc;
			}
			$tag = strtolower($tag);

			if ($type == 'open') {   // The starting of the tag '<tag>'
				$parent[$level - 1] = &$current;
				if (!is_array($current) || (!in_array($tag, array_keys($current)))) { // Insert New tag
					$current[$tag] = $result;
					if ($attributes_data) {
						$current[$tag. '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level] = 1;

					$current = &$current[$tag];
				} else { // There was another element with the same tag name

					if (isset($current[$tag][0])) { // If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					} else { // This section will make the value an array if multiple tags with the same name appear together
						$current[$tag]                           = [$current[$tag], $result]; // This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag . '_' . $level] = 2;

						if (isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current         = &$current[$tag][$last_item_index];
				}
			} elseif ($type == 'complete') { // Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if (!isset($current[$tag])) { //New Key
					$current[$tag]                           = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $attributes_data) {
						$current[$tag. '_attr'] = $attributes_data;
					}
				} else { // If taken, put all things inside a list(array)
					if (isset($current[$tag][0]) and is_array($current[$tag])) { // If it is already an array...

						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

						if ($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					} else { // If it is not an array...
						$current[$tag]                           = [$current[$tag], $result]; //...Make it an array using the existing value and the new value
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes) {
							if (isset($current[$tag.'_attr'])) { // The attribute of the last(0th) tag must be moved as well

								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}

							if ($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; // 0 and 1 indexes are already taken
					}
				}
			} elseif ($type == 'close') { // End of tag '</tag>'
				$current = &$parent[$level - 1];
			}
		}

		return $xml_array;
	}

	/**
	 * Delete a node in a XML object
	 *
	 * @param DOMDocument $doc  XML document
	 * @param string $node Node name
	 * @return void
	 */
	public static function deleteNode(DOMDocument $doc, string $node)
	{
		$xpath = new DOMXPath($doc);
		$list  = $xpath->query('//' . $node);
		foreach ($list as $child) {
			$child->parentNode->removeChild($child);
		}
	}

	/**
	 * Parse XML string
	 *
	 * @param string  $s XML string to parse into object
	 * @param boolean $suppress_log Whether to suppressing logging
	 * @return SimpleXMLElement|bool SimpleXMLElement or false on failure
	 */
	public static function parseString(string $s, bool $suppress_log = false)
	{
		libxml_use_internal_errors(true);

		$x = @simplexml_load_string($s);
		if (!$x) {
			if (!$suppress_log) {
				DI::logger()->error('Error(s) while parsing XML string.');
				foreach (libxml_get_errors() as $err) {
					DI::logger()->info('libxml error', ['code' => $err->code, 'position' => $err->line . ':' . $err->column, 'message' => $err->message]);
				}
				DI::logger()->debug('Erroring XML string', ['xml' => $s]);
			}
			libxml_clear_errors();
		}
		return $x;
	}

	/**
	 * Gets first node value
	 *
	 * @param DOMXPath $xpath XPath object
	 * @param string $element Element name
	 * @param DOMNode $context Context object or NULL
	 * @return string XML node value or empty string on failure
	 */
	public static function getFirstNodeValue(DOMXPath $xpath, string $element, DOMNode $context = null)
	{
		$result = @$xpath->evaluate($element, $context);
		if (!is_object($result)) {
			return '';
		}

		$first_item = $result->item(0);
		if (!is_object($first_item)) {
			return '';
		}

		return $first_item->nodeValue;
	}

	/**
	 * Gets first attributes
	 *
	 * @param DOMXPath $xpath XPath object
	 * @param string $element Element name
	 * @param DOMNode $context Context object or NULL
	 * @return mixed|bool First element's attributes field or false on failure
	 */
	public static function getFirstAttributes(DOMXPath $xpath, string $element, DOMNode $context = null)
	{
		$result = @$xpath->query($element, $context);
		if (!is_object($result)) {
			return false;
		}

		$first_item = $result->item(0);
		if (!is_object($first_item)) {
			return false;
		}

		return $first_item->attributes;
	}

	/**
	 * Gets first node's value
	 *
	 * @param DOMXPath $xpath XPath object
	 * @param string $element Element name
	 * @param DOMNode $context Context object or NULL
	 * @return string First value or empty string on failure
	 */
	public static function getFirstValue(DOMXPath $xpath, string $element, DOMNode $context = null): string
	{
		$result = @$xpath->query($element, $context);
		if (!is_object($result)) {
			return '';
		}

		$first_item = $result->item(0);
		if (!is_object($first_item)) {
			return '';
		}

		return $first_item->nodeValue;
	}

	/**
	 * escape text ($str) for XML transport
	 *
	 * @param string $str
	 * @return string Escaped text.
	 * @todo Move this generic method to Util\Strings and also rewrite all other occurrences
	 */
	public static function escape(string $str): string
	{
		return trim(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Undo an escape
	 *
	 * @param string $s xml escaped text
	 * @return string unescaped text
	 * @todo Move this generic method to Util\Strings and also rewrite all other occurrences
	 */
	public static function unescape(string $s): string
	{
		return htmlspecialchars_decode($s, ENT_QUOTES);
	}

	/**
	 * Apply escape() to all values of array $val, recursively
	 *
	 * @param array|bool|string $val Value of type bool, array or string
	 * @return array|string Returns array if array provided or string in other cases
	 * @todo Move this generic method to Util\Strings
	 */
	public static function arrayEscape($val)
	{
		if (is_bool($val)) {
			return $val ? 'true' : 'false';
		} elseif (is_array($val)) {
			return array_map('XML::arrayEscape', $val);
		}

		return self::escape((string) $val);
	}
}

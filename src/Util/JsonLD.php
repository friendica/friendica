<?php
/**
 * @file src/Util/JsonLD.php
 */
namespace Friendica\Util;

use Friendica\Core\Cache;
use Exception;

/**
 * @brief This class contain methods to work with JsonLD data
 */
class JsonLD
{
	/**
	 * @brief Loader for LD-JSON validation
	 *
	 * @param $url
	 *
	 * @return the loaded data
	 */
	public static function documentLoader($url)
	{
		$recursion = 0;

		$x = debug_backtrace();
		if ($x) {
			foreach ($x as $n) {
				if ($n['function'] === __FUNCTION__)  {
					$recursion ++;
				}
			}
		}

		if ($recursion > 5) {
			logger('jsonld bomb detected at: ' . $url);
			exit();
		}

		$result = Cache::get('documentLoader:' . $url);
		if (!is_null($result)) {
			return $result;
		}

		$data = jsonld_default_document_loader($url);
		Cache::set('documentLoader:' . $url, $data, Cache::DAY);
		return $data;
	}

	/**
	 * @brief Normalises a given JSON array
	 *
	 * @param array $json
	 *
	 * @return normalized JSON string
	 */
	public static function normalize($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		try {
			$normalized = jsonld_normalize($jsonobj, array('algorithm' => 'URDNA2015', 'format' => 'application/nquads'));
		}
		catch (Exception $e) {
			$normalized = false;
			logger('normalise error:' . print_r($e, true), LOGGER_DEBUG);
		}

		return $normalized;
	}

	/**
	 * @brief Compacts a given JSON array
	 *
	 * @param array $json
	 *
	 * @return comacted JSON array
	 */
	public static function compact($json)
	{
		jsonld_set_document_loader('Friendica\Util\JsonLD::documentLoader');

		$context = (object)['as' => 'https://www.w3.org/ns/activitystreams#',
			'w3id' => 'https://w3id.org/security#',
			'vcard' => (object)['@id' => 'http://www.w3.org/2006/vcard/ns#', '@type' => '@id'],
			'ostatus' => (object)['@id' => 'http://ostatus.org#', '@type' => '@id'],
			'diaspora' => (object)['@id' => 'https://diasporafoundation.org/ns/', '@type' => '@id'],
			'dc' => (object)['@id' => 'http://purl.org/dc/terms/', '@type' => '@id'],
			'ldp' => (object)['@id' => 'http://www.w3.org/ns/ldp#', '@type' => '@id']];

		$jsonobj = json_decode(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


		try {
			$compacted = jsonld_compact($jsonobj, $context);
		}
		catch (Exception $e) {
			$compacted = false;
			logger('compacting error:' . print_r($e, true), LOGGER_DEBUG);
		}

		return json_decode(json_encode($compacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);
	}

	/**
	 * @brief Fetches an element array from a JSON array
	 *
	 * @param $array
	 * @param $element
	 * @param $key
	 *
	 * @return fetched element array
	 */
	public static function fetchElementArray($array, $element, $key = '@id')
	{
		if (empty($array)) {
			return null;
		}

		if (!isset($array[$element])) {
			return null;
		}

		// If it isn't an array yet, make it to one
		if (!is_int(key($array[$element]))) {
			$array[$element] = [$array[$element]];
		}

		$elements = [];

		foreach ($array[$element] as $entry) {
			if (!is_array($entry)) {
				$elements[] = $entry;
			} elseif (!empty($entry[$key])) {
				$elements[] = $entry[$key];
			} elseif (!empty($entry) || !is_array($entry)) {
				$elements[] = $entry;
			}
		}

		return $elements;
	}

	/**
	 * @brief Fetches an element from a JSON array
	 *
	 * @param $array
	 * @param $element
	 * @param $key
	 * @param $type
	 * @param $type_value
	 *
	 * @return fetched element
	 */
	public static function fetchElement($array, $element, $key = '@id', $type = null, $type_value = null)
	{
		if (empty($array)) {
			return null;
		}

		if (!isset($array[$element])) {
			return null;
		}

		if (!is_array($array[$element])) {
			return $array[$element];
		}

		if (is_null($type) || is_null($type_value)) {
			$element_array = self::fetchElementArray($array, $element, $key);
			if (is_null($element_array)) {
				return null;
			}

			return array_shift($element_array);
		}

		$element_array = self::fetchElementArray($array, $element);
		if (is_null($element_array)) {
			return null;
		}

		foreach ($element_array as $entry) {
			if (isset($entry[$key]) && isset($entry[$type]) && ($entry[$type] == $type_value)) {
				return $entry[$key];
			}
		}

		return null;
	}
}

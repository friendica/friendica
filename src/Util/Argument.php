<?php

namespace Friendica\Util;

use Friendica\Network\HTTPException\InternalServerErrorException;

class Argument
{
	/**
	 * Get a value to a given key in the arguments
	 *
	 * @param string $key     The search key
	 * @param array  $args    The arguments to search
	 * @param string $default The default value if no value was found (default empty)
	 * @param string $type    The type of value it has to be (default null = all)
	 *
	 * @return string The value to the key
	 *
	 * @throws InternalServerErrorException Arguments are not valid
	 */
	public static function get($key, $args, $default = '', $type = null) {
		$countArgs = count($args);
		$keyId = array_search('--' . $key, $args);

		// argument not found, return default
		if (is_bool($keyId) && !$keyId) {
			return $default;
		}

		// There is no next argument? => this argument is a flag
		if ($countArgs <= ($keyId + 1)) {
			return true;
		}

		$value = $args[$keyId + 1];

		// The next parameter is another argument? => this argument is a flag
		if ((strlen($value) > 1) && substr($value,0, 2) == '--') {
			return true;
		}

		if (!is_null($type)) {
			settype($value, $type);

			if (gettype($value) != $type) {
				throw new InternalServerErrorException('PHP value ' . $value . ' is type ' . gettype($value) . ' not ' . $type);
			}
		}

		return $value;
	}

	/**
	 * Sets a value to a given key in the arguments
	 *
	 * @param string       $args  The argument string
	 * @param string       $key   The key of the new argument
	 * @param bool|string  $value The value of the new argument
	 */
	public static function set(&$args, $key, $value) {
		if (empty($args)) {
			$args = '';
		}

		if (is_bool($value) && !$value) {
			return;
		}

		$args .= ' --' . $key;

		if (!is_bool($value)) {
			$args .= ' ' . ((string)$value);
		}
	}

	/**
	 * Switch a argument array to a passable argument string
	 *
	 * @param string $cmdline The command where the Arguments will get set
	 * @param array  $args    Arguments to pass to a command line ( [ $key => $value, $key => $value, ... ]
	 *
	 */
	public static function setArgs(&$cmdline, $args) {
		if (is_null($args) || !is_array($args)) {
			return;
		}

		if (empty($cmdline)) {
			$cmdline = '';
		}

		foreach ($args as $key => $value) {
			self::set($cmdline, $key, $value);
		}
	}
}

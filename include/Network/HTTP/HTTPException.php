<?php

/**
 * @file include/Network/HTTP/HTTPException.php
 *
 * @brief Throwable exceptions to return HTTP status code
 *
 * This list of Exception has be extracted from
 * http://racksburg.com/choosing-an-http-status-code/
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Base class for HTTP response codes
 */
class HTTPException extends \Exception
{

	var $httpcode = 200;
	var $httpdesc = "";

	/**
	 * @brief Create a new HTTPException
	 *
	 * @param string $message Error message
	 * @param int $code Error code
	 * @param Exception $previous Previous exception
	 */
	public function __construct($message="", $code = 0, Exception $previous = null) {
		if ($this->httpdesc=="") {
			// if no description is set, we build it from class name.
			$classpath_arr = explode("\\", get_class($this));
			$classname = array_pop($classpath_arr);
			$httpexceptionname = str_replace("Exception", "", $classname);

			// add space between a lowercase and a uppercase char:
			// "NotFound" -> "Not Found"
			$this->httpdesc = preg_replace(
				"|([a-z])([A-Z])|",
				'$1 $2',
				$httpexceptionname
			);
		}
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @brief Send approriate HTTP header for the exception
	 */
	public function send_header() {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $this->httpcode . ' ' . $this->httpdesc);
	}
}

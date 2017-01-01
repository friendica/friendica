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
class HTTPException extends \Exception {

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
			$this->httpdesc = preg_replace("|([a-z])([A-Z])|",'$1 $2',
								str_replace("Exception","",
									explode("\\",get_class($this))[3]
								)
							);
		}
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @brief Send approriate HTTP header for the exception
	 */
	public function send_header() {
		header($_SERVER["SERVER_PROTOCOL"] . ' '.$this->httpcode.' ' . $this->httpdesc);
	}
}

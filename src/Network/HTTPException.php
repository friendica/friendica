<?php

/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has be extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */

namespace Friendica\Network;

use Exception;

class HTTPException extends Exception
{
	var $httpcode = 200;
	var $httpdesc = "";
}

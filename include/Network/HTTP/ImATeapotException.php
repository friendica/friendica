<?php

/**
 * @file include/Network/HTTP/ImATeapotException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Im ATeapot (418) http exception
 */
class ImATeapotException extends HTTPException {
	var $httpcode = 418;
	var $httpdesc = "I'm A Teapot";
}

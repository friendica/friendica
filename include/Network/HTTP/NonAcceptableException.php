<?php

/**
 * @file include/Network/HTTP/NonAcceptableException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Non Acceptable (406) http exception
 */
class NonAcceptableException extends HTTPException {
	var $httpcode = 406;
}

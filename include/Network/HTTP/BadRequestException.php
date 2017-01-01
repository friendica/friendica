<?php

/**
 * @file include/Network/HTTP/BadRequestException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Bad Request (400) http exception
 */
class BadRequestException extends HTTPException {
	var $httpcode = 400;
}

<?php

/**
 * @file include/Network/HTTP/UnauthorizedException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Unauthorized (401) http exception
 */
class UnauthorizedException extends HTTPException {
	var $httpcode = 401;
}

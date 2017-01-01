<?php

/**
 * @file include/Network/HTTP/TooManyRequestsException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Too Many Requests (429) http exception
 */
class TooManyRequestsException extends HTTPException {
	var $httpcode = 429;
}

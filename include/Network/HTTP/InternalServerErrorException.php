<?php

/**
 * @file include/Network/HTTP/InternalServerErrorException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Internal Server Error (500) http exception
 */
class InternalServerErrorException extends HTTPException {
	var $httpcode = 500;
}

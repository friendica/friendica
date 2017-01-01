<?php

/**
 * @file include/Network/HTTP/MethodNotAllowedException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Method Not Allowed (405) http exception
 */
class MethodNotAllowedException extends HTTPException {
	var $httpcode = 405;
}

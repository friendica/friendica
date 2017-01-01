<?php

/**
 * @file include/Network/HTTP/LenghtRequiredException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Lenght Required (411) http exception
 */
class LenghtRequiredException extends HTTPException {
	var $httpcode = 411;
}

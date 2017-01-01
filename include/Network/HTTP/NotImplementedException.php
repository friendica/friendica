<?php

/**
 * @file include/Network/HTTP/NotImplementedException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Not Implemented (501) http exception
 */
class NotImplementedException extends HTTPException {
	var $httpcode = 501;
}

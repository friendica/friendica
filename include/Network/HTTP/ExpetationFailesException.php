<?php

/**
 * @file include/Network/HTTP/ExpetationFailesException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Expetation Failes (417) http exception
 */
class ExpetationFailesException extends HTTPException {
	var $httpcode = 417;
}

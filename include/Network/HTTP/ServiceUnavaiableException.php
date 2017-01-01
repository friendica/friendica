<?php

/**
 * @file include/Network/HTTP/ServiceUnavaiableException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Service Unavaiable (503) http exception
 */
class ServiceUnavaiableException extends HTTPException {
	var $httpcode = 503;
}

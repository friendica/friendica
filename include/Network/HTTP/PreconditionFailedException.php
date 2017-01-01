<?php

/**
 * @file include/Network/HTTP/PreconditionFailedException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Precondition Failed (412) http exception
 */
class PreconditionFailedException extends HTTPException {
	var $httpcode = 412;
}

<?php

/**
 * @file include/Network/HTTP/ForbiddenException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Forbidden (403) http exception
 */
class ForbiddenException extends HTTPException 
{
	var $httpcode = 403;
}

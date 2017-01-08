<?php

/**
 * @file include/Network/HTTP/ConflictException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Conflict (409) http exception
 */
class ConflictException extends HTTPException 
{
	var $httpcode = 409;
}

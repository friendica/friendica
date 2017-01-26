<?php

/**
 * @file include/Network/HTTP/NotFoundException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Not Found (404) http exception
 */
class NotFoundException extends HTTPException 
{
	var $httpcode = 404;
}

<?php

/**
 * @file include/Network/HTTP/BadGatewayException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Bad Gateway (502) http exception
 */
class BadGatewayException extends HTTPException 
{
	var $httpcode = 502;
}

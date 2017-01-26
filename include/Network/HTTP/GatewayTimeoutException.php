<?php

/**
 * @file include/Network/HTTP/GatewayTimeoutException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Gateway Timeout (504) http exception
 */
class GatewayTimeoutException extends HTTPException 
{
	var $httpcode = 504;
}

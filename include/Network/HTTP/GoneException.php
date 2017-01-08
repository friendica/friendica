<?php

/**
 * @file include/Network/HTTP/GoneException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Gone (410) http exception
 */
class GoneException extends HTTPException 
{
	var $httpcode = 410;
}

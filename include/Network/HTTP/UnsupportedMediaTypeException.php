<?php

/**
 * @file include/Network/HTTP/UnsupportedMediaTypeException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Unsupported Media Type (415) http exception
 */
class UnsupportedMediaTypeException extends HTTPException 
{
	var $httpcode = 415;
}

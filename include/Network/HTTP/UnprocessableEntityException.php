<?php

/**
 * @file include/Network/HTTP/UnprocessableEntityException.php
 */

namespace Friendica\Network\HTTP;

/**
 * @brief Unprocessable Entity (422) http exception
 */
class UnprocessableEntityException extends HTTPException 
{
	var $httpcode = 422;
}

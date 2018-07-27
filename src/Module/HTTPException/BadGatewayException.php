<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class BadGatewayException extends HTTPException
{
	var $httpcode = 502;
}

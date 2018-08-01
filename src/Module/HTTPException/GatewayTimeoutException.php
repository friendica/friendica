<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class GatewayTimeoutException extends HTTPException
{
	var $httpcode = 504;
}

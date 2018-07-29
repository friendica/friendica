<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnauthorizedException extends HTTPException
{
	var $httpcode = 401;
}

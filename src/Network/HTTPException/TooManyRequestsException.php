<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class TooManyRequestsException extends HTTPException
{
	var $httpcode = 429;
}

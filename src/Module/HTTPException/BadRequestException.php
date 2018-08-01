<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class BadRequestException extends HTTPException
{
	var $httpcode = 400;
}

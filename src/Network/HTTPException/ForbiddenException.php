<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ForbiddenException extends HTTPException
{
	var $httpcode = 403;
}
<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ConflictException extends HTTPException
{
	var $httpcode = 409;
}

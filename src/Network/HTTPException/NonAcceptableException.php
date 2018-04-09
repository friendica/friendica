<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NonAcceptableException extends HTTPException
{
	var $httpcode = 406;
}

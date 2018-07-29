<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class InternalServerErrorException extends HTTPException
{
	var $httpcode = 500;
}

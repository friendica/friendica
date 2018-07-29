<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnprocessableEntityException extends HTTPException
{
	var $httpcode = 422;
}

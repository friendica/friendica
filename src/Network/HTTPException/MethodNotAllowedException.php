<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class MethodNotAllowedException extends HTTPException
{
	var $httpcode = 405;
}

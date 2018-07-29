<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class LenghtRequiredException extends HTTPException
{
	var $httpcode = 411;
}

<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class GoneException extends HTTPException
{
	var $httpcode = 410;
}

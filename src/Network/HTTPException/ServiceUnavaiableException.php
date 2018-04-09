<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ServiceUnavaiableException extends HTTPException
{
	var $httpcode = 503;
}

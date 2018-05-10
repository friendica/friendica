<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ImATeapotException extends HTTPException
{
	var $httpcode = 418;
	var $httpdesc = "I'm A Teapot";
}

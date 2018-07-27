<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class UnsupportedMediaTypeException extends HTTPException
{
	var $httpcode = 415;
}

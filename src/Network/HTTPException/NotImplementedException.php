<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotImplementedException extends HTTPException
{
	var $httpcode = 501;
}

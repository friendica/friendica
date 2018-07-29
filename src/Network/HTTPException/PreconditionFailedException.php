<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class PreconditionFailedException extends HTTPException
{
	var $httpcode = 412;
}

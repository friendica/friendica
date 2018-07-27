<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class ExpectationFailedException extends HTTPException
{
	var $httpcode = 417;
}

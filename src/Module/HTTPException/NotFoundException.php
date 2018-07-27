<?php

namespace Friendica\Network\HTTPException;

use Friendica\Network\HTTPException;

class NotFoundException extends HTTPException {
	var $httpcode = 404;
}

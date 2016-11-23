<?php

namespace Friendica\Network\HTTP;

class ConflictException extends HTTPException {
	var $httpcode = 409;
}

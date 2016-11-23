<?php

namespace Friendica\Network\HTTP;

class UnauthorizedException extends HTTPException {
	var $httpcode = 401;
}

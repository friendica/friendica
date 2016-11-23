<?php

namespace Friendica\Network\HTTP;

class ForbiddenException extends HTTPException {
	var $httpcode = 403;
}

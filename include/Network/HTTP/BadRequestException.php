<?php

namespace Friendica\Network\HTTP;

class BadRequestException extends HTTPException {
	var $httpcode = 400;
}

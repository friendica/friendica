<?php

namespace Friendica\Network\HTTP;

class TooManyRequestsException extends HTTPException {
	var $httpcode = 429;
}

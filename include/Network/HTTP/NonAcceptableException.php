<?php

namespace Friendica\Network\HTTP;

class NonAcceptableException extends HTTPException {
	var $httpcode = 406;
}

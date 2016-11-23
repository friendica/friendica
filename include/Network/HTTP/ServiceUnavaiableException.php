<?php

namespace Friendica\Network\HTTP;

class ServiceUnavaiableException extends HTTPException {
	var $httpcode = 503;
}

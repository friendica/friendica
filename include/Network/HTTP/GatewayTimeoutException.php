<?php

namespace Friendica\Network\HTTP;

class GatewayTimeoutException extends HTTPException {
	var $httpcode = 504;
}

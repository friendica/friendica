<?php

namespace Friendica\Network\HTTP;

class BadGatewayException extends HTTPException {
	var $httpcode = 502;
}

<?php

namespace Friendica\Network\HTTP;

class MethodNotAllowedException extends HTTPException {
	var $httpcode = 405;
}

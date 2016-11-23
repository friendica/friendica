<?php

namespace Friendica\Network\HTTP;

class LenghtRequiredException extends HTTPException {
	var $httpcode = 411;
}

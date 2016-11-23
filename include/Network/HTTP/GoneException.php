<?php

namespace Friendica\Network\HTTP;

class GoneException extends HTTPException {
	var $httpcode = 410;
}

<?php

namespace Friendica\Network\HTTP;

class ExpetationFailesException extends HTTPException {
	var $httpcode = 417;
}

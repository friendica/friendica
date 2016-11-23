<?php

namespace Friendica\Network\HTTP;

class NotImplementedException extends HTTPException {
	var $httpcode = 501;
}

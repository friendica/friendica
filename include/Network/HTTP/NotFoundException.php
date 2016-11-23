<?php

namespace Friendica\Network\HTTP;

class NotFoundException extends HTTPException {
	var $httpcode = 404;
}

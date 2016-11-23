<?php

namespace Friendica\Network\HTTP;

class InternalServerErrorException extends HTTPException {
	var $httpcode = 500;
}

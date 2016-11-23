<?php

namespace Friendica\Network\HTTP;

class UnprocessableEntityException extends HTTPException {
	var $httpcode = 422;
}

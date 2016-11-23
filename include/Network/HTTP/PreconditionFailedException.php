<?php

namespace Friendica\Network\HTTP;

class PreconditionFailedException extends HTTPException {
	var $httpcode = 412;
}

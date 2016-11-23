<?php

namespace Friendica\Network\HTTP;

class UnsupportedMediaTypeException extends HTTPException {
	var $httpcode = 415;
}

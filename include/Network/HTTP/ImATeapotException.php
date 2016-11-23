<?php

namespace Friendica\Network\HTTP;

class ImATeapotException extends HTTPException {
	var $httpcode = 418;
	var $httpdesc = "I'm A Teapot";
}

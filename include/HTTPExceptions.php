<?php
/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has be extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */

class HTTPException extends Exception {
	var $httpcode = 200;
	var $httpdesc = "";
	public function __construct($message="", $code = 0, Exception $previous = null) {
		if ($this->httpdesc=="") {
			$this->httpdesc = preg_replace("|([a-z])([A-Z])|",'$1 $2', str_replace("Exception","",get_class($this)));
		}
		parent::__construct($message, $code, $previous);
	}
}

// 4xx
class TooManyRequestsException extends HTTPException {
	var $httpcode = 429;
}

class UnauthorizedException extends HTTPException {
	var $httpcode = 401;
}

class ForbiddenException extends HTTPException {
	var $httpcode = 403;
}

class NotFoundException extends HTTPException {
	var $httpcode = 404;
}

class GoneException extends HTTPException {
	var $httpcode = 410;
}

class MethodNotAllowedException extends HTTPException {
	var $httpcode = 405;
}

class NonAcceptableException extends HTTPException {
	var $httpcode = 406;
}

class LenghtRequiredException extends HTTPException {
	var $httpcode = 411;
}

class PreconditionFailedException extends HTTPException {
	var $httpcode = 412;
}

class UnsupportedMediaTypeException extends HTTPException {
	var $httpcode = 415;
}

class ExpetationFailesException extends HTTPException {
	var $httpcode = 417;
}

class ConflictException extends HTTPException {
	var $httpcode = 409;
}

class UnprocessableEntityException extends HTTPException {
	var $httpcode = 422;
}

class ImATeapotException extends HTTPException {
	var $httpcode = 418;
	var $httpdesc = "I'm A Teapot";
}

class BadRequestException extends HTTPException {
	var $httpcode = 400;
}

// 5xx

class ServiceUnavaiableException extends HTTPException {
	var $httpcode = 503;
}

class BadGatewayException extends HTTPException {
	var $httpcode = 502;
}

class GatewayTimeoutException extends HTTPException {
	var $httpcode = 504;
}

class NotImplementedException extends HTTPException {
	var $httpcode = 501;
}

class InternalServerErrorException extends HTTPException {
	var $httpcode = 500;
}




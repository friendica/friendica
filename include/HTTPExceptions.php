<?php
/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has be extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */

class HTTPException extends Exception
{
    public $httpcode = 200;
    public $httpdesc = "";
    public function __construct($message="", $code = 0, Exception $previous = null)
    {
        if ($this->httpdesc=="") {
            $this->httpdesc = preg_replace("|([a-z])([A-Z])|", '$1 $2', str_replace("Exception", "", get_class($this)));
        }
        parent::__construct($message, $code, $previous);
    }
}

// 4xx
class TooManyRequestsException extends HTTPException
{
    public $httpcode = 429;
}

class UnauthorizedException extends HTTPException
{
    public $httpcode = 401;
}

class ForbiddenException extends HTTPException
{
    public $httpcode = 403;
}

class NotFoundException extends HTTPException
{
    public $httpcode = 404;
}

class GoneException extends HTTPException
{
    public $httpcode = 410;
}

class MethodNotAllowedException extends HTTPException
{
    public $httpcode = 405;
}

class NonAcceptableException extends HTTPException
{
    public $httpcode = 406;
}

class LenghtRequiredException extends HTTPException
{
    public $httpcode = 411;
}

class PreconditionFailedException extends HTTPException
{
    public $httpcode = 412;
}

class UnsupportedMediaTypeException extends HTTPException
{
    public $httpcode = 415;
}

class ExpetationFailesException extends HTTPException
{
    public $httpcode = 417;
}

class ConflictException extends HTTPException
{
    public $httpcode = 409;
}

class UnprocessableEntityException extends HTTPException
{
    public $httpcode = 422;
}

class ImATeapotException extends HTTPException
{
    public $httpcode = 418;
    public $httpdesc = "I'm A Teapot";
}

class BadRequestException extends HTTPException
{
    public $httpcode = 400;
}

// 5xx

class ServiceUnavaiableException extends HTTPException
{
    public $httpcode = 503;
}

class BadGatewayException extends HTTPException
{
    public $httpcode = 502;
}

class GatewayTimeoutException extends HTTPException
{
    public $httpcode = 504;
}

class NotImplementedException extends HTTPException
{
    public $httpcode = 501;
}

class InternalServerErrorException extends HTTPException
{
    public $httpcode = 500;
}

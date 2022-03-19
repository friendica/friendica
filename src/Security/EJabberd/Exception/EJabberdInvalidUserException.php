<?php

namespace Friendica\Security\EJabberd\Exception;

class EJabberdInvalidUserException extends \RuntimeException
{
	public function __construct($message = '', \Exception $previous = null)
	{
		parent::__construct($message, 404, $previous);
	}
}

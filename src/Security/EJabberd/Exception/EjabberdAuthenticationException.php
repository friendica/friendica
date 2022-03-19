<?php

namespace Friendica\Security\EJabberd\Exception;

class EjabberdAuthenticationException extends \RuntimeException
{
	public function __construct($message = '', \Exception $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}

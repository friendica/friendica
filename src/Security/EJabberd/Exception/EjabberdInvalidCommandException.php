<?php

namespace Friendica\Security\EJabberd\Exception;

class EjabberdInvalidCommandException extends \RuntimeException
{
	public function __construct($message = '', \Exception $previous = null)
	{
		parent::__construct($message, 400, $previous);
	}
}

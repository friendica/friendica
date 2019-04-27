<?php

namespace Friendica\Network\HTTPException;

use Exception;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

class UnsupportedMediaTypeException extends HTTPException
{
	var $httpcode = 415;

	public function __construct($message = '', $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->httpdesc = L10n::t('Unsupported Media Type');
	}
}

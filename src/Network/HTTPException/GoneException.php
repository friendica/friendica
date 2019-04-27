<?php

namespace Friendica\Network\HTTPException;

use Exception;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

class GoneException extends HTTPException
{
	var $httpcode = 410;

	public function __construct($message = '', $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->httpdesc = L10n::t('Gone');
	}
}

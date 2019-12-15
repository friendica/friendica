<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Shows the maintenance reason
 * or redirects to the alternate location
 */
class Maintenance extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$config = DI::app()->getConfig();

		$reason = $config->get('system', 'maintenance_reason');

		if ((substr(Strings::normaliseLink($reason), 0, 7) === 'http://') ||
			(substr(Strings::normaliseLink($reason), 0, 8) === 'https://')) {
			System::externalRedirect($reason, 307);
		}

		$exception = new HTTPException\ServiceUnavailableException($reason);
		$exception->httpdesc = L10n::t('System down for maintenance');
		throw $exception;
	}
}

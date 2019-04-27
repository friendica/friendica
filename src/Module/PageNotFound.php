<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

class PageNotFound extends BaseModule
{
	public static function content()
	{
		throw new HTTPException\NotFoundException(L10n::t('Page not found.'));
	}
}

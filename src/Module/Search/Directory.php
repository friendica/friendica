<?php

namespace Friendica\Module\Search;

use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Module\BaseSearchModule;
use Friendica\Module\Login;
use Friendica\Util\Strings;

/**
 * Directory search module
 */
class Directory extends BaseSearchModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			notice(L10n::t('Permission denied.'));
			return Login::form();
		}

		$search = Strings::escapeTags(trim(rawurldecode($_REQUEST['search'] ?? '')));

		$a = DI::app();

		if (empty($a->page['aside'])) {
			$a->page['aside'] = '';
		}

		$a->page['aside'] .= Widget::findPeople();
		$a->page['aside'] .= Widget::follow();

		return self::performSearch($search);
	}
}

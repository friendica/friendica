<?php

namespace Friendica\Module\Search;

use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Module\BaseSearchModule;
use Friendica\Module\Security\Login;
use Friendica\Registry\App;
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

		if (empty(App::page()['aside'])) {
			App::page()['aside'] = '';
		}

		App::page()['aside'] .= Widget::findPeople();
		App::page()['aside'] .= Widget::follow();

		return self::performContactSearch($search);
	}
}

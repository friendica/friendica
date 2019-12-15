<?php

namespace Friendica\Module\Search;

use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

class Saved extends BaseModule
{
	public static function init(array $parameters = [])
	{
		/** @var Arguments $args */
		$args = self::getClass(Arguments::class);

		$action = $args->get(2, 'none');
		$search = Strings::escapeTags(trim(rawurldecode($_GET['term'] ?? '')));

		$return_url = $_GET['return_url'] ?? 'search?q=' . urlencode($search);

		if (local_user() && $search) {
			switch ($action) {
				case 'add':
					$fields = ['uid' => local_user(), 'term' => $search];
					if (!DBA::exists('search', $fields)) {
						DBA::insert('search', $fields);
						info(L10n::t('Search term successfully saved.'));
					} else {
						info(L10n::t('Search term already saved.'));
					}
					break;

				case 'remove':
					DBA::delete('search', ['uid' => local_user(), 'term' => $search]);
					info(L10n::t('Search term successfully removed.'));
					break;
			}
		}

		DI::app()->internalRedirect($return_url);
	}
}

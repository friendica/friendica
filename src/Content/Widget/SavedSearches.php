<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Content\Widget;

use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;

class SavedSearches
{
	/**
	 * @param string $return_url
	 * @param string $search
	 * @return string
	 * @throws \Exception
	 */
	public static function getHTML(string $return_url, string $search = ''): string
	{
		$saved = [];
		$saved_searches = DBA::select('search', ['id', 'term'], ['uid' => DI::userSession()->getLocalUserId()], ['order' => ['term']]);
		while ($saved_search = DBA::fetch($saved_searches)) {
			$saved[] = [
				'id'          => $saved_search['id'],
				'term'        => $saved_search['term'],
				'encodedterm' => urlencode($saved_search['term']),
				'searchpath'  => Search::getSearchPath($saved_search['term']),
				'delete'      => DI::l10n()->t('Remove term'),
				'selected'    => $search == $saved_search['term'],
			];
		}
		DBA::close($saved_searches);

		if (empty($saved)) {
			return '';
		}

		$tpl = Renderer::getMarkupTemplate('widget/saved_searches.tpl');

		return Renderer::replaceMacros($tpl, [
			'$title'      => DI::l10n()->t('Saved Searches'),
			'$add'        => '',
			'$searchbox'  => '',
			'$saved'      => $saved,
			'$return_url' => urlencode($return_url),
		]);
	}
}

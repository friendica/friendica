<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
		$saved          = [];
		$saved_searches = DBA::select('search', ['id', 'term'], ['uid' => DI::userSession()->getLocalUserId()], ['order' => ['term']]);
		while ($saved_search = DBA::fetch($saved_searches)) {
			$saved[] = [
				'id'          => $saved_search['id'],
				'term'        => $saved_search['term'],
				'encodedterm' => urlencode((string) $saved_search['term']),
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
			'$return_url' => bin2hex($return_url),
		]);
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Content\Conversation;

use Friendica\Core\L10n;

/**
 * Builds the database conditions for the language filter of the timelines.
 *
 * The thread based views store the languages as JSON (e.g. `{"de":1,"en":1}`) in `post-content`,
 * the channel views contain the plain language code of `post-engagement`.
 * Only the first language of the JSON is relevant, since the further ones are unreliable.
 */
class LanguageCondition
{
	/**
	 * Returns the condition for views with the JSON language field (posts without language always pass)
	 *
	 * @param string[] $languages
	 * @return array Empty array when no language is selected
	 */
	public function getTimelineCondition(array $languages): array
	{
		if (!$languages) {
			return [];
		}

		$query  = [];
		$params = [];
		foreach ($languages as $language) {
			$query[]  = '`language` LIKE ?';
			$params[] = '{"' . $language . '":%';
		}
		return array_merge(['(' . implode(' OR ', $query) . ' OR `language` IS NULL)'], $params);
	}

	/**
	 * Returns the condition for views with the plain language code (posts without language always pass)
	 *
	 * @param string[] $languages
	 * @return array Empty array when no language is selected
	 */
	public function getChannelCondition(array $languages): array
	{
		if (!$languages) {
			return [];
		}

		return ['language' => array_values(array_unique(array_merge($languages, [L10n::UNDETERMINED_LANGUAGE])))];
	}
}

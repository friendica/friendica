<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/suggestions/
 */
class Suggestions extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit' => 40, // Maximum number of results to return. Defaults to 40.
		], $request);

		$suggestions = Contact\Relation::getCachedSuggestions($uid, 0, $request['limit']);

		$accounts = [];

		foreach ($suggestions as $suggestion) {
			$accounts[] = [
				'source'  => 'past_interactions',
				'account' => DI::mstdnAccount()->createFromContactId($suggestion['id'], $uid)
			];
		}

		$this->jsonExit($accounts);
	}
}

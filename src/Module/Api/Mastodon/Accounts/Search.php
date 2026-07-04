<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Util\Network;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/#search
 */
class Search extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'q'         => '',    // What to search for
			'limit'     => 40,    // Maximum number of results. Defaults to 40.
			'offset'    => 0,     // Offset in search results. Used for pagination. Defaults to 0.
			'resolve'   => false, // Attempt WebFinger lookup. Defaults to false. Use this when q is an exact address.
			'following' => false, // Only who the user is following. Defaults to false.
		], $request);

		$accounts = [];

		if (($request['offset'] == 0) && (Network::isValidHttpUrl($request['q']) || (strrpos((string) $request['q'], '@') > 0))) {
			$id = Contact::getIdForURL($request['q'], 0, $request['resolve'] ? null : false);

			if (!empty($id)) {
				$accounts[] = DI::mstdnAccount()->createFromContactId($id, $uid);
			}
		}

		if (empty($accounts)) {
			$contacts = Contact::searchByName($request['q'], '', false, $request['following'] ? $uid : 0, $request['limit'], $request['offset']);
			foreach ($contacts as $contact) {
				$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
			}
			DBA::close($contacts);
		}

		$this->jsonExit($accounts);
	}
}

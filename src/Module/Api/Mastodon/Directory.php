<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/instance/directory/
 */
class Directory extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/instance/directory/
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'offset' => 0,        // How many accounts to skip before returning results. Default 0.
			'limit'  => 40,       // How many accounts to load. Default 40.
			'order'  => 'active', // active to sort by most recently posted statuses (default) or new to sort by most recently created profiles.
			'local'  => false,    // Only return local accounts.
		], $request);

		$this->logger->info('directory', ['offset' => $request['offset'], 'limit' => $request['limit'], 'order' => $request['order'], 'local' => $request['local']]);

		if ($request['local']) {
			$table     = 'owner-view';
			$condition = ['net-publish' => true];
		} else {
			$table     = 'contact';
			$condition = ['uid' => 0, 'hidden' => false, 'network' => Protocol::FEDERATED];
		}

		$params = ['limit' => [$request['offset'], $request['limit']],
			'order'           => [($request['order'] == 'active') ? 'last-item' : 'created' => true]];

		$accounts = [];
		$contacts = DBA::select($table, ['id', 'uid'], $condition, $params);
		while ($contact = DBA::fetch($contacts)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $contact['uid']);
		}
		DBA::close($contacts);

		$this->jsonExit($accounts);
	}
}

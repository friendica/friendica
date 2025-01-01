<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/preferences/
 */
class Preferences extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$user = User::getById($uid, ['language', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
		if (!empty($user['allow_cid']) || !empty($user['allow_gid']) || !empty($user['deny_cid']) || !empty($user['deny_gid'])) {
			$visibility = 'private';
		} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
			$visibility = 'unlisted';
		} else {
			$visibility = 'public';
		}

		$sensitive = false;
		$language  = $user['language'];
		$media     = DI::pConfig()->get($uid, 'nsfw', 'disable') ? 'show_all' : 'default';
		$spoilers  = (bool)DI::pConfig()->get($uid, 'system', 'disable_cw');

		$preferences = new \Friendica\Object\Api\Mastodon\Preferences($visibility, $sensitive, $language, $media, $spoilers);

		$this->jsonExit($preferences);
	}
}

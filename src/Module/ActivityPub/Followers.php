<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

/**
 * ActivityPub Followers
 */
class Followers extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// @TODO: Replace with parameter from router
		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = !empty($request['page']) ? (int)$request['page'] : null;

		$followers = ActivityPub\Transmitter::getContacts($owner, [Contact::FOLLOWER, Contact::FRIEND], 'followers', $page, (string)HTTPSignature::getSigner('', $_SERVER));

		$this->jsonExit($followers, 'application/activity+json');
	}
}

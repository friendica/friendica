<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub featured posts
 */
class Featured extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = !empty($request['page']) ? (int)$request['page'] : null;

		$featured = ActivityPub\Transmitter::getFeatured($owner, $page);

		$this->jsonExit($featured, 'application/activity+json');
	}
}

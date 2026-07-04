<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Model\Subscription;
use Friendica\Network\HTTPException\UnprocessableEntityException;
use Psr\Log\LoggerInterface;

class Application extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly Database $dba)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $id Application ID
	 *
	 * @return \Friendica\Object\Api\Mastodon\Application
	 * @throws UnprocessableEntityException
	 */
	public function createFromApplicationId(int $id): \Friendica\Object\Api\Mastodon\Application
	{
		$application = $this->dba->selectFirst('application', ['client_id', 'client_secret', 'id', 'name', 'redirect_uri', 'website'], ['id' => $id]);
		if (!$this->dba->isResult($application)) {
			throw new UnprocessableEntityException(sprintf("ID '%s' not found", $id));
		}

		return new \Friendica\Object\Api\Mastodon\Application(
			$application['name'],
			$application['client_id'],
			$application['client_secret'],
			$application['id'],
			$application['redirect_uri'],
			$application['website'],
			Subscription::getPublicVapidKey(),
		);
	}
}

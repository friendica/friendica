<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Application
 *
 * @see https://docs.joinmastodon.org/entities/application
 */
class Application extends BaseDataTransferObject
{
	/** @var string */
	protected $client_id;
	/** @var string */
	protected $client_secret;
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $redirect_uri;
	/** @var string */
	protected $website;
	/** @var string */
	protected $vapid_key;

	/**
	 * Creates an application entry
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $name, string $client_id = null, string $client_secret = null, int $id = null, string $redirect_uri = null, string $website = null, string $vapid_key = null)
	{
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->id            = (string)$id;
		$this->name          = $name;
		$this->redirect_uri  = $redirect_uri;
		$this->website       = $website;
		$this->vapid_key     = $vapid_key;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$application = parent::toArray();

		if (empty($application['id'])) {
			unset($application['client_id']);
			unset($application['client_secret']);
			unset($application['id']);
			unset($application['redirect_uri']);
		}

		if (empty($application['vapid_key'])) {
			unset($application['vapid_key']);
		}

		return $application;
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Token
 *
 * @see https://docs.joinmastodon.org/entities/token/
 */
class Token extends BaseDataTransferObject
{
	/** @var string */
	protected $access_token;
	/** @var string */
	protected $token_type;
	/** @var string */
	protected $scope;
	/** @var int (timestamp) */
	protected $created_at;
	/** @var string */
	protected $me;

	/**
	 * Creates a token record
	 *
	 * @param string $access_token Token string
	 * @param string $token_type   Always "Bearer"
	 * @param string $scope        Combination of "read write follow push"
	 * @param string $created_at   Creation date of the token
	 * @param string $me           Actor profile of the token owner
	 */
	public function __construct(
		string $access_token,
		string $token_type,
		string $scope,
		string $created_at,
		?string $me = null,
	) {
		$this->access_token = $access_token;
		$this->token_type   = $token_type;
		$this->scope        = $scope;
		$this->created_at   = strtotime($created_at);
		$this->me           = $me;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$token = parent::toArray();

		if (empty($token['me'])) {
			unset($token['me']);
		}

		return $token;
	}
}

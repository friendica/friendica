<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Mention
 *
 * @see https://docs.joinmastodon.org/entities/mention
 */
class Mention extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $username;
	/** @var string */
	protected $url = null;
	/** @var string */
	protected $acct = null;

	/**
	 * Creates a mention record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag     tag-view record
	 * @param array   $contact contact table record
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $tag, array $contact)
	{
		$this->id       = (string) ($contact['id'] ?? 0);
		$this->username = $tag['name'];
		$this->url      = $tag['url'];

		if (!empty($contact)) {
			$this->acct
				= str_starts_with((string) $contact['url'], $baseUrl . '/')
					? $contact['nick']
					: $contact['addr'];

			$this->username = $contact['nick'];
		} else {
			$this->acct = '';
		}
	}
}

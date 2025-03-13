<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;

/**
 * Class Tag
 *
 * @see https://docs.joinmastodon.org/entities/tag
 */
class Tag extends BaseDataTransferObject
{
	/** @var string */
	protected $name;
	/** @var string */
	protected $url = null;
	/** @var array */
	protected $history = [];
	/** @var bool */
	protected $following = false;

	/**
	 * Creates a hashtag record from an tag-view record.
	 *
	 * @param BaseURL $baseUrl
	 * @param array   $tag       tag-view record
	 * @param array   $history
	 * @param bool    $following "true" if the user is following this tag
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(BaseURL $baseUrl, array $tag, array $history = [], bool $following = false)
	{
		$this->name      = $tag['name'];
		$this->url       = $baseUrl . '/search?tag=' . urlencode(strtolower($this->name));
		$this->history   = $history;
		$this->following = $following;
	}

	public function toArray(): array
	{
		if (empty($this->history)) {
			unset($this->history);
		}

		return parent::toArray();
	}
}

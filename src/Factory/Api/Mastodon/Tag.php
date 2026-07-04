<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Model\Tag as TagModel;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Tag extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly BaseURL $baseUrl)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 *
	 * @return array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$hashtags = [];
		$tags     = TagModel::getByURIId($uriId, [TagModel::HASHTAG]);
		foreach ($tags as $tag) {
			$hashtag    = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, $tag);
			$hashtags[] = $hashtag->toArray();
		}
		return $hashtags;
	}
}

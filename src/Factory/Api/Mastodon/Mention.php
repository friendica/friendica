<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\App\BaseURL;
use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Mentions;
use Friendica\Model\Contact;
use Friendica\Model\Tag;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Mention extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly BaseURL $baseUrl)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @return Mentions
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): Mentions
	{
		$mentions = new Mentions();
		$tags     = Tag::getByURIId($uriId, [Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION]);
		foreach ($tags as $tag) {
			$contact    = Contact::getByURL($tag['url'], false);
			$mentions[] = new \Friendica\Object\Api\Mastodon\Mention($this->baseUrl, $tag, $contact);
		}
		return $mentions;
	}
}

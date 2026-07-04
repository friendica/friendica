<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Model\Contact;
use Friendica\Model\Tag;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class Mention extends BaseFactory
{
	public function __construct(LoggerInterface $logger)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $uriId Uri-ID of the item
	 * @return Array
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId): array
	{
		$mentions = [];
		$tags     = Tag::getByURIId($uriId, [Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION]);
		foreach ($tags as $tag) {
			$indices    = [];
			$contact    = Contact::getByURL($tag['url'], false);
			$object     = new \Friendica\Object\Api\Twitter\Mention($tag, $contact, $indices);
			$mentions[] = $object->toArray();
		}
		return $mentions;
	}
}

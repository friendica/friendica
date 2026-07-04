<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use ImagickException;
use Psr\Log\LoggerInterface;

class Conversation extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly Database $dba, private readonly Status $mstdnStatusFactory, private readonly Account $mstdnAccountFactory)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $id Conversation id
	 *
	 * @return \Friendica\Object\Api\Mastodon\Conversation
	 * @throws ImagickException|HTTPException\InternalServerErrorException|HTTPException\NotFoundException
	 */
	public function createFromConvId(int $id): \Friendica\Object\Api\Mastodon\Conversation
	{
		$accounts    = [];
		$unread      = false;
		$last_status = null;

		$ids = [];

		$mails = $this->dba->select('mail', ['id', 'from-url', 'uid', 'seen'], ['convid' => $id], ['order' => ['id' => true]]);
		while ($mail = $this->dba->fetch($mails)) {
			if (!$mail['seen']) {
				$unread = true;
			}

			$id = Contact::getIdForURL($mail['from-url'], 0, false);
			if (in_array($id, $ids)) {
				continue;
			}

			$ids[] = $id;

			if (empty($last_status)) {
				$last_status = $this->mstdnStatusFactory->createFromMailId($mail['id']);
			}

			$accounts[] = $this->mstdnAccountFactory->createFromContactId($id, 0);
		}

		return new \Friendica\Object\Api\Mastodon\Conversation($id, $accounts, $unread, $last_status);
	}
}

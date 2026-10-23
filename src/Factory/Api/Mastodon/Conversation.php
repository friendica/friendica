<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
	 * Creates a conversation from all messages that share the thread of a given message.
	 * Like in the message module the messages are grouped by their parent URI.
	 * The conversation id is the id of the newest message of the thread.
	 *
	 * @param int $uid  User id
	 * @param int $id   Id of a message of the thread
	 *
	 * @return \Friendica\Object\Api\Mastodon\Conversation
	 * @throws ImagickException|HTTPException\InternalServerErrorException|HTTPException\NotFoundException
	 */
	public function createFromMailId(int $uid, int $id): \Friendica\Object\Api\Mastodon\Conversation
	{
		$thread = $this->dba->selectFirst('mail', ['parent-uri'], ['id' => $id, 'uid' => $uid]);
		if (!$thread) {
			throw new HTTPException\NotFoundException('Conversation not found.');
		}

		$accounts    = [];
		$unread      = false;
		$last_status = null;
		$last_id     = $id;

		$ids = [];

		$mails = $this->dba->select('mail', ['id', 'from-url', 'seen'], ['parent-uri' => $thread['parent-uri'], 'uid' => $uid], ['order' => ['id' => true]]);
		while ($mail = $this->dba->fetch($mails)) {
			if (!$mail['seen']) {
				$unread = true;
			}

			if (empty($last_status)) {
				$last_id     = $mail['id'];
				$last_status = $this->mstdnStatusFactory->createFromMailId($mail['id']);
			}

			$contactId = Contact::getIdForURL($mail['from-url'], 0, false);
			if (in_array($contactId, $ids)) {
				continue;
			}

			$ids[] = $contactId;

			$accounts[] = $this->mstdnAccountFactory->createFromContactId($contactId, 0);
		}
		$this->dba->close($mails);

		return new \Friendica\Object\Api\Mastodon\Conversation((string) $last_id, $accounts, $unread, $last_status);
	}
}

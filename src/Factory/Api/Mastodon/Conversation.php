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
	/** @var Database */
	private $dba;
	/** @var Status */
	private $mstdnStatusFactory;
	/** @var Account */
	private $mstdnAccountFactory;

	public function __construct(LoggerInterface $logger, Database $dba, Status $mstdnStatusFactory, Account $mstdnAccountFactoryFactory)
	{
		parent::__construct($logger);
		$this->dba                 = $dba;
		$this->mstdnStatusFactory  = $mstdnStatusFactory;
		$this->mstdnAccountFactory = $mstdnAccountFactoryFactory;
	}

	/**
	 * @param int $id  Conversation id
	 * @param int $uid Current user id, used to exclude the caller's own contact from the
	 *                 returned participant list (Mastodon's spec has `accounts` list the
	 *                 *other* participants, not the caller themselves).
	 *
	 * @return \Friendica\Object\Api\Mastodon\Conversation
	 * @throws ImagickException|HTTPException\InternalServerErrorException|HTTPException\NotFoundException
	 */
	public function createFromConvId(int $id, int $uid = 0): \Friendica\Object\Api\Mastodon\Conversation
	{
		$accounts    = [];
		$unread      = false;
		$last_status = null;

		$ids    = [];
		$selfId = $uid ? Contact::getPublicIdByUserId($uid) : 0;

		$mails = $this->dba->select('mail', ['id', 'from-url', 'uid', 'seen'], ['convid' => $id], ['order' => ['id' => true]]);
		while ($mail = $this->dba->fetch($mails)) {
			if (!$mail['seen']) {
				$unread = true;
			}

			// Bug fix: this used to reassign the method's own $id parameter here, so the
			// Conversation object returned below carried the last distinct sender's contact
			// id instead of the actual conversation id -- breaking mark-read/delete (wrong
			// convid) and list rendering (duplicate/colliding React keys across conversations).
			$contactId = Contact::getIdForURL($mail['from-url'], 0, false);
			if (in_array($contactId, $ids)) {
				continue;
			}

			$ids[] = $contactId;

			if (empty($last_status)) {
				$last_status = $this->mstdnStatusFactory->createFromMailId($mail['id']);
			}

			if ($contactId != $selfId) {
				$accounts[] = $this->mstdnAccountFactory->createFromContactId($contactId, 0);
			}
		}

		return new \Friendica\Object\Api\Mastodon\Conversation($id, $accounts, $unread, $last_status);
	}
}

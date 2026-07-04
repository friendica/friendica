<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Twitter;

use Friendica\BaseFactory;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Database\Database;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class DirectMessage extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly Database $dba, /** @var twitterUser entity */
		private readonly TwitterUser $twitterUser)
	{
		parent::__construct($logger);
	}

	/**
	 * Create a direct message from a given mail id
	 *
	 * @todo Processing of "getUserObjects" (true/false) and "getText" (html/plain)
	 *
	 * @param int    $id        Mail id
	 * @param int    $uid       Mail user
	 * @param string $text_mode Either empty, "html" or "plain"
	 *
	 * @return \Friendica\Object\Api\Twitter\DirectMessage
	 */
	public function createFromMailId(int $id, int $uid, string $text_mode = ''): \Friendica\Object\Api\Twitter\DirectMessage
	{
		$mail = $this->dba->selectFirst('mail', [], ['id' => $id, 'uid' => $uid]);
		if (!$mail) {
			throw new HTTPException\NotFoundException('Direct message with ID ' . $mail . ' not found.');
		}

		$title = '';
		$text  = '';

		if (!empty($text_mode)) {
			$title = $mail['title'];
			if ($text_mode == 'html') {
				$text = BBCode::convertForUriId($mail['uri-id'], $mail['body'], BBCode::TWITTER_API);
			} elseif ($text_mode == 'plain') {
				$text = HTML::toPlaintext(BBCode::convertForUriId($mail['uri-id'], $mail['body'], BBCode::TWITTER_API), 0);
			}
		} else {
			$text = $mail['title'] . "\n" . HTML::toPlaintext(BBCode::convertForUriId($mail['uri-id'], $mail['body'], BBCode::TWITTER_API), 0);
		}

		$pcid = Contact::getPublicIdByUserId($uid);

		if ($mail['author-id'] == $pcid) {
			$sender    = $this->twitterUser->createFromUserId($uid, true);
			$recipient = $this->twitterUser->createFromContactId($mail['contact-id'], $uid, true);
		} else {
			$sender    = $this->twitterUser->createFromContactId($mail['author-id'], $uid, true);
			$recipient = $this->twitterUser->createFromUserId($uid, true);
		}

		return new \Friendica\Object\Api\Twitter\DirectMessage($mail, $sender, $recipient, $text, $title);
	}
}

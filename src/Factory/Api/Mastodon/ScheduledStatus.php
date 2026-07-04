<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Photo;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;

class ScheduledStatus extends BaseFactory
{
	public function __construct(LoggerInterface $logger, private readonly Database $dba)
	{
		parent::__construct($logger);
	}

	/**
	 * @param int $id  Id of the delayed post
	 * @param int $uid Post user
	 *
	 * @return \Friendica\Object\Api\Mastodon\ScheduledStatus
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromDelayedPostId(int $id, int $uid): \Friendica\Object\Api\Mastodon\ScheduledStatus
	{
		$delayed_post = $this->dba->selectFirst('delayed-post', [], ['id' => $id, 'uid' => $uid]);
		if (empty($delayed_post)) {
			throw new HTTPException\NotFoundException('Scheduled status with ID ' . $id . ' not found for user ' . $uid . '.');
		}

		$parameters = Post\Delayed::getParametersForid($delayed_post['id']);
		if (empty($parameters)) {
			throw new HTTPException\NotFoundException('Scheduled status with ID ' . $id . ' not found for user ' . $uid . '.');
		}

		$media_ids         = [];
		$media_attachments = [];
		foreach ($parameters['attachments'] as $attachment) {
			$id = Photo::getIdForName($attachment['url']);

			$media_ids[]         = (string) $id;
			$media_attachments[] = DI::mstdnAttachment()->createFromPhoto($id);
		}

		$gravity = $parameters['item']['gravity'] ?? Item::GRAVITY_PARENT;

		if (isset($parameters['item']['thr-parent']) && ($gravity != Item::GRAVITY_PARENT)) {
			$in_reply_to_id = ItemURI::getIdByURI($parameters['item']['thr-parent']);
		} else {
			$in_reply_to_id = null;
		}

		return new \Friendica\Object\Api\Mastodon\ScheduledStatus($delayed_post, $parameters, $media_ids, $media_attachments, $in_reply_to_id);
	}
}

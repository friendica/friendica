<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Friendica;

use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Psr\Log\LoggerInterface;
use Friendica\Factory\Api\Twitter\User as TwitterUser;

class Activities extends BaseFactory
{
	public function __construct(LoggerInterface $logger, /** @var twitterUser entity */
		private readonly TwitterUser $twitterUser)
	{
		parent::__construct($logger);
	}

	/**
	 * Creates activities array from URI id, user id
	 *
	 * @param int $uriId Uri-ID of the item
	 * @param int $uid User id
	 * @param string $type Type of returned activities, can be 'json' or 'xml', default: json
	 *
	 * @return array Array of found activities
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromUriId(int $uriId, int $uid, string $type = 'json'): array
	{
		$activities = [
			'like'        => [],
			'dislike'     => [],
			'attendyes'   => [],
			'attendno'    => [],
			'attendmaybe' => [],
			'announce'    => [],
		];

		$condition = ['uid' => $uid, 'thr-parent-id' => $uriId, 'gravity' => Item::GRAVITY_ACTIVITY];

		$ret = Post::selectForUser($uid, ['author-id', 'verb'], $condition);

		while ($parent_item = Post::fetch($ret)) {
			// get user data and add it to the array of the activity
			$user = $this->twitterUser->createFromContactId($parent_item['author-id'], $uid, true)->toArray();
			switch ($parent_item['verb']) {
				case Activity::LIKE:
					$activities['like'][] = $user;
					break;

				case Activity::DISLIKE:
					$activities['dislike'][] = $user;
					break;

				case Activity::ATTEND:
					$activities['attendyes'][] = $user;
					break;

				case Activity::ATTENDNO:
					$activities['attendno'][] = $user;
					break;

				case Activity::ATTENDMAYBE:
					$activities['attendmaybe'][] = $user;
					break;

				case Activity::ANNOUNCE:
					$activities['announce'][] = $user;
					break;

				default:
					break;
			}
		}

		DBA::close($ret);

		if ($type == 'xml') {
			$xml_activities = [];
			foreach ($activities as $k => $v) {
				// change xml element from "like" to "friendica:like"
				$xml_activities['friendica:' . $k] = $v;
				// add user data into xml output
				$k_user = 0;
				foreach ($v as $user) {
					$xml_activities['friendica:' . $k][$k_user++ . ':user'] = $user;
				}
			}
			$activities = $xml_activities;
		}

		return $activities;
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\TimelineOrderByTypes;
use Friendica\Protocol\Activity;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Statuses extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$id = $this->parameters['id'];
		if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$request = $this->getRequest([
			'only_media'      => false, // Show only statuses with media attached? Defaults to false.
			'max_id'          => null,     // Return results older than this id
			'since_id'        => null,     // Return results newer than this id
			'min_id'          => null,     // Return results immediately newer than this id
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.
			'pinned'          => false, // Only pinned posts
			'exclude_replies' => false, // Don't show comments
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'exclude_reblogs' => false, // Undocumented parameter
			'tagged'          => false, // Undocumented parameter
			'friendica_order' => TimelineOrderByTypes::ID, // order options (defaults to ID)
		], $request);

		if ($request['pinned']) {
			$condition = ['author-id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED], 'type' => Post\Collection::FEATURED];
		} elseif ($request['only_media']) {
			$condition = ['author-id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED], 'type' => [Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]];
		} elseif (!$uid) {
			$condition = [
				'author-id' => $id, 'private' => [Item::PUBLIC, Item::UNLISTED],
				'uid'       => 0, 'network' => Protocol::FEDERATED
			];
		} else {
			$condition = ["`author-id` = ? AND (`uid` = 0 OR (`uid` = ? AND NOT `global`))", $id, $uid];
		}

		$condition = $this->addPagingConditions($request, $condition);
		$params    = $this->buildOrderAndLimitParams($request);

		if (!$request['pinned'] && !$request['only_media']) {
			if ($request['exclude_replies']) {
				$condition = DBA::mergeConditions($condition, [
					"(`gravity` = ? OR (`gravity` = ? AND `vid` = ? AND `protocol` != ?))",
					Item::GRAVITY_PARENT, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Conversation::PARCEL_DIASPORA
				]);
			} else {
				$condition = DBA::mergeConditions($condition, [
					"(`gravity` IN (?, ?) OR (`gravity` = ? AND `vid` = ? AND `protocol` != ?))",
					Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Conversation::PARCEL_DIASPORA
				]);
			}
		} elseif ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => Item::GRAVITY_PARENT]);
		}

		if ($request['pinned']) {
			$items = DBA::select('collection-view', ['uri-id'], $condition, $params);
		} elseif ($request['only_media']) {
			$items = DBA::select('media-view', ['uri-id'], $condition, $params);
		} else {
			$items = Post::selectTimelineForUser($uid, ['uri-id'], $condition, $params);
		}

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			try {
				$status = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
				$this->updateBoundaries($status, $item, $request['friendica_order']);
				$statuses[] = $status;
			} catch (\Throwable $th) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'error' => $th]);
			}
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader($request['friendica_order'] != TimelineOrderByTypes::ID);
		$this->jsonExit($statuses);
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class Tag extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['hashtag'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		/**
		 * @todo Respect missing parameters
		 * @see https://github.com/tootsuite/mastodon/blob/main/app/controllers/api/v1/timelines/tag_controller.rb
		 *
		 * There seem to be the parameters "any", "all", and "none".
		 */

		$request = $this->getRequest([
			'local'           => false, // If true, return only local statuses. Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'only_media'      => false, // If true, return only statuses with media attachments. Defaults to false.
			'max_id'          => 0,     // Return results older than this ID.
			'since_id'        => 0,     // Return results newer than this ID.
			'min_id'          => 0,     // Return results immediately newer than this ID.
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'exclude_replies' => false, // Don't show comments
		], $request);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
			AND (`network` IN (?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			$this->parameters['hashtag'], 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, $uid, 0];

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin` AND `post-user`.`uri-id` = `tag-search-view`.`uri-id`)"]);
		}

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => Item::GRAVITY_PARENT]);
		}

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);

			$params['order'] = ['uri-id'];
		}

		if (!empty($uid)) {
			$condition = DBA::mergeConditions(
				$condition,
				["NOT `author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored` OR `is-blocked`) AND `cid` = `author-id`)", $uid]
			);
		}

		$items = DBA::select('tag-search-view', ['uri-id'], $condition, $params);

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['uri-id']);
			try {
				$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		$this->jsonExit($statuses);
	}
}

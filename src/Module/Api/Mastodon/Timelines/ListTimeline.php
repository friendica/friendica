<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Module\Conversation\Timeline;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\TimelineOrderByTypes;
use Friendica\Protocol\Activity;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class ListTimeline extends BaseApi
{
	/** @var Timeline */
	protected $timeline;

	public function __construct(Timeline $timeline, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->timeline = $timeline;
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$request = $this->getRequest([
			'max_id'          => null,  // Return results older than id
			'since_id'        => null,  // Return results newer than id
			'min_id'          => null,  // Return results immediately newer than id
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.Return results older than this ID.
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'only_media'      => false, // Show only statuses with media attached? Defaults to false.
			'local'           => false, // Show only local statuses? Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'exclude_replies' => false, // Don't show comments
			'friendica_order' => TimelineOrderByTypes::ID, // Sort order options (defaults to ID)
		], $request);

		$display_quotes = self::appSupportsQuotes();

		if (substr($this->parameters['id'], 0, 6) == 'group:') {
			$items = $this->getStatusesForGroup($uid, $request);
		} elseif (substr($this->parameters['id'], 0, 8) == 'channel:') {
			$items = $this->getStatusesForChannel($uid, $request);
		} else {
			$items = $this->getStatusesForCircle($uid, $request);
		}

		$statuses = [];
		foreach ($items as $item) {
			try {
				$status = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
				$this->updateBoundaries($status, $item, $request['friendica_order']);
				$statuses[] = $status;
			} catch (\Throwable $th) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'error' => $th]);
			}
		}

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader($request['friendica_order'] != TimelineOrderByTypes::ID);
		$this->jsonExit($statuses);
	}

	private function getStatusesForGroup(int $uid, array $request): array
	{
		$cid = Contact::getPublicContactId((int)substr($this->parameters['id'], 6), $uid);

		$condition = ["(`uid` = ? OR (`uid` = ? AND NOT `global`))", 0, $uid];

		$condition1 = DBA::mergeConditions($condition, ["`owner-id` = ? AND `gravity` = ?", $cid, Item::GRAVITY_PARENT]);

		$condition2 = DBA::mergeConditions($condition, [
			"`author-id` = ? AND `gravity` = ? AND `vid` = ? AND `protocol` != ? AND `thr-parent-id` = `parent-uri-id`",
			$cid, Item::GRAVITY_ACTIVITY, Verb::getID(Activity::ANNOUNCE), Conversation::PARCEL_DIASPORA
		]);

		$condition1 = $this->addPagingConditions($request, $condition1);
		$condition2 = $this->addPagingConditions($request, $condition2);

		$sql1 = "SELECT `uri-id` FROM `post-thread-user-view` WHERE " . array_shift($condition1);
		$sql2 = "SELECT `thr-parent-id` AS `uri-id` FROM `post-user-view` WHERE " . array_shift($condition2);

		$condition = array_merge($condition1, $condition2);
		$sql       = $sql1 . " UNION " . $sql2 . " GROUP BY `uri-id` " . DBA::buildParameter($this->buildOrderAndLimitParams($request));

		return Post::toArray(DBA::p($sql, $condition));
	}

	private function getStatusesForChannel(int $uid, array $request): array
	{
		$request['friendica_order'] = TimelineOrderByTypes::ID;

		return $this->timeline->getChannelItemsForAPI(substr($this->parameters['id'], 8), $uid, $request['limit'], $request['min_id'], $request['max_id']);
	}

	private function getStatusesForCircle(int $uid, array $request): array
	{
		$condition = [
			"`uid` = ? AND `gravity` IN (?, ?) AND `contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)",
			$uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $this->parameters['id']
		];

		$condition = $this->addPagingConditions($request, $condition);
		$params    = $this->buildOrderAndLimitParams($request);

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, [
				"`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO
			]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => Item::GRAVITY_PARENT]);
		}

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin` AND `post-user`.`uri-id` = `post-user-view`.`uri-id`)"]);
		}

		$items = Post::selectTimelineForUser($uid, ['uri-id'], $condition, $params);
		return Post::toArray($items);
	}
}

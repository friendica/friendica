<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Entity\Activity as ActivityEntity;
use Friendica\Content\Conversation\Repository\Activity as ActivityRepository;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * Activity factory class.
 */
final class Activity
{
	private ActivityRepository $activityRepository;
	private UserDefinedChannel $channelRepository;
	private Database $database;
	private LoggerInterface $logger;

	/**
	 * ActivityFactory constructor.
	 *
	 * @param ActivityRepository $activityRepository
	 * @param UserDefinedChannel $channelRepository
	 */
	public function __construct(ActivityRepository $activityRepository, UserDefinedChannel $channelRepository, Database $database, LoggerInterface $logger)
	{
		$this->activityRepository = $activityRepository;
		$this->channelRepository  = $channelRepository;
		$this->database           = $database;
		$this->logger             = $logger;
	}

	/**
	 * Get activities for a user and network.
	 *
	 * @param int $uid
	 * @param string $network
	 * @return ActivityEntity
	 */
	public function getActivities(int $uid, string $network = ''): ActivityEntity
	{
		$activity = $this->activityRepository->findByUidAndNetwork($uid, $network);
		if ($activity) {
			return $activity;
		}

		$cid = Contact::getPublicIdByUserId($uid);

		$activity = new ActivityEntity(
			$uid,
			$network,
			User::getWantedLanguages($uid),
			$cid,
			DateTimeFormat::utc('now + 1 hours'),
			$this->getMedianComments($uid, 4, $network),
			$this->getMedianActivities($uid, 4, $network),
			$this->getMedianViews($uid, 4, $network),
			$this->getMedianRelationThreadScore($cid, 4, $network),
			$this->getMedianPostScore($cid, 2, $network),
		);

		$this->activityRepository->save($activity);
		return $activity;
	}

	/**
	 * Compute median comments for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median comments value.
	 */
	private function getMedianComments(int $uid, int $divider, string $network = ''): int
	{
		$condition = ["`contact-type` != ? AND `comments` > ?", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->channelRepository->addLanguageCondition($uid, $condition);
		$condition = $this->setNetworkFilter($condition, $network);

		$limit    = $this->database->count('post-engagement', $condition) / $divider;
		$post     = $this->database->selectToArray('post-engagement', ['comments'], $condition, ['order' => ['comments' => true], 'limit' => [$limit, 1]]);
		$comments = $post[0]['comments'] ?? 0;

		$this->logger->debug('Calculated median comments', ['uid' => $uid, 'network' => $network, 'divider' => $divider, 'median' => $comments, 'condition' => $condition]);
		return $comments;
	}

	/**
	 * Compute median activities for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median activities value.
	 */
	private function getMedianActivities(int $uid, int $divider, string $network = ''): int
	{
		$condition = ["`contact-type` != ? AND `activities` > ?", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->channelRepository->addLanguageCondition($uid, $condition);
		$condition = $this->setNetworkFilter($condition, $network);

		$limit      = $this->database->count('post-engagement', $condition) / $divider;
		$post       = $this->database->selectToArray('post-engagement', ['activities'], $condition, ['order' => ['activities' => true], 'limit' => [$limit, 1]]);
		$activities = $post[0]['activities'] ?? 0;

		$this->logger->debug('Calculated median activities', ['uid' => $uid, 'network' => $network, 'divider' => $divider, 'median' => $activities, 'condition' => $condition]);
		return $activities;
	}

	/**
	 * Compute median views for a user's wanted languages.
	 *
	 * @param int $uid User id.
	 * @param int $divider Divider used to determine median index.
	 * @param string $network Optional network filter.
	 * @return int Median views value.
	 */
	private function getMedianViews(int $uid, int $divider, string $network = ''): int
	{
		$condition = ["`contact-type` != ? AND `views` > ?", Contact::TYPE_COMMUNITY, 0];
		$condition = $this->channelRepository->addLanguageCondition($uid, $condition);
		$condition = $this->setNetworkFilter($condition, $network);

		$limit = $this->database->count('post-engagement', $condition) / $divider;
		$post  = $this->database->selectToArray('post-engagement', ['views'], $condition, ['order' => ['views' => true], 'limit' => [$limit, 1]]);
		$views = $post[0]['views'] ?? 0;

		$this->logger->debug('Calculated median views', ['uid' => $uid, 'network' => $network, 'divider' => $divider, 'median' => $views, 'condition' => $condition]);
		return $views;
	}

	/**
	 * Compute median relation thread score for a contact id.
	 *
	 * @param int $cid Contact id.
	 * @param int $divider Divider to compute the median index.
	 * @param string $network Optional network filter.
	 * @return int Median relation thread score.
	 */
	private function getMedianRelationThreadScore(int $cid, int $divider, string $network = ''): int
	{
		$condition = ["`relation-cid` = ? AND `relation-thread-score` > ?", $cid, 0];
		$condition = $this->setNetworkFilter($condition, $network);

		$limit    = $this->database->count('contact-relation', $condition) / $divider;
		$relation = $this->database->selectToArray('contact-relation', ['relation-thread-score'], $condition, ['order' => ['relation-thread-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['relation-thread-score'] ?? 0;

		$this->logger->debug('Calculated median score', ['cid' => $cid, 'network' => $network, 'divider' => $divider, 'median' => $score, 'condition' => $condition]);
		return $score;
	}

	/**
	 * Compute a median-like post score for a contact id.
	 *
	 * @param int $cid Contact id.
	 * @param int $divider Divider to compute the median index.
	 * @param string $network Optional network filter.
	 * @return int Median post score.
	 */
	private function getMedianPostScore(int $cid, int $divider, string $network = ''): int
	{
		$condition = ["`relation-cid` = ? AND `post-score` > ?", $cid, 0];
		$condition = $this->setNetworkFilter($condition, $network);

		$limit    = $this->database->count('contact-relation', $condition) / $divider;
		$relation = $this->database->selectToArray('contact-relation', ['post-score'], $condition, ['order' => ['post-score' => true], 'limit' => [$limit, 1]]);
		$score    = $relation[0]['post-score'] ?? 0;

		$this->logger->debug('Calculated median score', ['cid' => $cid, 'network' => $network, 'divider' => $divider, 'median' => $score, 'condition' => $condition]);
		return $score;
	}

	/**
	 * Set network filter for database queries.
	 *
	 * @param array $condition Existing query condition.
	 * @param string $network Network to filter by.
	 * @return array Updated query condition with network filter applied.
	 */
	private function setNetworkFilter(array $condition, string $network): array
	{
		switch ($network) {
			case '':
			case Protocol::DFRN:
				$network_condition = ["(`network` IN (?, ?, ?) OR `network` IS NULL)", Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA];
				break;

			case Protocol::ACTIVITYPUB:
				$network_condition = ["(`network` IN (?, ?) OR `network` IS NULL)", Protocol::ACTIVITYPUB, Protocol::DFRN];
				break;

			case Protocol::DIASPORA:
				$network_condition = ["(`network` IN (?, ?) OR `network` IS NULL)", Protocol::DFRN, Protocol::DIASPORA];
				break;

			default:
				$network_condition = ["(`network` = ? OR `network` IS NULL)", $network];
		}

		return DBA::mergeConditions($condition, $network_condition);
	}
}

<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Content\Conversation\Factory\Activity as ActivityFactory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Content\Conversation\Entity\Channel;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

/**
 * SystemChannelPost factory class.
 *
 * Evaluates posts and stores them into system channels (e.g. whatshot,
 * foryou, discover) for users based on engagement, relations and
 * repository thresholds.
 *
 * @package Friendica\Content\Conversation\Factory
 */
final class SystemChannelPost
{
	private LoggerInterface $logger;
	private UserDefinedChannel $channelRepository;
	private Database $dba;
	private IManageConfigValues $config;
	private ActivityFactory $activityFactory;

	/**
	 * SystemChannelPost constructor.
	 *
	 * @param Database $dba Database access object.
	 * @param UserDefinedChannel $channel Channel repository.
	 * @param LoggerInterface $logger Logger instance.
	 * @param IManageConfigValues $config Configuration manager.
	 * @param ActivityFactory $activityFactory Activity factory.
	 */
	public function __construct(Database $dba, UserDefinedChannel $channel, LoggerInterface $logger, IManageConfigValues $config, ActivityFactory $activityFactory)
	{
		$this->dba               = $dba;
		$this->logger            = $logger;
		$this->channelRepository = $channel;
		$this->config            = $config;
		$this->activityFactory   = $activityFactory;
	}

	/**
	 * Add a post to matching system channels for one or many users.
	 *
	 * When system channel caching is enabled this computes whether a post
	 * should be stored in various system channels for the given user(s)
	 * and inserts cache entries into `system-channel-post`.
	 *
	 * @param array $engagement post-engagement record
	 * @param int $gravity Gravity of the post
	 * @param int $post_uid User id context (0 for all users).
	 * @param string $network Network identifier.
	 * @param int $reshare_id Optional reshare id.
	 * @return void
	 */
	public function add(array $engagement, int $gravity, int $post_uid, string $network, int $reshare_id = 0): void
	{
		$timestamp = microtime(true);
		if (!$this->config->get('system', 'system_channel_cache')) {
			return;
		}

		$this->logger->info('Start: Check for system channel posts', ['uri-id' => $engagement['uri-id'], 'post_uid' => $post_uid, 'reshare_id' => $reshare_id]);

		$owner = $reshare_id ?: $engagement['owner-id'];

		$post = Post::selectFirstPost(['created', 'received', 'commented', 'network', 'private'], ['uri-id' => $engagement['uri-id']]);
		if ($post === false || $post === []) {
			$this->logger->debug('Post not found', ['uri-id' => $engagement['uri-id'], 'post_uid' => $post_uid, 'reshare_id' => $reshare_id]);
			return;
		}

		if (in_array($network, [Protocol::MAIL, Protocol::FEED])) {
			$channels = [Channel::QUIETSHARERS, Channel::IMAGE, Channel::VIDEO, Channel::AUDIO, Channel::LANGUAGE];
		} elseif ($engagement['restricted']) {
			$channels = [Channel::FORYOU, Channel::QUIETSHARERS, Channel::IMAGE, Channel::VIDEO, Channel::AUDIO, Channel::LANGUAGE];
		} elseif ($gravity === Item::GRAVITY_PARENT) {
			$channels = [Channel::WHATSHOT, Channel::FORYOU, Channel::DISCOVER, Channel::FOLLOWERS, Channel::SHARERSOFSHARERS, Channel::QUIETSHARERS, Channel::IMAGE, Channel::VIDEO, Channel::AUDIO, Channel::LANGUAGE];
		} else {
			$channels = [Channel::WHATSHOT, Channel::FORYOU, Channel::DISCOVER];
		}

		$uids = $this->channelRepository->getUsersForPost($engagement['uri-id'], $post_uid, $post['network'], $post['private']);
		if (!$uids) {
			return;
		}

		$cids = $this->getPublicCids($uids);
		if (!$cids) {
			return;
		}

		// Generally available data
		$existing         = $this->getExistingChannelPostUids($engagement['uri-id'], $uids);
		$inTimelineUids   = $this->getInTimelineUids($engagement['uri-id'], $uids);
		$relationState    = $this->getRelationStates($owner, $uids);
		$followerRelation = $this->getFollowerRelation($owner, $cids);
		$sharerRelation   = $this->getSharerRelation($owner, $cids);

		// Channel specific data (Data is only fetched when the channel is selected)
		$forYouuserContacts        = $this->getForYouUserContacts($channels, $owner, $uids);
		$sharersOfSharersMaxScores = $this->getSharersOfSharersMaxScores($channels, $owner, $cids);

		$added         = 0;
		$insert_time   = 0;
		$prep_time     = microtime(true) - $timestamp;
		$activity_time = 0;
		$rows          = [];
		foreach ($uids as $uid) {
			$in_timeline = isset($inTimelineUids[$uid]);
			if ($engagement['restricted'] && !$in_timeline) {
				continue;
			}
			$timestamp2 = microtime(true);
			$activities = $this->activityFactory->getActivities($uid, $network);
			$activity_time += (microtime(true) - $timestamp2);

			$isSharer   = in_array($relationState[$uid] ?? null, [Contact::SHARING, Contact::FRIEND]);
			$isFollower = ($relationState[$uid] ?? null) == Contact::FOLLOWER;

			foreach ($channels as $channel) {
				if (isset($existing[$uid]) && in_array($channel, $existing[$uid])) {
					continue;
				}

				if ($channel !== Channel::LANGUAGE && !in_array($engagement['language'], $activities->languages)) {
					continue;
				}

				$store = false;
				switch ($channel) {
					case Channel::WHATSHOT:
						$store = ($engagement['comments'] > $activities->medianComments || $engagement['activities'] > $activities->medianActivities || $engagement['views'] > $activities->medianViews) && $engagement['contact-type'] != Contact::TYPE_COMMUNITY;
						break;

					case Channel::FORYOU:
						if (isset($sharerRelation[$activities->cid])) {
							$store = $sharerRelation[$activities->cid]['relation-thread-score'] > $activities->medianThreadScore;
							if (!$store && $isSharer) {
								$store = ($engagement['comments'] >= $activities->medianComments || $engagement['activities'] >= $activities->medianActivities || $engagement['views'] >= $activities->medianViews);
							}
						}

						if (!$store && isset($forYouuserContacts[$uid])) {
							$store = $forYouuserContacts[$uid]['channel-frequency'] == Contact\User::FREQUENCY_ALWAYS || $forYouuserContacts[$uid]['notify_new_posts'];
						}
						break;

					case Channel::DISCOVER:
						if (!$isSharer) {
							$store = isset($sharerRelation[$activities->cid]) && $sharerRelation[$activities->cid]['relation-thread-score'] > $activities->medianThreadScore;
							if (!$store) {
								$store = isset($followerRelation[$activities->cid]) && $followerRelation[$activities->cid]['relation-thread-score'] > $activities->medianThreadScore;
							}
							if (!$store) {
								$store = isset($followerRelation[$activities->cid]) && $isFollower && $followerRelation[$activities->cid]['relation-thread-score'] > 0;
							}
							if (!$store && ($engagement['comments'] >= $activities->medianComments || $engagement['activities'] >= $activities->medianActivities) || $engagement['views'] >= $activities->medianViews) {
								$store = isset($followerRelation[$activities->cid]) && $followerRelation[$activities->cid]['relation-thread-score'] > 0;
								if (!$store) {
									$store = isset($sharerRelation[$activities->cid]) && $sharerRelation[$activities->cid]['relation-thread-score'] > 0;
								}
							}
						}
						break;

					case Channel::FOLLOWERS:
						$store = $isFollower;
						break;

					case Channel::SHARERSOFSHARERS:
						if (isset($sharersOfSharersMaxScores[$activities->cid]) && !$isSharer) {
							$store = $sharersOfSharersMaxScores[$activities->cid] >= $activities->medianThreadScore;
						}
						break;

					case Channel::QUIETSHARERS:
						if (isset($sharerRelation[$activities->cid]) && $isSharer) {
							$store = $sharerRelation[$activities->cid]['post-score'] <= $activities->medianPostScore;
						}
						break;

					case Channel::IMAGE:
						$store = $engagement['media-type'] & 1;
						break;

					case Channel::VIDEO:
						$store = $engagement['media-type'] & 2;
						break;

					case Channel::AUDIO:
						$store = $engagement['media-type'] & 4;
						break;

					case Channel::LANGUAGE:
						$store = $engagement['language'] === User::getLanguageCode($uid);
						break;
				}

				if (!$store) {
					continue;
				}

				$cache = [
					'channel'     => $channel,
					'uid'         => $uid,
					'uri-id'      => $engagement['uri-id'],
					'in-timeline' => $in_timeline,
					'created'     => $post['created'],
					'received'    => $post['received'],
					'commented'   => $post['commented'],
				];
				$this->logger->debug('Added system channel post', ['uri-id' => $engagement['uri-id'], 'cache' => $cache]);
				$rows[] = $cache;
				$added++;
				if (count($rows) === 100) {
					$timestamp2 = microtime(true);
					$ret        = $this->dba->batchInsert('system-channel-post', $rows, Database::INSERT_IGNORE);
					$this->logger->debug('Stored system channel posts', ['uri-id' => $engagement['uri-id'], 'rows' => count($rows), 'ret' => $ret]);
					$insert_time += (microtime(true) - $timestamp2);
					$rows = [];
				}
			}
		}

		if ($rows) {
			$timestamp2 = microtime(true);
			$ret        = $this->dba->batchInsert('system-channel-post', $rows, Database::INSERT_IGNORE);
			$this->logger->debug('Stored system channel posts', ['uri-id' => $engagement['uri-id'], 'rows' => count($rows), 'ret' => $ret]);
			$insert_time += (microtime(true) - $timestamp2);
		}

		$total = microtime(true) - $timestamp;
		$this->logger->info('End: Check for system channel posts', ['uri-id' => $engagement['uri-id'], 'added' => $added, 'total' => number_format($total, 3), 'preparation' => number_format($prep_time, 3), 'activity' => number_format($activity_time, 3), 'insert' => number_format($insert_time, 3)]);
	}

	/**
	 * Return the maximum relation-thread-score per target contact id (SHARERSOFSHARERS channel).
	 *
	 * Considers only contacts with whom the owner interacted recently
	 * (within the configured sharer_interaction_days window).
	 *
	 * @param array $channels Active channel list; skips query when SHARERSOFSHARERS is absent.
	 * @param int   $owner    Public contact id of the post owner.
	 * @param array $cids     Public contact ids to restrict the target lookup to.
	 * @return array<int, int> Map of target contact id => max relation-thread-score.
	 */
	private function getSharersOfSharersMaxScores(array $channels, int $owner, array $cids): array
	{
		if (!in_array(Channel::SHARERSOFSHARERS, $channels)) {
			return [];
		}

		$interactionLimit = DateTimeFormat::utc('now - ' . $this->config->get('channel', 'sharer_interaction_days') . ' day');

		$maxScoreByTarget = [];
		if (!empty($cids)) {
			$placeholders = str_repeat('?,', count($cids) - 1) . '?';
			$query        = 'SELECT cr2.`relation-cid`, MAX(cr2.`relation-thread-score`) as max_score
				FROM `contact-relation` cr1
				INNER JOIN `contact-relation` cr2 ON cr1.`relation-cid` = cr2.`cid`
				WHERE cr1.`cid` = ? AND cr1.`follows` AND cr1.`last-interaction` > ?
				AND cr2.`follows` AND cr2.`relation-cid` IN (' . $placeholders . ')
				GROUP BY cr2.`relation-cid`';
			$relations = $this->dba->p($query, $owner, $interactionLimit, ...$cids);
			while ($relation = $this->dba->fetch($relations)) {
				$maxScoreByTarget[(int) $relation['relation-cid']] = (int) $relation['max_score'];
			}
			$this->dba->close($relations);
		}

		return $maxScoreByTarget;
	}

	/**
	 * Return user-contact data for the owner/uid combinations (FORYOU channel).
	 *
	 * @param array $channels Active channel list; skips query when FORYOU is absent.
	 * @param int   $owner    Public contact id of the post owner.
	 * @param array $uids     User ids to restrict the lookup to.
	 * @return array<int, array{channel-frequency: int, notify_new_posts: bool}> Map of uid => contact settings.
	 */
	private function getForYouUserContacts(array $channels, int $owner, array $uids): array
	{
		if (!in_array(Channel::FORYOU, $channels)) {
			return [];
		}

		$forYouUserContacts = [];
		$userContacts       = $this->dba->select('user-contact', ['uid', 'channel-frequency', 'notify_new_posts'], ['cid' => $owner, 'uid' => $uids]);
		while ($userContact = $this->dba->fetch($userContacts)) {
			$forYouUserContacts[(int) $userContact['uid']] = [
				'channel-frequency' => (int) $userContact['channel-frequency'],
				'notify_new_posts'  => (bool) $userContact['notify_new_posts'],
			];
		}
		$this->dba->close($userContacts);

		return $forYouUserContacts;
	}

	/**
	 * Return the public contact ids (pid) for a list of user ids.
	 *
	 * @param array $uids List of user ids.
	 * @return int[] List of public contact ids.
	 */
	private function getPublicCids(array $uids): array
	{
		$cids = [];
		$rows = $this->dba->select('account-user-view', ['pid'], ['self' => true, 'uid' => $uids]);
		while ($row = $this->dba->fetch($rows)) {
			$cids[] = (int) $row['pid'];
		}
		$this->dba->close($rows);

		return $cids;
	}

	/**
	 * Return the sharer relation data for the owner/relation-cid combinations.
	 *
	 * @param int   $owner Public contact id of the post owner.
	 * @param array $cids  Public contact ids to restrict the lookup to.
	 * @return array<int, array> Map of relation-cid => relation data array.
	 */
	private function getSharerRelation(int $owner, array $cids): array
	{
		$ownerFollows = [];
		$relations    = $this->dba->select('contact-relation', ['relation-cid', 'last-interaction', 'follows', 'score', 'relation-score', 'thread-score', 'relation-thread-score', 'post-score'], ['cid' => $owner, 'relation-cid' => $cids]);
		while ($relation = $this->dba->fetch($relations)) {
			$ownerFollows[$relation['relation-cid']] = $relation;
		}
		$this->dba->close($relations);

		return $ownerFollows;
	}

	/**
	 * Return the follower relation data for the owner/cid combinations.
	 *
	 * @param int   $owner Public contact id of the post owner.
	 * @param array $cids  Public contact ids to restrict the lookup to.
	 * @return array<int, array> Map of cid => relation data array.
	 */
	private function getFollowerRelation(int $owner, array $cids): array
	{
		$ownerShares = [];
		$relations   = $this->dba->select('contact-relation', ['cid', 'last-interaction', 'follows', 'score', 'relation-score', 'thread-score', 'relation-thread-score', 'post-score'], ['relation-cid' => $owner, 'cid' => $cids]);
		while ($relation = $this->dba->fetch($relations)) {
			$ownerShares[$relation['cid']] = $relation;
		}
		$this->dba->close($relations);

		return $ownerShares;
	}

	/**
	 * Return channel entries already stored for the given post, keyed by uid.
	 *
	 * @param int   $uriId URI-id of the post.
	 * @param array $uids  List of user ids to check.
	 * @return array<int, int[]> Map of uid to list of channel ids.
	 */
	private function getExistingChannelPostUids(int $uriId, array $uids): array
	{
		if (empty($uids)) {
			return [];
		}

		$existing = [];
		$posts    = $this->dba->select('system-channel-post', ['uid', 'channel'], ['uri-id' => $uriId, 'uid' => $uids]);
		while ($entry = $this->dba->fetch($posts)) {
			$existing[$entry['uid']][] = $entry['channel'];
		}
		$this->dba->close($posts);

		return $existing;
	}

	/**
	 * Return the relation states between the owner and the users.
	 *
	 * @param int   $owner Public contact id of the post owner.
	 * @param array $uids  User ids to restrict the lookup to.
	 * @return array<int, int> Map of uid => relation state.
	 */
	private function getRelationStates(int $owner, array $uids): array
	{
		$relation = [];
		$accounts = $this->dba->select('account-user-view', ['uid', 'rel'], ['pid' => $owner, 'uid' => $uids]);
		while ($follower = $this->dba->fetch($accounts)) {
			$relation[$follower['uid']] = $follower['rel'];
		}
		$this->dba->close($accounts);

		return $relation;
	}

	/**
	 * Return the set of uids for which the post appears in the user's timeline.
	 *
	 * @param int   $uriId URI-id of the post.
	 * @param array $uids  User ids to restrict the lookup to.
	 * @return array<int, true> Map of uid => true for matching users.
	 */
	private function getInTimelineUids(int $uriId, array $uids): array
	{
		$inTimelineUids = [];
		$condition      = ["`parent-uri-id` = ? AND NOT `verb` IN (?, ?, ?)", $uriId, Activity::FOLLOW, Activity::VIEW, Activity::READ];
		$condition      = DBA::mergeConditions($condition, ['uid' => $uids]);
		$timelinePosts  = Post::select(['uid'], $condition);
		while ($timelinePost = $this->dba->fetch($timelinePosts)) {
			$inTimelineUids[$timelinePost['uid']] = true;
		}
		$this->dba->close($timelinePosts);

		return $inTimelineUids;
	}
}

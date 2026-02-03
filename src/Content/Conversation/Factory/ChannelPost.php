<?php

declare(strict_types=1);

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Conversation\Factory;

use Friendica\Content\Conversation\Entity\UserDefinedChannel as UserDefinedChannelEntity;
use Friendica\Content\Conversation\Repository\UserDefinedChannel;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Psr\Log\LoggerInterface;

/**
 * ChannelPost factory class.
 *
 * Handles caching of posts into user-defined channels based on engagement
 * and matching rules.
 *
 * @package Friendica\Content\Conversation\Factory
 */
final class ChannelPost
{
	private LoggerInterface $logger;
	private UserDefinedChannel $channelRepository;
	private Database $dba;
	private IManageConfigValues $config;

	/**
	 * ChannelPost constructor.
	 *
	 * @param Database $dba Database access object.
	 * @param UserDefinedChannel $channel Channel repository.
	 * @param LoggerInterface $logger Logger instance.
	 * @param IManageConfigValues $config Configuration manager.
	 */
	public function __construct(Database $dba, UserDefinedChannel $channel, LoggerInterface $logger, IManageConfigValues $config)
	{
		$this->dba               = $dba;
		$this->logger            = $logger;
		$this->channelRepository = $channel;
		$this->config            = $config;
	}

	/**
	 * Add a post to matching user-defined channels.
	 *
	 * This will insert entries into the `channel-post` cache table when the
	 * system's channel caching is enabled and matching channels are found.
	 *
	 * @param array $engagement post-engagement record
	 * @param int $gravity Gravity of the post
	 * @param int $uid User id context.
	 * @param int $reshare_id Optional reshare id.
	 * @return void
	 */
	public function add(array $engagement, int $gravity, int $uid, int $reshare_id = 0): void
	{
		if (!$this->config->get('system', 'channel_cache')) {
			return;
		}

		$this->logger->debug('Adding channel post', ['uri-id' => $engagement['uri-id'], 'uid' => $uid, 'reshare_id' => $reshare_id]);

		$post = Post::selectFirstPost(['created', 'received', 'commented', 'network', 'private'], ['uri-id' => $engagement['uri-id']]);
		if ($post === false || $post === []) {
			$this->logger->debug('Post not found', ['uri-id' => $engagement['uri-id'], 'uid' => $uid, 'reshare_id' => $reshare_id]);
			return;
		}

		$uids = $this->channelRepository->getUsersForPost($engagement['uri-id'], $uid, $post['network'], $post['private']);

		$this->logger->debug('Found uids for channel post', ['uri-id' => $engagement['uri-id'], 'private' => $post['private'], 'network' => $post['network'], 'uids' => $uids]);

		$language = $engagement['language'] !== L10n::UNDETERMINED_LANGUAGE ? $engagement['language'] : '';
		$tags     = array_column(Tag::getByURIId($engagement['uri-id'], [Tag::HASHTAG]), 'name');
		$circles  = ($gravity != Item::GRAVITY_PARENT) ? [UserDefinedChannelEntity::CIRCLE_CREATION, UserDefinedChannelEntity::CIRCLE_POSTS, UserDefinedChannelEntity::CIRCLE_ACTIVITY] : [];

		$channels = $this->channelRepository->getMatchingChannels($engagement['searchtext'], $language, $tags, $engagement['media-type'], $engagement['owner-id'], $reshare_id, $uids, $circles);
		if (!($channels instanceof \Friendica\Content\Conversation\Collection\UserDefinedChannels) || $channels->count() === 0) {
			$this->logger->debug('No channels found', ['uri-id' => $engagement['uri-id'], 'uids' => $uids, 'reshare_id' => $reshare_id]);
			return;
		}

		foreach ($channels as $channel) {
			if (in_array($channel->circle, [-3, -4, -5]) && !Post::exists(['parent-uri-id' => $engagement['uri-id'], 'uid' => $channel->uid])) {
				continue;
			}

			if ($channel->circle === -1 && !Contact::isSharing($engagement['owner-id'], $channel->uid)) {
				continue;
			}

			if ($channel->circle === -2 && (!Contact::isFollower($engagement['owner-id'], $channel->uid) || Contact::isSharing($engagement['owner-id'], $channel->uid))) {
				continue;
			}

			$cache = [
				'channel'   => (int)$channel->code,
				'uid'       => $channel->uid,
				'uri-id'    => $engagement['uri-id'],
				'created'   => $post['created'],
				'received'  => $post['received'],
				'commented' => $post['commented'],
			];
			$ret = $this->dba->insert('channel-post', $cache, Database::INSERT_IGNORE);
			$this->logger->debug('Added channel post', ['uri-id' => $engagement['uri-id'], 'cache' => $cache, 'ret' => $ret]);
		}
	}
}

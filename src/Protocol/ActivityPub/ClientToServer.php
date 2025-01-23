<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Text\Markdown;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\JsonLD;

/**
 * ActivityPub Client To Server class
 */
class ClientToServer
{
	/**
	 * Process client to server activities
	 *
	 * @param array $activity
	 * @param integer $uid
	 * @param array $application
	 * @return array
	 */
	public static function processActivity(array $activity, int $uid, array $application): array
	{
		$ldactivity = JsonLD::compact($activity);
		if (empty($ldactivity)) {
			DI::logger()->notice('Invalid activity', ['activity' => $activity, 'uid' => $uid]);
			return [];
		}

		$type = JsonLD::fetchElement($ldactivity, '@type');
		if (!$type) {
			DI::logger()->notice('Empty type', ['activity' => $ldactivity, 'uid' => $uid]);
			return [];
		}

		$object_id   = JsonLD::fetchElement($ldactivity, 'as:object', '@id') ?? '';
		$object_type = Receiver::fetchObjectType($ldactivity, $object_id, $uid);
		if (!$object_type && !$object_id) {
			DI::logger()->notice('Empty object type or id', ['activity' => $ldactivity, 'uid' => $uid]);
			return [];
		}

		DI::logger()->debug('Processing activity', ['type' => $type, 'object_type' => $object_type, 'object_id' => $object_id, 'activity' => $ldactivity]);
		return self::routeActivities($type, $object_type, $object_id, $uid, $application, $ldactivity);
	}

	/**
	 * Route client to server activities
	 *
	 * @param string $type
	 * @param string $object_type
	 * @param string $object_id
	 * @param integer $uid
	 * @param array $application
	 * @param array $ldactivity
	 * @return array
	 */
	private static function routeActivities(string $type, string $object_type, string $object_id, int $uid, array $application, array $ldactivity): array
	{
		switch ($type) {
			case 'as:Create':
				if (in_array($object_type, Receiver::CONTENT_TYPES)) {
					return self::createContent($uid, $application, $ldactivity);
				}
				break;
			case 'as:Update':
				if (in_array($object_type, Receiver::CONTENT_TYPES) && !empty($object_id)) {
					return self::updateContent($uid, $object_id, $application, $ldactivity);
				}
				break;
			case 'as:Follow':
				if (in_array($object_type, Receiver::ACCOUNT_TYPES) && !empty($object_id)) {
					return self::followAccount($uid, $object_id, $ldactivity);
				}
				break;
		}
		return [];
	}

	/**
	 * Create a new post or comment
	 *
	 * @param integer $uid
	 * @param array $application
	 * @param array $ldactivity
	 * @return array
	 */
	private static function createContent(int $uid, array $application, array $ldactivity): array
	{
		$object_data = self::processObject($ldactivity['as:object']);
		$item        = ClientToServer::processContent($object_data, $application, $uid);
		DI::logger()->debug('Got data', ['item' => $item, 'object' => $object_data]);

		$id = Item::insert($item, true);
		if (!empty($id)) {
			$item = Post::selectFirst(['uri-id'], ['id' => $id]);
			if (!empty($item['uri-id'])) {
				return Transmitter::createActivityFromItem($id);
			}
		}
		return [];
	}

	/**
	 * Update an existing post or comment
	 *
	 * @param integer $uid
	 * @param string $object_id
	 * @param array $application
	 * @param array $ldactivity
	 * @return array
	 */
	private static function updateContent(int $uid, string $object_id, array $application, array $ldactivity): array
	{
		$id            = Item::fetchByLink($object_id, $uid, ActivityPub\Receiver::COMPLETION_ASYNC);
		$original_post = Post::selectFirst(['uri-id'], ['uid' => $uid, 'origin' => true, 'id' => $id]);
		if (empty($original_post)) {
			DI::logger()->debug('Item not found or does not belong to the user', ['id' => $id, 'uid' => $uid, 'object_id' => $object_id, 'activity' => $ldactivity]);
			return [];
		}

		$object_data = self::processObject($ldactivity['as:object']);
		$item        = ClientToServer::processContent($object_data, $application, $uid);
		if (empty($item['title']) && empty($item['body'])) {
			DI::logger()->debug('Empty body and title', ['id' => $id, 'uid' => $uid, 'object_id' => $object_id, 'activity' => $ldactivity]);
			return [];
		}
		$post = ['title' => $item['title'], 'body' => $item['body']];
		DI::logger()->debug('Got data', ['id' => $id, 'uid' => $uid, 'item' => $post]);
		Item::update($post, ['id' => $id]);
		Item::updateDisplayCache($original_post['uri-id']);

		return Transmitter::createActivityFromItem($id);
	}

	/**
	 * Follow a given account
	 * @todo Check the expected return value
	 *
	 * @param integer $uid
	 * @param string $object_id
	 * @param array $ldactivity
	 * @return array
	 */
	private static function followAccount(int $uid, string $object_id, array $ldactivity): array
	{
		return [];
	}

	/**
	 * Fetches data from the object part of an client to server activity
	 *
	 * @param array $object
	 *
	 * @return array Object data
	 */
	private static function processObject(array $object): array
	{
		$object_data = Receiver::getObjectDataFromActivity($object);

		$object_data['target']   = self::getTargets($object, $object_data['actor'] ?? '');
		$object_data['receiver'] = [];

		return $object_data;
	}

	/**
	 * Accumulate the targets and visibility of this post
	 *
	 * @param array $object
	 * @param string $actor
	 * @return array
	 */
	private static function getTargets(array $object, string $actor): array
	{
		$profile   = APContact::getByURL($actor);
		$followers = $profile['followers'];

		$targets = [];

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc', 'as:audience'] as $element) {
			switch ($element) {
				case 'as:to':
					$type = Receiver::TARGET_TO;
					break;
				case 'as:cc':
					$type = Receiver::TARGET_CC;
					break;
				case 'as:bto':
					$type = Receiver::TARGET_BTO;
					break;
				case 'as:bcc':
					$type = Receiver::TARGET_BCC;
					break;
				case 'as:audience':
					$type = Receiver::TARGET_AUDIENCE;
					break;
			}
			$receiver_list = JsonLD::fetchElementArray($object, $element, '@id');
			if (empty($receiver_list)) {
				continue;
			}

			foreach ($receiver_list as $receiver) {
				if ($receiver == Receiver::PUBLIC_COLLECTION) {
					$targets[Receiver::TARGET_GLOBAL] = ($element == 'as:to');
					continue;
				}

				if ($receiver == $followers) {
					$targets[Receiver::TARGET_FOLLOWER] = true;
					continue;
				}
				$targets[$type][] = Contact::getIdForURL($receiver);
			}
		}
		return $targets;
	}

	/**
	 * Create an item array from client to server object data
	 *
	 * @param array $object_data
	 * @param array $application
	 * @param integer $uid
	 * @return array
	 */
	private static function processContent(array $object_data, array $application, int $uid): array
	{
		$owner = User::getOwnerDataById($uid);

		$item = [];

		$item['network']    = Protocol::DFRN;
		$item['uid']        = $uid;
		$item['verb']       = Activity::POST;
		$item['contact-id'] = $owner['id'];
		$item['author-id']  = $item['owner-id'] = Contact::getPublicIdByUserId($uid);
		$item['title']      = $object_data['name'];
		$item['body']       = Markdown::toBBCode($object_data['content'] ?? '');
		$item['app']        = $application['name'] ?? 'API';

		if (!empty($object_data['target'][Receiver::TARGET_GLOBAL])) {
			$item['allow_cid'] = '';
			$item['allow_gid'] = '';
			$item['deny_cid']  = '';
			$item['deny_gid']  = '';
			$item['private']   = Item::PUBLIC;
		} elseif (isset($object_data['target'][Receiver::TARGET_GLOBAL])) {
			$item['allow_cid'] = '';
			$item['allow_gid'] = '';
			$item['deny_cid']  = '';
			$item['deny_gid']  = '';
			$item['private']   = Item::UNLISTED;
		} elseif (!empty($object_data['target'][Receiver::TARGET_FOLLOWER])) {
			$item['allow_cid'] = '';
			$item['allow_gid'] = '<' . Circle::FOLLOWERS . '>';
			$item['deny_cid']  = '';
			$item['deny_gid']  = '';
			$item['private']   = Item::PRIVATE;
		} else {
			// @todo Set permissions via the $object_data['target'] array
			$item['allow_cid'] = '<' . $owner['id'] . '>';
			$item['allow_gid'] = '';
			$item['deny_cid']  = '';
			$item['deny_gid']  = '';
			$item['private']   = Item::PRIVATE;
		}

		if (!empty($object_data['summary'])) {
			$item['body'] = '[abstract=' . Protocol::ACTIVITYPUB . ']' . $object_data['summary'] . "[/abstract]\n" . $item['body'];
		}

		if ($object_data['reply-to-id']) {
			$item['thr-parent'] = $object_data['reply-to-id'];
			$item['gravity']    = Item::GRAVITY_COMMENT;
		} else {
			$item['gravity'] = Item::GRAVITY_PARENT;
		}

		$item = DI::contentItem()->expandTags($item);

		return $item;
	}

	/**
	 * Public posts for the given owner
	 *
	 * @param array   $owner     Owner array
	 * @param integer $uid       User id
	 * @param integer $page      Page number
	 * @param integer $max_id    Maximum ID
	 * @param string  $requester URL of requesting account
	 * @return array of posts
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getOutbox(array $owner, int $uid, int $page = null, int $max_id = null, string $requester = ''): array
	{
		$condition = [
			'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			'private' => [Item::PUBLIC, Item::UNLISTED]
		];

		if (!empty($requester)) {
			$requester_id = Contact::getIdForURL($requester, $owner['uid']);
			if (!empty($requester_id)) {
				$permissionSets = DI::permissionSet()->selectByContactId($requester_id, $owner['uid']);
				if (count($permissionSets) > 0) {
					$condition = ['psid' => array_merge(
						$permissionSets->column('id'),
						[DI::permissionSet()->selectPublicForUser($owner['uid'])]
					)];
				}
			}
		}

		$condition = array_merge($condition, [
			'uid'            => $owner['uid'],
			'author-id'      => Contact::getIdForURL($owner['url'], 0, false),
			'gravity'        => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			'network'        => Protocol::FEDERATED,
			'parent-network' => Protocol::FEDERATED,
			'origin'         => true,
			'deleted'        => false,
			'visible'        => true
		]);

		$apcontact = APContact::getByURL($owner['url']);

		if (empty($apcontact)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		return self::getCollection($condition, DI::baseUrl() . '/outbox/' . $owner['nickname'], $page, $max_id, $uid, $apcontact['statuses_count']);
	}

	public static function getInbox(int $uid, int $page = null, int $max_id = null)
	{
		$owner = User::getOwnerDataById($uid);

		$condition = [
			'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN],
			'uid'     => $uid
		];

		return self::getCollection($condition, DI::baseUrl() . '/inbox/' . $owner['nickname'], $page, $max_id, $uid, null);
	}

	public static function getPublicInbox(int $uid, int $page = null, int $max_id = null)
	{
		$condition = [
			'gravity'        => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT],
			'private'        => Item::PUBLIC,
			'network'        => [Protocol::ACTIVITYPUB, Protocol::DFRN],
			'author-blocked' => false,
			'author-hidden'  => false
		];

		return self::getCollection($condition, DI::baseUrl() . '/inbox', $page, $max_id, $uid, null);
	}

	private static function getCollection(array $condition, string $path, int $page = null, int $max_id = null, int $uid = null, int $total_items = null)
	{
		$data = ['@context' => ActivityPub::CONTEXT];

		$data['id']   = $path;
		$data['type'] = 'OrderedCollection';

		if (!is_null($total_items)) {
			$data['totalItems'] = $total_items;
		}

		if (!empty($page)) {
			$data['id'] .= '?' . http_build_query(['page' => $page]);
		}

		if (empty($page) && empty($max_id)) {
			$data['first'] = $path . '?page=1';
		} else {
			$data['type'] = 'OrderedCollectionPage';

			$list = [];

			if (!empty($max_id)) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $max_id]);
			}

			if (!empty($page)) {
				$params = ['limit' => [($page - 1) * 20, 20], 'order' => ['uri-id' => true]];
			} else {
				$params = ['limit' => 20, 'order' => ['uri-id' => true]];
			}

			if (!is_null($uid)) {
				$items = Post::selectForUser($uid, ['id', 'uri-id'], $condition, $params);
			} else {
				$items = Post::select(['id', 'uri-id'], $condition, $params);
			}

			$last_id = 0;

			while ($item = Post::fetch($items)) {
				$activity = Transmitter::createActivityFromItem($item['id'], false, !is_null($uid));
				if (!empty($activity)) {
					$list[]  = $activity;
					$last_id = $item['uri-id'];
					continue;
				}
			}
			DBA::close($items);

			if (count($list) == 20) {
				$data['next'] = $path . '?max_id=' . $last_id;
			}

			// Fix the cached total item count when it is lower than the real count
			if (!is_null($total_items)) {
				$total = (($page - 1) * 20) + $data['totalItems'];
				if ($total > $data['totalItems']) {
					$data['totalItems'] = $total;
				}
			}

			$data['partOf'] = $path;

			$data['orderedItems'] = $list;
		}

		return $data;
	}
}

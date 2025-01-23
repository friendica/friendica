<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Util\DateTimeFormat;

class Delayed
{
	/**
	 * The content of the post is posted as is. Connector settings are using the default settings.
	 * This is used for automated scheduled posts via feeds or from the API.
	 */
	const PREPARED = 0;
	/**
	 * Like PREPARED, but additionally the connector settings can differ.
	 * This is used when manually publishing scheduled posts.
	 */
	const PREPARED_NO_HOOK = 2;

	/**
	 * Insert a new delayed post
	 *
	 * @param string $uri
	 * @param array  $item
	 * @param int    $notify
	 * @param int    $preparation_mode
	 * @param string $delayed
	 * @param array  $taglist
	 * @param array  $attachments
	 * @return int   ID of the created delayed post entry
	 */
	public static function add(string $uri, array $item, int $notify = 0, int $preparation_mode = self::PREPARED, string $delayed = '', array $taglist = [], array $attachments = [])
	{
		if (empty($item['uid']) || self::exists($uri, $item['uid'])) {
			DI::logger()->notice('No uid or already found');
			return 0;
		}

		if (empty($delayed)) {
			$min_posting = DI::config()->get('system', 'minimum_posting_interval', 0);

			$last_publish = DI::pConfig()->get($item['uid'], 'system', 'last_publish', 0, true);
			$next_publish = max($last_publish + (60 * $min_posting), time());
			$delayed      = date(DateTimeFormat::MYSQL, $next_publish);
			DI::pConfig()->set($item['uid'], 'system', 'last_publish', $next_publish);
		}

		DI::logger()->notice('Adding post for delayed publishing', ['uid' => $item['uid'], 'delayed' => $delayed, 'uri' => $uri]);

		$wid = Worker::add(['priority' => Worker::PRIORITY_HIGH, 'delayed' => $delayed], 'DelayedPublish', $item, $notify, $taglist, $attachments, $preparation_mode, $uri);
		if (!$wid) {
			return 0;
		}

		$delayed_post = [
			'uri'     => $uri,
			'uid'     => $item['uid'],
			'delayed' => $delayed,
			'wid'     => $wid,
		];

		if (DBA::insert('delayed-post', $delayed_post, Database::INSERT_IGNORE)) {
			return DBA::lastInsertId();
		} else {
			return 0;
		}
	}

	/**
	 * Delete a delayed post
	 *
	 * @param string $uri
	 * @param int    $uid
	 *
	 * @return bool delete success
	 */
	private static function delete(string $uri, int $uid)
	{
		return DBA::delete('delayed-post', ['uri' => $uri, 'uid' => $uid]);
	}

	/**
	 * Delete scheduled posts and the associated workerqueue entry
	 *
	 * @param integer $id
	 * @return void
	 */
	public static function deleteById(int $id)
	{
		$post = DBA::selectFirst('delayed-post', ['wid'], ['id' => $id]);
		if (empty($post['wid'])) {
			return;
		}

		DBA::delete('delayed-post', ['id' => $id]);
		DBA::delete('workerqueue', ['id' => $post['wid']]);
	}

	/**
	 * Check if an entry exists
	 *
	 * @param string $uri
	 * @param int    $uid
	 *
	 * @return bool "true" if an entry with that URI exists
	 */
	public static function exists(string $uri, int $uid)
	{
		return DBA::exists('delayed-post', ['uri' => $uri, 'uid' => $uid]);
	}

	/**
	 * Fetch parameters for delayed posts
	 *
	 * @param integer $id
	 * @return array
	 */
	public static function getParametersForid(int $id)
	{
		$delayed = DBA::selectFirst('delayed-post', ['id', 'uid', 'wid', 'delayed'], ['id' => $id]);
		if (empty($delayed['wid'])) {
			return [];
		}

		$worker = DBA::selectFirst('workerqueue', ['parameter'], ['id' => $delayed['wid'], 'command' => 'DelayedPublish']);
		if (empty($worker)) {
			return [];
		}

		$parameters = json_decode($worker['parameter'], true);
		if (empty($parameters)) {
			return [];
		}

		// Make sure to only publish the attachments in the dedicated array field
		if (empty($parameters[3]) && !empty($parameters[0]['attachments'])) {
			$parameters[3] = $parameters[0]['attachments'];
			unset($parameters[0]['attachments']);
		}

		return [
			'parameters'  => $delayed,
			'item'        => $parameters[0],
			'notify'      => $parameters[1],
			'taglist'     => $parameters[2],
			'attachments' => $parameters[3],
			'unprepared'  => $parameters[4],
			'uri'         => $parameters[5],
		];
	}

	/**
	 * Publish a delayed post
	 *
	 * @param array  $item
	 * @param int    $notify
	 * @param array  $taglist
	 * @param array  $attachments
	 * @param int    $preparation_mode
	 * @param string $uri
	 * @return bool
	 */
	public static function publish(array $item, int $notify = 0, array $taglist = [], array $attachments = [], int $preparation_mode = self::PREPARED, string $uri = '')
	{
		if (!empty($attachments)) {
			$item['attachments'] = $attachments;
		}

		$id = Item::insert($item, $notify, $preparation_mode == self::PREPARED);

		DI::logger()->notice('Post stored', ['id' => $id, 'uid' => $item['uid'], 'cid' => $item['contact-id'] ?? 'N/A']);

		if (empty($uri) && !empty($item['uri'])) {
			$uri = $item['uri'];
		}

		if (!empty($uri) && self::exists($uri, $item['uid'])) {
			self::delete($uri, $item['uid']);
		}

		if (!empty($id) && (!empty($taglist) || !empty($attachments))) {
			$feeditem = Post::selectFirst(['uri-id'], ['id' => $id]);

			foreach ($taglist as $tag) {
				Tag::store($feeditem['uri-id'], Tag::HASHTAG, $tag);
			}
		}

		return $id;
	}
}

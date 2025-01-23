<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Factory\Api\Mastodon\Notification as NotificationFactory;
use Friendica\Navigation\Notifications\Entity\Notification as NotificationEntity;
use Minishlink\WebPush\VAPID;

class Subscription
{
	/**
	 * Select a subscription record exists
	 *
	 * @return array|bool Array on success, false on failure
	 */
	public static function select(int $applicationid, int $uid, array $fields = [])
	{
		return DBA::selectFirst('subscription', $fields, ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Check if a subscription record exists
	 *
	 * @param int   $applicationid
	 * @param int   $uid
	 *
	 * @return bool Does it exist?
	 */
	public static function exists(int $applicationid, int $uid): bool
	{
		return DBA::exists('subscription', ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Update a subscription record
	 *
	 * @param int   $applicationid
	 * @param int   $uid
	 * @param array $fields subscription fields
	 * @return bool result of update
	 */
	public static function update(int $applicationid, int $uid, array $fields): bool
	{
		return DBA::update('subscription', $fields, ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Insert or replace a subscription record
	 *
	 * @param array $fields subscription fields
	 * @return bool result of replace
	 */
	public static function replace(array $fields): bool
	{
		return DBA::replace('subscription', $fields);
	}

	/**
	 * Delete a subscription record
	 *
	 * @param int $applicationid
	 * @param int $uid
	 * @return bool
	 */
	public static function delete(int $applicationid, int $uid): bool
	{
		return DBA::delete('subscription', ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Fetch a VAPID keypair
	 *
	 * @return array
	 */
	private static function getKeyPair(): array
	{
		$keypair = DI::config()->get('system', 'ec_keypair');
		if (empty($keypair['publicKey']) || empty($keypair['privateKey'])) {
			$keypair = VAPID::createVapidKeys();
			DI::config()->set('system', 'ec_keypair', $keypair);
		}
		return $keypair;
	}

	/**
	 * Fetch the public VAPID key
	 *
	 * @return string
	 */
	public static function getPublicVapidKey(): string
	{
		$keypair = self::getKeyPair();
		return $keypair['publicKey'];
	}

	/**
	 * Fetch the public VAPID key
	 *
	 * @return string
	 */
	public static function getPrivateVapidKey(): string
	{
		$keypair = self::getKeyPair();
		return $keypair['privateKey'];
	}

	/**
	 * Prepare push notification
	 *
	 * @return void
	 */
	public static function pushByNotification(NotificationEntity $notification)
	{
		$type = NotificationFactory::getType($notification);

		if (DI::notify()->shouldShowOnDesktop($notification, $type)) {
			DI::notify()->createFromNotification($notification);
		}

		if (empty($type)) {
			return;
		}

		$subscriptions = DBA::select('subscription', [], ['uid' => $notification->uid, $type => true]);
		while ($subscription = DBA::fetch($subscriptions)) {
			DI::logger()->info('Push notification', ['id' => $subscription['id'], 'uid' => $subscription['uid'], 'type' => $type]);
			Worker::add(Worker::PRIORITY_HIGH, 'PushSubscription', $subscription['id'], $notification->id);
		}
		DBA::close($subscriptions);
	}
}

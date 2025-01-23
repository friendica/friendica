<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Factory\Api\Mastodon\Notification as NotificationFactory;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\Subscription as ModelSubscription;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushSubscription
{
	/**
	 * Creates push subscription by subscription and notification ids
	 *
	 * @param int $sid Subscription id
	 * @param int $nid Notification id
	 * @return void
	 */
	public static function execute(int $sid, int $nid)
	{
		DI::logger()->info('Start', ['subscription' => $sid, 'notification' => $nid]);

		$subscription = DBA::selectFirst('subscription', [], ['id' => $sid]);
		if (empty($subscription)) {
			DI::logger()->info('Subscription not found', ['subscription' => $sid]);
			return;
		}

		try {
			$notification = DI::notification()->selectOneById($nid);
		} catch (NotFoundException $e) {
			DI::logger()->info('Notification not found', ['notification' => $nid]);
			return;
		}

		$application_token = DBA::selectFirst('application-token', [], ['application-id' => $subscription['application-id'], 'uid' => $subscription['uid']]);
		if (empty($application_token)) {
			DI::logger()->info('Application token not found', ['application' => $subscription['application-id']]);
			return;
		}

		$user = User::getById($notification->uid);
		if (empty($user)) {
			DI::logger()->info('User not found', ['application' => $subscription['uid']]);
			return;
		}

		$l10n = DI::l10n()->withLang($user['language']);

		if ($notification->actorId) {
			$actor = Contact::getById($notification->actorId);
		}

		$body = '';

		if ($notification->targetUriId) {
			$post = Post::selectFirst([], ['uri-id' => $notification->targetUriId, 'uid' => [0, $notification->uid]]);
			if (!empty($post['body'])) {
				$body = BBCode::toPlaintext($post['body'], false);
				$body = Plaintext::shorten($body, 160, $notification->uid);
			}
		}

		$message = DI::notificationFactory()->getMessageFromNotification($notification);
		$title   = $message['plain'] ?? '';

		$push = Subscription::create([
			'contentEncoding' => 'aesgcm',
			'endpoint'        => $subscription['endpoint'],
			'keys'            => [
				'p256dh' => $subscription['pubkey'],
				'auth'   => $subscription['secret']
			],
		]);

		$payload = [
			'access_token'      => $application_token['access_token'],
			'preferred_locale'  => $user['language'],
			'notification_id'   => $nid,
			'notification_type' => NotificationFactory::getType($notification),
			'icon'              => $actor['thumb'] ?? '',
			'title'             => $title ?: $l10n->t('Notification from Friendica'),
			'body'              => $body ?: $l10n->t('Empty Post'),
		];

		DI::logger()->info('Payload', ['payload' => $payload]);

		$auth = [
			'VAPID' => [
				'subject'    => DI::baseUrl()->getHost(),
				'publicKey'  => ModelSubscription::getPublicVapidKey(),
				'privateKey' => ModelSubscription::getPrivateVapidKey(),
			],
		];

		$webPush = new WebPush($auth, [], DI::config()->get('system', 'xrd_timeout'));

		$report = $webPush->sendOneNotification($push, json_encode($payload), ['urgency' => 'normal']);

		$endpoint = $report->getRequest()->getUri()->__toString();

		if ($report->isSuccess()) {
			DI::logger()->info('Message sent successfully for subscription', ['subscription' => $sid, 'notification' => $nid, 'endpoint' => $endpoint]);
		} else {
			DI::logger()->info('Message failed to sent for subscription', ['subscription' => $sid, 'notification' => $nid, 'endpoint' => $endpoint, 'reason' => $report->getReason()]);
		}
	}
}

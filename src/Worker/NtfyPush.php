<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\LarpnetPush;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;

class NtfyPush
{
	public static function execute(int $uid, int $nid)
	{
		$ntfyUrl = DI::config()->get('larpnet_notifications', 'ntfy_url');
		if (empty($ntfyUrl)) {
			return;
		}

		try {
			$notification = DI::notification()->selectOneById($nid);
		} catch (NotFoundException $e) {
			DI::logger()->info('NtfyPush: notification not found', ['nid' => $nid]);
			return;
		}

		$user = User::getById($uid);
		if (empty($user)) {
			return;
		}

		$actor = [];
		if ($notification->actorId) {
			$actor = Contact::getById($notification->actorId);
		}

		$body = '';
		if ($notification->targetUriId) {
			$post = Post::selectFirst([], ['uri-id' => $notification->targetUriId, 'uid' => [0, $uid]]);
			if (!empty($post['body'])) {
				$body = BBCode::toPlaintext($post['body'], false);
				$body = Plaintext::shorten($body, 160, $uid);
			}
		}

		$message = DI::notificationFactory()->getMessageFromNotification($notification);
		$title   = $message['plain'] ?? '';

		$topic = LarpnetPush::getOrCreateTopic($uid);
		LarpnetPush::send(
			$topic,
			$title ?: DI::l10n()->t('Notification'),
			$body ?: $title,
			(string) DI::baseUrl() . '/notification',
			$actor['thumb'] ?? null
		);
	}
}

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
use Friendica\Model\Contact;
use Friendica\Model\LarpnetPush;

/**
 * Pushes a ntfy notification for a new direct message. Kept separate from
 * NtfyPush because private messages use the legacy `mail`/Notify tables, not
 * the Navigation\Notifications\Entity\Notification that NtfyPush::execute()
 * reads from.
 */
class NtfyPushMail
{
	public static function execute(int $uid, int $mailId)
	{
		$ntfyUrl = DI::config()->get('larpnet_notifications', 'ntfy_url');
		if (empty($ntfyUrl)) {
			return;
		}

		$mail = DBA::selectFirst('mail', ['from-name', 'body', 'contact-id'], ['id' => $mailId, 'uid' => $uid]);
		if (!DBA::isResult($mail)) {
			return;
		}

		$body = BBCode::toPlaintext($mail['body'], false);
		$body = Plaintext::shorten($body, 160, $uid);

		$icon = null;
		if (!empty($mail['contact-id'])) {
			$contact = Contact::getById($mail['contact-id']);
			$icon    = $contact['thumb'] ?? null;
		}

		$topic = LarpnetPush::getOrCreateTopic($uid);
		LarpnetPush::send(
			$topic,
			DI::l10n()->t('New message from %s', $mail['from-name']),
			$body,
			(string) DI::baseUrl() . '/message/' . $mailId,
			$icon
		);
	}
}

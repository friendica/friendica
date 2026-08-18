<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\DI;
use Friendica\Model\LarpnetPush;
use Friendica\Module\BaseApi;

/**
 * Hands a native client (e.g. the Android app) what it needs to subscribe
 * directly to the larpnet ntfy relay: the server URL, the caller's
 * (auto-provisioned) per-user topic, and a read-only ntfy token.
 *
 * This is the native-client equivalent of what larpnet_notifications/theme.php
 * injects into the browser via window.LarpnetPush.
 */
class LarpnetPushConfig extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$ntfyUrl = DI::config()->get('larpnet_notifications', 'ntfy_url');
		if (empty($ntfyUrl)) {
			$this->jsonExit(['enabled' => false]);
		}

		// Never expose the write-capable token to a client, only the read-only one.
		$ntfyToken = (string) DI::config()->get('larpnet_notifications', 'ntfy_ro_token');

		$this->jsonExit([
			'enabled'    => true,
			'ntfy_url'   => rtrim($ntfyUrl, '/'),
			'ntfy_topic' => LarpnetPush::getOrCreateTopic($uid),
			'ntfy_token' => $ntfyToken,
		]);
	}
}

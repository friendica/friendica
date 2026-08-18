<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\DI;
use Friendica\Util\Strings;

/**
 * Shared helpers for the larpnet ntfy push integration, used by both the
 * larpnet_notifications theme (browser/PWA Web Push) and native API clients
 * (e.g. the Android app) that provision/consume the same per-user ntfy topic.
 */
class LarpnetPush
{
	public static function getOrCreateTopic(int $uid): string
	{
		$topic = DI::pConfig()->get($uid, 'larpnet_notifications', 'ntfy_topic');
		// Regenerate if empty or contains hyphens (old format rejected by ntfy)
		if (empty($topic) || strpos($topic, '-') !== false) {
			$topic = 'ln' . Strings::getRandomHex(16);
			DI::pConfig()->set($uid, 'larpnet_notifications', 'ntfy_topic', $topic);
		}
		return $topic;
	}

	public static function send(string $topic, string $title, string $message, string $click, ?string $icon = null): void
	{
		$ntfyUrl   = DI::config()->get('larpnet_notifications', 'ntfy_url');
		$ntfyToken = DI::config()->get('larpnet_notifications', 'ntfy_token');
		if (empty($ntfyUrl)) {
			return;
		}

		$payload = [
			'topic'   => $topic,
			'title'   => $title,
			'message' => $message,
			'click'   => $click,
		];
		if (!empty($icon)) {
			$payload['icon'] = $icon;
		}

		$headers = ['Content-Type' => 'application/json'];
		if ($ntfyToken) {
			$headers['Authorization'] = 'Bearer ' . $ntfyToken;
		}

		try {
			DI::httpClient()->post(rtrim($ntfyUrl, '/') . '/', json_encode($payload), $headers);
			DI::logger()->info('LarpnetPush: sent', ['topic' => $topic]);
		} catch (\Throwable $e) {
			DI::logger()->warning('LarpnetPush: failed', ['topic' => $topic, 'error' => $e->getMessage()]);
		}
	}
}

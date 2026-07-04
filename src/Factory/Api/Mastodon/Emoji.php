<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Collection\Api\Mastodon\Emojis;

class Emoji extends BaseFactory
{
	public function create(string $shortcode, string $url): \Friendica\Object\Api\Mastodon\Emoji
	{
		return new \Friendica\Object\Api\Mastodon\Emoji($shortcode, $url);
	}

	/**
	 * Creates an emoji collection from shortcode => image mappings.
	 *
	 * @param array $smilies
	 *
	 * @return Emojis
	 */
	public function createCollectionFromArray(array $smilies): Emojis
	{
		$prototype = null;

		$emojis = [];

		foreach ($smilies as $shortcode => $url) {
			if ($shortcode !== '' && $url !== '') {
				$shortcode = trim((string) $shortcode, ':');

				if ($prototype === null) {
					$prototype = $this->create($shortcode, $url);
					$emojis[]  = $prototype;
				} else {
					$emojis[] = \Friendica\Object\Api\Mastodon\Emoji::createFromPrototype($prototype, $shortcode, $url);
				}
			}
		}

		return new Emojis($emojis);
	}

	/**
	 * @param array $smilies as is returned by Smilies::getList()
	 *
	 * @return Emojis
	 */
	public function createCollectionFromSmilies(array $smilies): Emojis
	{
		$emojis = [];
		$icons  = $smilies['icons'];
		foreach ($smilies['texts'] as $i => $name) {
			$url = $icons[$i];
			if (preg_match('/src="(.+?)"/', (string) $url, $matches)) {
				$emojis[$name] = $matches[1];
			}
		}
		return self::createCollectionFromArray($emojis);
	}
}

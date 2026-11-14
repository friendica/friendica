<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Post;

use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Util\Strings;

/**
 * Resolves links to already known posts
 */
final readonly class LinkResolver
{
	/**
	 * Search item id for given URI or plink
	 *
	 * @param string $uri
	 * @param integer $uid
	 *
	 * @return integer item id
	 */
	public function searchByLink(string $uri, int $uid = 0): int
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris    = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['id'], ['uri' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		$item = Post::selectFirst(['id'], ['plink' => $uris, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item['id'];
		}

		return 0;
	}

	/**
	 * Return the URI for a link to the post
	 *
	 * @param string $uri URI or link to post
	 *
	 * @return string URI
	 */
	public function getURIByLink(string $uri): string
	{
		$ssl_uri = str_replace('http://', 'https://', $uri);
		$uris    = [$uri, $ssl_uri, Strings::normaliseLink($uri)];

		$item = Post::selectFirst(['uri'], ['uri' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		$item = Post::selectFirst(['uri'], ['plink' => $uris]);
		if (DBA::isResult($item)) {
			return $item['uri'];
		}

		return '';
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Post;

use Friendica\App\BaseURL;
use Friendica\Core\System;
use Psr\Log\LoggerInterface;

/**
 * Creates GUIDs and URIs for posts
 */
final readonly class UriGenerator
{
	public function __construct(
		private BaseURL $baseURL,
		private LoggerInterface $logger,
	) {}

	/**
	 * Creates an unique guid out of a given uri.
	 * This function is used for messages outside the fediverse (Connector posts, feeds, Mails, ...)
	 * Posts that are created on this system are using System::createUUID.
	 * Received ActivityPub posts are using Processor::getGUIDByURL.
	 *
	 * @param string      $uri  uri of an item entry
	 * @param string|null $host hostname for the GUID prefix
	 * @return string Unique guid
	 */
	public function guidFromUri(string $uri, ?string $host = null): string
	{
		// Our regular guid routine is using this kind of prefix as well
		// We have to avoid that different routines could accidentally create the same value
		// "parse_url()" returns false for malformed URIs
		$parsed = parse_url($uri) ?: [];

		// Remove the scheme to make sure that "https" and "http" doesn't make a difference
		unset($parsed['scheme']);

		$hostPart = $host ?: $parsed['host'] ?? '';
		if (!$hostPart) {
			$this->logger->warning('Empty host GUID part', ['uri' => $uri, 'host' => $host, 'parsed' => $parsed]);
		}

		// Glue it together to be able to make a hash from it
		if (!empty($parsed)) {
			$host_id = implode('/', (array) $parsed);
		} else {
			$host_id = $uri;
		}

		// Use a mixture of several hashes to provide some GUID like experience
		return hash('crc32', (string) $hostPart) . '-' . hash('joaat', $host_id) . '-' . hash('fnv164', $host_id);
	}

	/**
	 * generate an unique URI
	 *
	 * @param string $guid An existing GUID (Otherwise it will be generated)
	 *
	 * @return string
	 */
	public function newURI(string $guid = ''): string
	{
		if ($guid == '') {
			$guid = System::createUUID();
		}

		return $this->baseURL . '/objects/' . $guid;
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Util\HTTPCache;

/**
 * Standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo extends BaseModule
{
	protected function rawContent(array $request = []): never
	{
		$nodeinfo = [
			'links' => [
				[
					'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
					'href' => DI::baseUrl() . '/nodeinfo/1.0',
				],
				[
					'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
					'href' => DI::baseUrl() . '/nodeinfo/2.0',
				],
				[
					'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.1',
					'href' => DI::baseUrl() . '/nodeinfo/2.1',
				],
				[
					'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.2',
					'href' => DI::baseUrl() . '/nodeinfo/2.2',
				],
				// @see https://codeberg.org/fediverse/fep/src/branch/main/fep/2677/fep-2677.md
				[
					'rel'  => 'https://www.w3.org/ns/activitystreams#Application',
					'href' => DI::baseUrl() . '/friendica',
				],
			],
		];

		header(sprintf('Cache-Control: max-age=%d, public, immutable', HTTPCache::DISCOVERY_MAX_AGE));
		$this->earlyJsonExit($nodeinfo);
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;

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
			],
		];

		// A cache time of 3 days is a good compromise between performance and freshness of the data (the same values are used by Mastodon). 
		header('Cache-Control: max-age=259200, public, immutable');
		$this->earlyJsonExit($nodeinfo);
	}
}

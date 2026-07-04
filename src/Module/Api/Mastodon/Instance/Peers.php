<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Instance;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\GServer;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use GuzzleHttp\Psr7\Uri;

/**
 * Undocumented API endpoint that is implemented by both Mastodon and Pleroma
 */
class Peers extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$return = [];

		// We only select for Friendica and ActivityPub servers, since it is expected to only deliver AP compatible systems here.
		$instances = DBA::select('gserver', ['url'], ["`network` in (?, ?) AND NOT `blocked` AND NOT `failed` AND NOT `detection-method` IN (?, ?, ?, ?)",
			Protocol::DFRN, Protocol::ACTIVITYPUB,
			GServer::DETECT_MANUAL, GServer::DETECT_HEADER, GServer::DETECT_BODY, GServer::DETECT_HOST_META]);
		while ($instance = DBA::fetch($instances)) {
			$urldata = parse_url((string) $instance['url']);
			unset($urldata['scheme']);
			$return[] = ltrim((string) Uri::fromParts((array) $urldata), '/');
		}
		DBA::close($instances);

		$this->jsonExit($return);
	}
}

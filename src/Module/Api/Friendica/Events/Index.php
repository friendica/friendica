<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Events;

use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * api/friendica/events
 *
 * @package Friendica\Module\Api\Friendica\Events
 */
class Index extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'since_id' => 0,
			'count'    => 50,
		], $request);

		$condition = ["`id` > ? AND `uid` = ?", $request['since_id'], $uid];
		$params    = ['limit' => $request['count']];
		$events    = DBA::selectToArray('event', [], $condition, $params);

		$items = [];
		foreach ($events as $event) {
			$items[] = [
				'id'         => intval($event['id']),
				'uid'        => intval($event['uid']),
				'cid'        => $event['cid'],
				'uri'        => $event['uri'],
				'name'       => $event['summary'],
				'desc'       => BBCode::convertForUriId($event['uri-id'], $event['desc']),
				'start_time' => $event['start'],
				'end_time'   => $event['finish'],
				'type'       => $event['type'],
				'nofinish'   => $event['nofinish'],
				'place'      => $event['location'],
				'adjust'     => 1,
				'ignore'     => $event['ignore'],
				'allow_cid'  => $event['allow_cid'],
				'allow_gid'  => $event['allow_gid'],
				'deny_cid'   => $event['deny_cid'],
				'deny_gid'   => $event['deny_gid']
			];
		}

		$this->response->addFormattedContent('events', ['events' => $items], $this->parameters['extension'] ?? null);
	}
}

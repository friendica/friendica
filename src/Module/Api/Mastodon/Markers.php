<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/markers/
 */
class Markers extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$timeline     = '';
		$last_read_id = '';
		foreach (['home', 'notifications'] as $name) {
			if (!empty($request[$name])) {
				$timeline     = $name;
				$last_read_id = $request[$name]['last_read_id'] ?? '';
			}
		}

		if ($timeline === '' || $last_read_id === '' || empty($application['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$condition = ['application-id' => $application['id'], 'uid' => $uid, 'timeline' => $timeline];
		$marker    = DBA::selectFirst('application-marker', [], $condition);
		if (!empty($marker['version'])) {
			$version = $marker['version'] + 1;
		} else {
			$version = 1;
		}

		$fields = ['last_read_id' => $last_read_id, 'version' => $version, 'updated_at' => DateTimeFormat::utcNow()];
		DBA::update('application-marker', $fields, $condition, true);
		$this->jsonExit($this->fetchTimelines($application['id'], $uid));
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$this->jsonExit($this->fetchTimelines($application['id'], $uid));
	}

	private function fetchTimelines(int $application_id, int $uid): \stdClass
	{
		$values  = new \stdClass();
		$markers = DBA::select('application-marker', [], ['application-id' => $application_id, 'uid' => $uid]);
		while ($marker = DBA::fetch($markers)) {
			$values->{$marker['timeline']} = [
				'last_read_id' => $marker['last_read_id'],
				'version'      => $marker['version'],
				'updated_at'   => DateTimeFormat::utc($marker['updated_at'], DateTimeFormat::JSON)
			];
		}
		return $values;
	}
}

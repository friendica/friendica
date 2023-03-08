<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/markers/
 */
class Markers extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
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

		if (empty($timeline) || empty($last_read_id) || empty($application['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$condition = ['application-id' => $application['id'], 'uid' => $uid, 'timeline' => $timeline];
		$marker = DBA::selectFirst('application-marker', [], $condition);
		if (!empty($marker['version'])) {
			$version = $marker['version'] + 1;
		} else {
			$version = 1;
		}

		$fields = ['last_read_id' => $last_read_id, 'version' => $version, 'updated_at' => DateTimeFormat::utcNow()];
		DBA::update('application-marker', $fields, $condition, true);
		System::jsonExit($this->fetchTimelines($application['id'], $uid));
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		System::jsonExit($this->fetchTimelines($application['id'], $uid));
	}

	private function fetchTimelines(int $application_id, int $uid)
	{
		$values = [];
		$markers = DBA::select('application-marker', [], ['application-id' => $application_id, 'uid' => $uid]);
		while ($marker = DBA::fetch($markers)) {
			$values[$marker['timeline']] = [
				'last_read_id' => $marker['last_read_id'],
				'version'      => $marker['version'],
				'updated_at'   => $marker['updated_at']
			];
		}
		return $values;
	}
}

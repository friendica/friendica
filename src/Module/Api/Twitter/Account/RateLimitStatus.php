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

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * API endpoint: /api/account/rate_limit_status
 */
class RateLimitStatus extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		if (($this->parameters['extension'] ?? '') == 'xml') {
			$hash = [
				'remaining-hits'        => '150',
				'@attributes'           => ['type' => 'integer'],
				'hourly-limit'          => '150',
				'@attributes2'          => ['type' => 'integer'],
				'reset-time'            => DateTimeFormat::utc('now + 1 hour', DateTimeFormat::ATOM),
				'@attributes3'          => ['type' => 'datetime'],
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'@attributes4'          => ['type' => 'integer'],
			];
		} else {
			$hash = [
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'remaining_hits'        => '150',
				'hourly_limit'          => '150',
				'reset_time'            => DateTimeFormat::utc('now + 1 hour', DateTimeFormat::API),
			];
		}

		$this->response->exit('hash', ['hash' => $hash], $this->parameters['extension'] ?? null);
	}
}

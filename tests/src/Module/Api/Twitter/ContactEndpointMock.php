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

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Module\Api\Twitter\ContactEndpoint;

/**
 * Class ContactEndpointMock
 *
 * Exposes protected methods for test in the inherited class
 *
 * @method static int   getUid(int $contact_id = null, string $screen_name = null)
 * @method static array list($rel, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $skip_status = false, bool $include_user_entities = true)
 * @method static array ids($rel, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $stringify_ids = false)
 *
 * @package Friendica\Test\Mock\Module\Api\Twitter
 */
class ContactEndpointMock extends ContactEndpoint
{
	public static function __callStatic($name, $arguments)
	{
		return self::$name(...$arguments);
	}
}

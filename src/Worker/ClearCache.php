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

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Clear cache entries
 */
class ClearCache
{
	public static function execute()
	{
		// clear old cache
		DI::cache()->clear();

		// Delete the cached OEmbed entries that are older than three month
		DBA::delete('oembed', ["`created` < ?", DateTimeFormat::utc('now - 3 months')]);

		// Delete the cached "parsed_url" entries that are expired
		DBA::delete('parsed_url', ["`expires` < ?", DateTimeFormat::utcNow()]);
	}
}

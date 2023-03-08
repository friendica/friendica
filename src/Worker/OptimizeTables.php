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

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Optimize tables that are known to grow and shrink all the time
 */
class OptimizeTables
{
	public static function execute()
	{

		if (!DI::lock()->acquire('optimize_tables', 0)) {
			Logger::warning('Lock could not be acquired');
			return;
		}

		Logger::info('Optimize start');

		DBA::e("OPTIMIZE TABLE `cache`");
		DBA::e("OPTIMIZE TABLE `locks`");
		DBA::e("OPTIMIZE TABLE `oembed`");
		DBA::e("OPTIMIZE TABLE `parsed_url`");
		DBA::e("OPTIMIZE TABLE `session`");

		if (DI::config()->get('system', 'optimize_all_tables')) {
			DBA::e("OPTIMIZE TABLE `apcontact`");
			DBA::e("OPTIMIZE TABLE `contact`");
			DBA::e("OPTIMIZE TABLE `contact-relation`");
			DBA::e("OPTIMIZE TABLE `conversation`");
			DBA::e("OPTIMIZE TABLE `diaspora-contact`");
			DBA::e("OPTIMIZE TABLE `diaspora-interaction`");
			DBA::e("OPTIMIZE TABLE `fcontact`");
			DBA::e("OPTIMIZE TABLE `gserver`");
			DBA::e("OPTIMIZE TABLE `gserver-tag`");
			DBA::e("OPTIMIZE TABLE `inbox-status`");
			DBA::e("OPTIMIZE TABLE `item-uri`");
			DBA::e("OPTIMIZE TABLE `notification`");
			DBA::e("OPTIMIZE TABLE `notify`");
			DBA::e("OPTIMIZE TABLE `photo`");
			DBA::e("OPTIMIZE TABLE `post`");
			DBA::e("OPTIMIZE TABLE `post-content`");
			DBA::e("OPTIMIZE TABLE `post-delivery-data`");
			DBA::e("OPTIMIZE TABLE `post-link`");
			DBA::e("OPTIMIZE TABLE `post-thread`");
			DBA::e("OPTIMIZE TABLE `post-thread-user`");
			DBA::e("OPTIMIZE TABLE `post-user`");
			DBA::e("OPTIMIZE TABLE `storage`");
			DBA::e("OPTIMIZE TABLE `tag`");
		}

		Logger::info('Optimize end');

		DI::lock()->release('optimize_tables');
	}
}

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
use Friendica\Model\Post;

class DelayedPublish
{
	 /**
	 * Publish a post, used for delayed postings
	  *
	  * @param array  $item
	  * @param int    $notify
	  * @param array  $taglist
	  * @param array  $attachments
	  * @param int    $preparation_mode
	  * @param string $uri
	  * @return void
	  */
	public static function execute(array $item, int $notify = 0, array $taglist = [], array $attachments = [], int $preparation_mode = Post\Delayed::PREPARED, string $uri = '')
	{
		$id = Post\Delayed::publish($item, $notify, $taglist, $attachments, $preparation_mode, $uri);
		Logger::notice('Post published', ['id' => $id, 'uid' => $item['uid'], 'notify' => $notify, 'unprepared' => $preparation_mode]);
	}
}

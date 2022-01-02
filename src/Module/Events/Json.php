<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Events;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class Json extends \Friendica\BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException();
		}

		$y = intval(DateTimeFormat::localNow('Y'));
		$m = intval(DateTimeFormat::localNow('m'));

		// Put some limit on dates. The PHP date functions don't seem to do so well before 1900.
		if ($y < 1901) {
			$y = 1900;
		}

		$dim    = Temporal::getDaysInMonth($y, $m);
		$start  = sprintf('%d-%d-%d %d:%d:%d', $y, $m, 1, 0, 0, 0);
		$finish = sprintf('%d-%d-%d %d:%d:%d', $y, $m, $dim, 23, 59, 59);

		if (!empty($_GET['start'])) {
			$start = $_GET['start'];
		}

		if (!empty($_GET['end'])) {
			$finish = $_GET['end'];
		}

		// put the event parametes in an array so we can better transmit them
		$event_params = [
			'event_id' => intval($_GET['id'] ?? 0),
			'start'    => $start,
			'finish'   => $finish,
			'ignore'   => 0,
		];

		// get events by id or by date
		if ($event_params['event_id']) {
			$r = Event::getListById(local_user(), $event_params['event_id']);
		} else {
			$r = Event::getListByDate(local_user(), $event_params);
		}

		$links = [];

		if (DBA::isResult($r)) {
			$r = Event::sortByDate($r);
			foreach ($r as $rr) {
				$j = DateTimeFormat::utc($rr['start'], 'j');
				if (empty($links[$j])) {
					$links[$j] = DI::baseUrl() . '/' . DI::args()->getCommand() . '#link-' . $j;
				}
			}
		}

		$events = [];

		// transform the event in a usable array
		if (DBA::isResult($r)) {
			$events = Event::sortByDate($r);

			$events = self::map($events);
		}

		header('Content-Type: application/json');
		echo json_encode($events);
		exit();
	}

	private static function map(array $events): array
	{
		return array_map(function ($event) {
			$item = Post::selectFirst(['plink', 'author-name', 'author-avatar', 'author-link', 'private', 'uri-id'], ['id' => $event['itemid']]);
			if (!DBA::isResult($item)) {
				// Using default values when no item had been found
				$item = ['plink' => '', 'author-name' => '', 'author-avatar' => '', 'author-link' => '', 'private' => Item::PUBLIC, 'uri-id' => ($event['uri-id'] ?? 0)];
			}

			return [
				'id'       => $event['id'],
				'title'    => $event['summary'],
				'start'    => DateTimeFormat::local($event['start']),
				'end'      => DateTimeFormat::local($event['finish']),
				'nofinish' => $event['nofinish'],
				'desc'     => $event['desc'],
				'location' => $event['location'],
				'item'     => $item,
			];
		}, $events);
	}
}

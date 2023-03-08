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

namespace Friendica\Module\Api\Friendica\Events;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Event;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Delivery;
use Friendica\Util\DateTimeFormat;

/**
 * API endpoint: /api/friendica/event_create
 */
class Create extends BaseApi
{
	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$request = $this->getRequest([
			'id'         => 0, //if provided, event will be amended
			'name'       => '', //summary of the event
			'desc'       => '', //description in BBCode
			'start_time' => '', //start_time, required
			'end_time'   => '', //endtime, required if nofinish false
			'place'      => '', //location of the event
			'publish'    => 0,  //publish message
			'allow_cid'  => '', //array of allowed person, if access restricted
			'allow_gid'  => '', //array of allowed groups, if access restricted
			'deny_cid'   => '', //array of denied person, if access restricted
			'deny_gid'   => '', //array of denied groups, if access restricted
		], $request);

		// error if no name specified
		if (empty($request['name'])) {
			throw new HTTPException\BadRequestException('event name not specified');
		}

		// error startDate is not specified
		if (empty($request['start_time'])) {
			throw new HTTPException\BadRequestException('startDate not specified');
		}

		// nofinish if end_time is not specified
		if (empty($request['end_time'])) {
			$finish   = DBA::NULL_DATETIME;
			$nofinish = true;
		} else {
			$finish   = DateTimeFormat::convert($request['end_time'], 'UTC', DI::app()->getTimeZone());
			$nofinish = false;
		}

		$start = DateTimeFormat::convert($request['start_time'], 'UTC', DI::app()->getTimeZone());

		// create event
		$event = [];

		$event['id']         = $request['id'];
		$event['uid']        = $uid;
		$event['type']       = 'event';
		$event['summary']    = $request['name'];
		$event['desc']       = $request['desc'];
		$event['location']   = $request['place'];
		$event['start']      = $start;
		$event['finish']     = $finish;
		$event['nofinish']   = $nofinish;

		$event['allow_cid'] = $request['allow_cid'];
		$event['allow_gid'] = $request['allow_gid'];
		$event['deny_cid']  = $request['deny_cid'];
		$event['deny_gid']  = $request['deny_gid'];
		$event['publish']   = $request['publish'];

		$event_id = Event::store($event);

		if (!empty($request['publish'])) {
			$item = ['network' => Protocol::DFRN, 'protocol' => Conversation::PARCEL_DIRECT, 'direction' => Conversation::PUSH];
			$item = Event::getItemArrayForId($event_id, $item);
			if (Item::insert($item)) {
				Worker::add(Worker::PRIORITY_HIGH, "Notifier", Delivery::POST, (int)$item['uri-id'], $uid);
			}
		}

		$result = ['success' => true, 'event_id' => $event_id, 'event' => $event];

		$this->response->exit('event_create', ['$result' => $result], $this->parameters['extension'] ?? null);
	}
}

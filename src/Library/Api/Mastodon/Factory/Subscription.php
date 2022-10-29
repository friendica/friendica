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

namespace Friendica\Library\Api\Mastodon\Factory;

use Friendica\Library\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Model\Subscription as ModelSubscription;

class Subscription extends BaseFactory
{
	/**
	 * @param int $applicationid Application Id
	 * @param int $uid           Item user
	 *
	 * @return \Friendica\Library\Api\Mastodon\Object\Status
	 */
	public function createForApplicationIdAndUserId(int $applicationid, int $uid): \Friendica\Library\Api\Mastodon\Object\Subscription
	{
		$subscription = DBA::selectFirst('subscription', [], ['application-id' => $applicationid, 'uid' => $uid]);
		return new \Friendica\Library\Api\Mastodon\Object\Subscription($subscription, ModelSubscription::getPublicVapidKey());
	}
}

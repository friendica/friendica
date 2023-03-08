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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\Model\Subscription as ModelSubscription;

class Subscription extends BaseFactory
{
	/**
	 * @param int $applicationid Application Id
	 * @param int $uid           Item user
	 *
	 * @return \Friendica\Object\Api\Mastodon\Status
	 */
	public function createForApplicationIdAndUserId(int $applicationid, int $uid): \Friendica\Object\Api\Mastodon\Subscription
	{
		$subscription = DBA::selectFirst('subscription', [], ['application-id' => $applicationid, 'uid' => $uid]);
		return new \Friendica\Object\Api\Mastodon\Subscription($subscription, ModelSubscription::getPublicVapidKey());
	}
}

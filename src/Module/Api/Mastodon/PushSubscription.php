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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Subscription;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\Notification;

/**
 * @see https://docs.joinmastodon.org/methods/notifications/push/
 */
class PushSubscription extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = $this->getRequest([
			'subscription' => [],
			'data'         => [],
		], $request);

		$subscription = [
			'application-id'                => $application['id'],
			'uid'                           => $uid,
			'endpoint'                      => $request['subscription']['endpoint'] ?? '',
			'pubkey'                        => $request['subscription']['keys']['p256dh'] ?? '',
			'secret'                        => $request['subscription']['keys']['auth'] ?? '',
			Notification::TYPE_FOLLOW       => $request['data']['alerts'][Notification::TYPE_FOLLOW] ?? false,
			Notification::TYPE_LIKE         => $request['data']['alerts'][Notification::TYPE_LIKE] ?? false,
			Notification::TYPE_RESHARE      => $request['data']['alerts'][Notification::TYPE_RESHARE] ?? false,
			Notification::TYPE_MENTION      => $request['data']['alerts'][Notification::TYPE_MENTION] ?? false,
			Notification::TYPE_POLL         => $request['data']['alerts'][Notification::TYPE_POLL] ?? false,
			Notification::TYPE_INTRODUCTION => $request['data']['alerts'][Notification::TYPE_INTRODUCTION] ?? false,
			Notification::TYPE_POST         => $request['data']['alerts'][Notification::TYPE_POST] ?? false,
		];

		$ret = Subscription::replace($subscription);

		Logger::info('Subscription stored', ['ret' => $ret, 'subscription' => $subscription]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}

	public function put(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = $this->getRequest([
			'data' => [],
		], $request);

		$subscription = Subscription::select($application['id'], $uid, ['id']);
		if (empty($subscription)) {
			Logger::info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			DI::mstdnError()->RecordNotFound();
		}

		$fields = [
			Notification::TYPE_FOLLOW       => $request['data']['alerts'][Notification::TYPE_FOLLOW] ?? false,
			Notification::TYPE_LIKE         => $request['data']['alerts'][Notification::TYPE_LIKE] ?? false,
			Notification::TYPE_RESHARE      => $request['data']['alerts'][Notification::TYPE_RESHARE] ?? false,
			Notification::TYPE_MENTION      => $request['data']['alerts'][Notification::TYPE_MENTION] ?? false,
			Notification::TYPE_POLL         => $request['data']['alerts'][Notification::TYPE_POLL] ?? false,
			Notification::TYPE_INTRODUCTION => $request['data']['alerts'][Notification::TYPE_INTRODUCTION] ?? false,
			Notification::TYPE_POST         => $request['data']['alerts'][Notification::TYPE_POST] ?? false,
		];

		$ret = Subscription::update($application['id'], $uid, $fields);

		Logger::info('Subscription updated', ['result' => $ret, 'application-id' => $application['id'], 'uid' => $uid, 'fields' => $fields]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}

	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$ret = Subscription::delete($application['id'], $uid);

		Logger::info('Subscription deleted', ['result' => $ret, 'application-id' => $application['id'], 'uid' => $uid]);

		System::jsonExit([]);
	}

	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		if (!Subscription::exists($application['id'], $uid)) {
			Logger::info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			DI::mstdnError()->RecordNotFound();
		}

		Logger::info('Fetch subscription', ['application-id' => $application['id'], 'uid' => $uid]);

		return DI::mstdnSubscription()->createForApplicationIdAndUserId($application['id'], $uid)->toArray();
	}
}

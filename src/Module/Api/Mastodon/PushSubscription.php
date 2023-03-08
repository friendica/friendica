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

namespace Friendica\Module\Api\Mastodon;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Mastodon\Subscription as SubscriptionFactory;
use Friendica\Model\Subscription;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\Notification;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/notifications/push/
 */
class PushSubscription extends BaseApi
{
	/** @var SubscriptionFactory */
	protected $subscriptionFac;
	/** @var Error */
	protected $errorFac;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, SubscriptionFactory $subscriptionFac, Error $errorFac, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->subscriptionFac = $subscriptionFac;
		$this->errorFac        = $errorFac;
	}

	protected function post(array $request = []): void
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
			Notification::TYPE_FOLLOW       => filter_var($request['data']['alerts'][Notification::TYPE_FOLLOW] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_LIKE         => filter_var($request['data']['alerts'][Notification::TYPE_LIKE] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_RESHARE      => filter_var($request['data']['alerts'][Notification::TYPE_RESHARE] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_MENTION      => filter_var($request['data']['alerts'][Notification::TYPE_MENTION] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_POLL         => filter_var($request['data']['alerts'][Notification::TYPE_POLL] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_INTRODUCTION => filter_var($request['data']['alerts'][Notification::TYPE_INTRODUCTION] ?? false, FILTER_VALIDATE_BOOLEAN),
			Notification::TYPE_POST         => filter_var($request['data']['alerts'][Notification::TYPE_POST] ?? false, FILTER_VALIDATE_BOOLEAN),
		];

		$ret = Subscription::replace($subscription);

		$this->logger->info('Subscription stored', ['ret' => $ret, 'subscription' => $subscription]);

		$subscriptionObj = $this->subscriptionFac->createForApplicationIdAndUserId($application['id'], $uid);
		$this->response->exitWithJson($subscriptionObj->toArray());
	}

	public function put(array $request = []): void
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = $this->getRequest([
			'data' => [],
		], $request);

		$subscription = Subscription::select($application['id'], $uid, ['id']);
		if (empty($subscription)) {
			$this->logger->info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			$this->errorFac->RecordNotFound();
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

		$this->logger->info('Subscription updated', [
			'result'         => $ret,
			'application-id' => $application['id'],
			'uid'            => $uid,
			'fields'         => $fields,
		]);

		$subscriptionObj = $this->subscriptionFac->createForApplicationIdAndUserId($application['id'], $uid);
		$this->response->exitWithJson($subscriptionObj->toArray());
	}

	protected function delete(array $request = []): void
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$ret = Subscription::delete($application['id'], $uid);

		$this->logger->info('Subscription deleted', [
			'result'         => $ret,
			'application-id' => $application['id'],
			'uid'            => $uid,
		]);

		$this->response->exitWithJson([]);
	}

	protected function rawContent(array $request = []): void
	{
		self::checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		if (!Subscription::exists($application['id'], $uid)) {
			$this->logger->info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			$this->errorFac->RecordNotFound();
		}

		$this->logger->info('Fetch subscription', ['application-id' => $application['id'], 'uid' => $uid]);

		$subscriptionObj = $this->subscriptionFac->createForApplicationIdAndUserId($application['id'], $uid);
		$this->response->exitWithJson($subscriptionObj->toArray());
	}
}

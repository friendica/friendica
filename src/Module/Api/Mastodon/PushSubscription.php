<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
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

	public function __construct(Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, SubscriptionFactory $subscriptionFac, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->subscriptionFac = $subscriptionFac;
	}

	protected function post(array $request = []): void
	{
		$this->checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = $this->getRequest([
			'subscription' => [],
			'data'         => [],
		], $request);

		$subscription = [
			'application-id'                => $application['id'],
			'uid'                           => $uid,
			'endpoint'                      => $request['subscription']['endpoint']       ?? '',
			'pubkey'                        => $request['subscription']['keys']['p256dh'] ?? '',
			'secret'                        => $request['subscription']['keys']['auth']   ?? '',
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
		$this->jsonExit($subscriptionObj->toArray());
	}

	public function put(array $request = []): void
	{
		$this->checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$request = $this->getRequest([
			'data' => [],
		], $request);

		$subscription = Subscription::select($application['id'], $uid, ['id']);
		if (empty($subscription)) {
			$this->logger->info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$fields = [
			Notification::TYPE_FOLLOW       => $this->setBoolean($request['data']['alerts'][Notification::TYPE_FOLLOW] ?? false),
			Notification::TYPE_LIKE         => $this->setBoolean($request['data']['alerts'][Notification::TYPE_LIKE] ?? false),
			Notification::TYPE_RESHARE      => $this->setBoolean($request['data']['alerts'][Notification::TYPE_RESHARE] ?? false),
			Notification::TYPE_MENTION      => $this->setBoolean($request['data']['alerts'][Notification::TYPE_MENTION] ?? false),
			Notification::TYPE_POLL         => $this->setBoolean($request['data']['alerts'][Notification::TYPE_POLL] ?? false),
			Notification::TYPE_INTRODUCTION => $this->setBoolean($request['data']['alerts'][Notification::TYPE_INTRODUCTION] ?? false),
			Notification::TYPE_POST         => $this->setBoolean($request['data']['alerts'][Notification::TYPE_POST] ?? false),
		];

		$ret = Subscription::update($application['id'], $uid, $fields);

		$this->logger->info('Subscription updated', [
			'result'         => $ret,
			'application-id' => $application['id'],
			'uid'            => $uid,
			'fields'         => $fields,
		]);

		$subscriptionObj = $this->subscriptionFac->createForApplicationIdAndUserId($application['id'], $uid);
		$this->jsonExit($subscriptionObj->toArray());
	}

	private function setBoolean($input): bool
	{
		if (is_bool($input)) {
			return $input;
		}
		return strtolower((string) $input) == 'true';
	}

	protected function delete(array $request = []): void
	{
		$this->checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		$ret = Subscription::delete($application['id'], $uid);

		$this->logger->info('Subscription deleted', [
			'result'         => $ret,
			'application-id' => $application['id'],
			'uid'            => $uid,
		]);

		$this->jsonExit([]);
	}

	protected function get(array $request = []): void
	{
		$this->checkAllowedScope(self::SCOPE_PUSH);
		$uid         = self::getCurrentUserID();
		$application = self::getCurrentApplication();

		if (!Subscription::exists($application['id'], $uid)) {
			$this->logger->info('Subscription not found', ['application-id' => $application['id'], 'uid' => $uid]);
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$this->logger->info('Fetch subscription', ['application-id' => $application['id'], 'uid' => $uid]);

		$subscriptionObj = $this->subscriptionFac->createForApplicationIdAndUserId($application['id'], $uid);
		$this->response->addJsonContent($subscriptionObj->toArray());
	}
}

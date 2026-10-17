<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Protocol\ActivityPub;

use Dice\Dice;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Protocol\ActivityPub\Delivery;
use Friendica\Protocol\Delivery as ProtocolDelivery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DeliveryTest extends TestCase
{
	/**
	 * A transport exception leaves no response object. The failure must be reported as a server
	 * failure instead of ending the worker with "Call to a member function getReturnCode() on null".
	 */
	public function testDeliveryToInboxWithTransportExceptionReportsServerFailure(): void
	{
		$cache = $this->createMock(ICanCache::class);
		$cache->method('get')->willReturn(['id' => 'https://example.test/activity/1', 'type' => 'Create']);

		$config = $this->createMock(IManageConfigValues::class);
		$config->method('get')->willReturn(null);

		$client = $this->createMock(ICanSendHttpRequests::class);
		$client->method('post')->willThrowException(new \RuntimeException('cURL error 28: timeout'));

		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class      => new NullLogger(),
			ICanCache::class            => $cache,
			IManageConfigValues::class  => $config,
			ICanSendHttpRequests::class => $client,
			default                     => throw new \LogicException('Unexpected class requested: ' . $name),
		});
		DI::init($dice, true);

		$key = openssl_pkey_new(['private_key_bits' => 2048]);
		openssl_pkey_export($key, $privateKey);
		$owner = ['uid' => 1, 'url' => 'https://example.test/profile/owner', 'uprvkey' => $privateKey];

		$result = Delivery::deliverToInbox(ProtocolDelivery::POST, 1, 'https://remote.test/inbox', $owner, [], 0);

		$this->assertSame(['success' => false, 'serverfailure' => true, 'drop' => false], $result);
	}
}

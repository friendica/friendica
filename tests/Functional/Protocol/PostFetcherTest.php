<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Protocol;

use Friendica\DI;
use Friendica\Event\FetchItemByLinkEvent;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Post\LinkResolver;
use Friendica\Protocol\PostFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Friendica\Test\FixtureTestCase;

class PostFetcherTest extends FixtureTestCase
{
	// post-user 1 of user 42 in api.fixture.php: uri "1", guid "1", plink "https://friendica.local/display/1"
	private const UID = 42;

	public function testFetchByLinkReturnsKnownPostWithoutNetwork(): void
	{
		self::assertSame(1, DI::postFetcher()->fetchByLink('https://friendica.local/display/1', self::UID));
	}

	public function testGetQuoteUriIdWithoutShareIsZero(): void
	{
		self::assertSame(0, DI::postFetcher()->getQuoteUriId('Just text', self::UID));
	}

	public function testGetQuoteUriIdFindsSharedPostByGuid(): void
	{
		// The link points to post 2, so only the guid lookup returns post 1
		$body = "[share guid='1' link='https://friendica.local/display/2']Parent status[/share]";

		self::assertSame(1, DI::postFetcher()->getQuoteUriId($body, self::UID));
	}

	public function testGetQuoteUriIdFindsSharedPostByMessageId(): void
	{
		self::assertSame(1, DI::postFetcher()->getQuoteUriId('[share]1[/share]', self::UID));
	}

	public static function hookItemIdProvider(): array
	{
		return [
			'int' => [7, 7],
		];
	}

	#[DataProvider('hookItemIdProvider')]
	public function testFetchByLinkUsesAddonResultWithoutNetwork(int $hookItemId, int $expected): void
	{
		$dispatcher = $this->createStub(EventDispatcherInterface::class);
		$dispatcher->method('dispatch')->willReturnCallback(function (FetchItemByLinkEvent $event) use ($hookItemId): FetchItemByLinkEvent {
			$event->setItemId($hookItemId);
			return $event;
		});

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects(self::never())->method('head');

		$fetcher = new PostFetcher(new LinkResolver(), new NullLogger(), $dispatcher, $httpClient);

		self::assertSame($expected, $fetcher->fetchByLink('https://remote.example/unknown/1', self::UID));
	}
}

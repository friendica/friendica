<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Worker;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Cache\Type\ArrayCache;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\ActivityPub\Processor;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use Friendica\Util\DateTimeFormat;
use Friendica\Worker\FetchMissingActivity;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class FetchMissingActivityTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	private const PARENT = 'https://198.51.100.1/objects/parent';

	private const CHILD = 'https://198.51.100.1/objects/child';

	// Consecutive server errors after which fetching from a host is suspended
	private const HOST_FAILURE_LIMIT = 10;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();

		// The remote server is an IP literal from TEST-NET-2, it needs no DNS entry
		DI::config()->set('system', 'block_private_addresses', false);
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	public function testPermanentErrorIsNotFetchedAgain(): void
	{
		$mock = $this->mockResponses(new Response(404), new Response(404));

		FetchMissingActivity::execute(self::PARENT);
		FetchMissingActivity::execute(self::PARENT);

		self::assertSame(1, $mock->count());
	}

	public function testPermanentErrorIsNotFetchedAgainByAnotherWorker(): void
	{
		$this->useCacheLocalToEveryWorker();
		$mock = $this->mockResponses(new Response(404), new Response(404));

		FetchMissingActivity::execute(self::PARENT);
		FetchMissingActivity::execute(self::PARENT);

		self::assertSame(1, $mock->count());
	}

	public function testTemporaryErrorIsFetchedAgain(): void
	{
		$mock = $this->mockResponses(new Response(503), new Response(503));

		FetchMissingActivity::execute(self::PARENT);
		FetchMissingActivity::execute(self::PARENT);

		self::assertSame(0, $mock->count());
	}

	public function testPermanentErrorDeletesWaitingChildren(): void
	{
		$this->mockResponses(new Response(404));
		$child = $this->addWaitingChild();

		FetchMissingActivity::execute(self::PARENT);

		self::assertFalse(DBA::exists('inbox-entry', ['id' => $child]));
	}

	public function testDeferLimitKeepsWaitingChildren(): void
	{
		$this->mockResponses(new Response(503));
		$child = $this->addWaitingChild();
		$this->runAsLastRetrial();

		FetchMissingActivity::execute(self::PARENT);

		self::assertTrue(DBA::exists('inbox-entry', ['id' => $child]));
	}

	public function testUnauthorizedIsPermanentError(): void
	{
		$this->mockResponses(new Response(401), new Response(401), new Response(401));

		self::assertNull(Processor::fetchMissingActivity(self::PARENT));
	}

	public function testServerErrorsSuspendFetchingFromHost(): void
	{
		$responses = array_fill(0, self::HOST_FAILURE_LIMIT + 1, new Response(500));
		$mock      = $this->mockResponses(...$responses);

		for ($i = 0; $i <= self::HOST_FAILURE_LIMIT; $i++) {
			self::assertSame('', Processor::fetchMissingActivity(self::PARENT . '/' . $i));
		}

		self::assertSame(1, $mock->count());
	}

	public function testServerErrorsSuspendFetchingFromHostForAnotherWorker(): void
	{
		$this->useCacheLocalToEveryWorker();
		$responses = array_fill(0, self::HOST_FAILURE_LIMIT + 1, new Response(500));
		$mock      = $this->mockResponses(...$responses);

		for ($i = 0; $i <= self::HOST_FAILURE_LIMIT; $i++) {
			Processor::fetchMissingActivity(self::PARENT . '/' . $i);
		}

		self::assertSame(1, $mock->count());
	}

	public function testTimeoutsSuspendFetchingFromHost(): void
	{
		$requests = 0;
		// MockHandler would pass a queued exception to the on_headers callback, a real transport error never reaches it
		$this->httpRequestHandler->setHandler(function (RequestInterface $request) use (&$requests): PromiseInterface {
			$requests++;
			return Create::rejectionFor(new ConnectException('Operation timed out', $request));
		});

		for ($i = 0; $i <= self::HOST_FAILURE_LIMIT; $i++) {
			self::assertSame('', Processor::fetchMissingActivity(self::PARENT . '/' . $i));
		}

		self::assertSame(self::HOST_FAILURE_LIMIT, $requests);
	}

	public function testSuccessResetsServerErrorsOfHost(): void
	{
		$responses = array_merge(
			array_fill(0, self::HOST_FAILURE_LIMIT - 1, new Response(500)),
			[new Response(200)],
			array_fill(0, self::HOST_FAILURE_LIMIT, new Response(500)),
		);
		$mock = $this->mockResponses(...$responses);

		for ($i = 0; $i < 2 * self::HOST_FAILURE_LIMIT; $i++) {
			Processor::fetchMissingActivity(self::PARENT . '/' . $i);
		}

		self::assertSame(0, $mock->count());
	}

	/**
	 * Every worker is a process of its own, a driver like APCu doesn't keep anything between them on the CLI
	 */
	private function useCacheLocalToEveryWorker(): void
	{
		DI::init(DI::getDice()->addRule(ICanCache::class, [
			'instanceOf'      => ArrayCache::class,
			'constructParams' => ['localhost'],
			'shared'          => false,
			'call'            => null,
		]));
	}

	private function mockResponses(Response ...$responses): MockHandler
	{
		$mock = new MockHandler($responses);
		$this->httpRequestHandler->setHandler($mock);

		return $mock;
	}

	/**
	 * A reply that waits in the inbox for its parent, as Queue::isProcessable() leaves it: no worker assigned
	 */
	private function addWaitingChild(): int
	{
		DBA::insert('inbox-entry', [
			'activity-id'    => self::CHILD . '/activity',
			'object-id'      => self::CHILD,
			'in-reply-to-id' => self::PARENT,
			'type'           => 'as:Create',
			'object-type'    => 'as:Note',
			'received'       => DateTimeFormat::utcNow(),
			'trust'          => true,
		]);

		return DBA::lastInsertId();
	}

	/**
	 * Run the task as the worker entry that has reached FetchMissingActivity::WORKER_DEFER_LIMIT
	 */
	private function runAsLastRetrial(): void
	{
		$created = DateTimeFormat::utc('now - 2 hours');
		DBA::insert('workerqueue', [
			'command'   => 'FetchMissingActivity',
			'parameter' => json_encode([self::PARENT]),
			'priority'  => Worker::PRIORITY_HIGH,
			'created'   => $created,
			'retrial'   => FetchMissingActivity::WORKER_DEFER_LIMIT,
		]);
		$wid = DBA::lastInsertId();
		DBA::insert('fetch-entry', ['url' => self::PARENT, 'created' => $created, 'wid' => $wid]);

		DI::appHelper()->setQueue(DBA::selectFirst('workerqueue', [], ['id' => $wid]));
	}
}

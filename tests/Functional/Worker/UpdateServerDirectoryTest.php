<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Functional\Worker;

use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTestCase;
use Friendica\Worker\UpdateServerDirectory;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * The responses are shortened answers of real servers
 */
class UpdateServerDirectoryTest extends MockedTestCase
{
	use DiceHttpMockHandlerTrait;

	/** @var string[] Profile URLs of the queued "DiscoverGroup" tasks */
	private array $discovered = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();

		/** @var \Friendica\Event\EventDispatcher */
		$eventDispatcher = DI::eventDispatcher();

		// The tasks are only collected, they aren't added to the worker queue
		$eventDispatcher->addListener(ArrayFilterEvent::ADD_WORKER_TASK, function (ArrayFilterEvent $event): void {
			$arr = $event->getArray();
			if ($arr['args'][1] === 'DiscoverGroup') {
				$this->discovered[] = $arr['args'][2];
			}
			$arr['run_cmd'] = false;
			$event->setArray($arr);
		});
	}

	protected function tearDown(): void
	{
		$this->tearDownHandler();

		parent::tearDown();
	}

	private static function getFixture(string $name): array
	{
		return json_decode(file_get_contents(__DIR__ . '/../../Fixtures/groups/' . $name . '.json'), true);
	}

	private static function jsonResponse(array $data): Response
	{
		return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
	}

	public function testLemmyGroupsFallBackToApiV3(): void
	{
		$mock = new MockHandler([
			new Response(404),
			self::jsonResponse(self::getFixture('lemmy-v3')),
		]);
		$this->httpRequestHandler->setHandler($mock);

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://lemmy.world', 'platform' => 'lemmy']);

		self::assertSame([
			'https://lemmy.world/c/allinone',
			'https://lemmy.world/c/genX_Safespace',
			'https://lemmy.world/c/Carhartt',
			'https://lemmy.world/c/remedialhq',
			'https://lemmy.world/c/eatandexplore',
		], $this->discovered);
		self::assertSame('/api/v3/community/list', $mock->getLastRequest()->getUri()->getPath());
	}

	public function testLemmyGroupsAreFiltered(): void
	{
		$data = self::getFixture('lemmy-v3');

		$data['communities'][0]['community']['deleted']    = true;
		$data['communities'][1]['community']['removed']    = true;
		$data['communities'][2]['community']['visibility'] = 'LocalOnly';
		// Entries from other hosts mustn't be probed
		$data['communities'][3]['community']['actor_id'] = 'https://example.com/c/remedialhq';

		$this->httpRequestHandler->setHandler(new MockHandler([new Response(404), self::jsonResponse($data)]));

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://lemmy.world', 'platform' => 'lemmy']);

		self::assertSame(['https://lemmy.world/c/eatandexplore'], $this->discovered);
	}

	public function testLemmyGroupsFromApiV4(): void
	{
		$mock = new MockHandler([self::jsonResponse(self::getFixture('lemmy-v4'))]);
		$this->httpRequestHandler->setHandler($mock);

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://voyager.lemmy.ml', 'platform' => 'lemmy']);

		// "test_no_mod" is a local only community
		self::assertSame([
			'https://voyager.lemmy.ml/c/pubtest0906',
			'https://voyager.lemmy.ml/c/Boost',
			'https://voyager.lemmy.ml/c/secrets',
			'https://voyager.lemmy.ml/c/sjmarf',
		], $this->discovered);

		$uri = $mock->getLastRequest()->getUri();
		self::assertSame('/api/v4/community/list', $uri->getPath());
		self::assertSame('type_=local&sort=active_monthly&limit=50', $uri->getQuery());
	}

	public function testPieFedGroups(): void
	{
		$mock = new MockHandler([self::jsonResponse(self::getFixture('piefed'))]);
		$this->httpRequestHandler->setHandler($mock);

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://piefed.social', 'platform' => 'piefed']);

		self::assertSame([
			'https://piefed.social/c/microblogs',
			'https://piefed.social/c/ask',
			'https://piefed.social/c/movies',
		], $this->discovered);
		self::assertSame('/api/alpha/community/list', $mock->getLastRequest()->getUri()->getPath());
	}

	public function testNodeBBGroups(): void
	{
		$mock = new MockHandler([self::jsonResponse(self::getFixture('nodebb'))]);
		$this->httpRequestHandler->setHandler($mock);

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://community.nodebb.org', 'platform' => 'nodebb']);

		// Category 15 is a link to an external page
		$expected = [];
		foreach ([2, 3, 13, 6, 30, 5, 7, 17, 18, 10, 16, 28, 21, 26, 4] as $cid) {
			$expected[] = 'https://community.nodebb.org/category/' . $cid;
		}

		self::assertSame($expected, $this->discovered);
		self::assertSame('/api/categories', $mock->getLastRequest()->getUri()->getPath());
	}

	public function testNodeBBSectionsAreSkipped(): void
	{
		$data = self::getFixture('nodebb');

		// Sections only structure the list, but their children are regular categories
		$data['categories'] = [['cid' => 1, 'isSection' => 1, 'children' => $data['categories'][1]['children']]];

		$this->httpRequestHandler->setHandler(new MockHandler([self::jsonResponse($data)]));

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://community.nodebb.org', 'platform' => 'nodebb']);

		self::assertSame([
			'https://community.nodebb.org/category/13',
			'https://community.nodebb.org/category/6',
			'https://community.nodebb.org/category/30',
		], $this->discovered);
	}

	public function testInvalidResponse(): void
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(200, ['Content-Type' => 'text/html'], '<html></html>'),
		]));

		UpdateServerDirectory::execute(['id' => 1, 'url' => 'https://community.nodebb.org', 'platform' => 'nodebb']);

		self::assertSame([], $this->discovered);
	}
}

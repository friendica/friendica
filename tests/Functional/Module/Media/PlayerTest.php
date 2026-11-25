<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Module\Media;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Content\Post\Entity\PostMedia;
use Friendica\Core\EarlyExitException;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Module\Media\Player;
use Friendica\Module\Response;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Test\FixtureTestCase;
use Psr\Log\NullLogger;

final class PlayerTest extends FixtureTestCase
{
	// Owner of the post 1 of the fixture
	private const OWNER_UID = 42;
	private const OTHER_UID = 43;
	private const URI_ID    = 1;

	protected function setUp(): void
	{
		parent::setUp();

		Renderer::registerTemplateEngine(FriendicaSmartyEngine::class);
	}

	private function insertMedia(int $type, string $mimetype, string $url): int
	{
		DBA::insert('post-media', ['uri-id' => self::URI_ID, 'url' => $url, 'type' => $type, 'mimetype' => $mimetype]);

		return DBA::lastInsertId();
	}

	/**
	 * @return int HTTP status code of the response
	 */
	private function requestStatus(int $mediaId, int $viewerUid): int
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('getLocalUserId')->willReturn($viewerUid);

		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('__toString')->willReturn('https://friendica.local');

		$args = $this->createStub(Arguments::class);
		$args->method('getMethod')->willReturn('GET');

		$module = new Player(DI::postMediaRepository(), $session, DI::l10n(), $baseUrl, $args, new NullLogger(), DI::profiler(), new Response(), [], ['id' => (string) $mediaId]);

		try {
			(new \ReflectionMethod($module, 'rawContent'))->invoke($module, []);
		} catch (EarlyExitException $e) {
			return $e->getResponse()->getStatusCode();
		}

		self::fail('The module should end the request');
	}

	public function testAnonymousVisitorIsRejected(): void
	{
		$id = $this->insertMedia(PostMedia::TYPE_VIDEO, 'video/mp4', 'https://remote.example/video.mp4');

		self::assertSame(403, $this->requestStatus($id, 0));
	}

	public function testUnknownMediaIsNotFound(): void
	{
		self::assertSame(404, $this->requestStatus(999999, self::OWNER_UID));
	}

	public function testOwnerGetsPlayer(): void
	{
		$id = $this->insertMedia(PostMedia::TYPE_VIDEO, 'video/mp4', 'https://remote.example/video.mp4');

		self::assertSame(200, $this->requestStatus($id, self::OWNER_UID));
	}

	public function testPrivatePostOfSomeoneElseIsNotFound(): void
	{
		DBA::update('post-user', ['private' => Item::PRIVATE], ['uri-id' => self::URI_ID]);
		$id = $this->insertMedia(PostMedia::TYPE_VIDEO, 'video/mp4', 'https://remote.example/video.mp4');

		self::assertSame(404, $this->requestStatus($id, self::OTHER_UID));
	}

	public function testMediaThatCannotBePlayedIsNotFound(): void
	{
		$id = $this->insertMedia(PostMedia::TYPE_DOCUMENT, 'application/pdf', 'https://remote.example/agenda.pdf');

		self::assertSame(404, $this->requestStatus($id, self::OWNER_UID));
	}
}

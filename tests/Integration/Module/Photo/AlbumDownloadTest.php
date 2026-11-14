<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Photo;

use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Photo\AlbumDownload;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Covers the access checks and the error cases.
 * The successful download sends the file and exits, so it cannot be run inside PHPUnit.
 */
#[RequiresPhpExtension('zip')]
class AlbumDownloadTest extends FixtureTestCase
{
	private const NICKNAME = 'selfcontact'; // Local user from api.fixture.php.
	private const UID      = 42;

	protected function setUp(): void
	{
		parent::setUp();
		// Owner data is cached statically per uid, fixture changes would not be seen otherwise.
		(new \ReflectionProperty(User::class, 'owner'))->setValue(null, []);
	}

	protected function tearDown(): void
	{
		DI::session()->clear();
		parent::tearDown();
	}

	private function login(int $uid): void
	{
		DI::session()->set('authenticated', true);
		DI::session()->set('uid', $uid);
	}

	private function handle(string $nickname, string $album): void
	{
		$module = $this->dice->create(AlbumDownload::class, [[], ['nickname' => $nickname, 'album' => $album]]);

		$module->handleRequest(new ServerRequest('GET', DI::baseUrl() . '/photos/' . $nickname . '/album/' . $album . '/download'));
	}

	public function testUnknownUserIsNotFound(): void
	{
		$this->login(self::UID);

		$this->expectException(NotFoundException::class);
		$this->handle('nobody', bin2hex('Album'));
	}

	public function testAnonymousIsForbidden(): void
	{
		$this->expectException(ForbiddenException::class);
		$this->handle(self::NICKNAME, bin2hex('Album'));
	}

	public function testOtherUserIsForbidden(): void
	{
		$this->login(self::UID + 1);

		$this->expectException(ForbiddenException::class);
		$this->handle(self::NICKNAME, bin2hex('Album'));
	}

	public function testInvalidAlbumNameIsBadRequest(): void
	{
		$this->login(self::UID);

		$this->expectException(BadRequestException::class);
		$this->handle(self::NICKNAME, 'not-hex');
	}

	public function testUnknownAlbumIsNotFound(): void
	{
		$this->login(self::UID);

		$this->expectException(NotFoundException::class);
		$this->handle(self::NICKNAME, bin2hex('No such album'));
	}
}

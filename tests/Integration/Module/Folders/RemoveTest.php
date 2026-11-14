<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Folders;

use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Content\Post\Repository\PostCategory;
use Friendica\DI;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;
use Friendica\Module\Folders\Remove;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;

class RemoveTest extends FixtureTestCase
{
	private const USER_ID = 42; // Local user from api.fixture.php.

	private array $originalRequest;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalRequest = $_REQUEST;
		DI::sysmsg()->flushNotices();
	}

	protected function tearDown(): void
	{
		$_REQUEST = $this->originalRequest;
		parent::tearDown();
	}

	private function login(): void
	{
		DI::userSession()->set('authenticated', true);
		DI::userSession()->set('uid', self::USER_ID);
	}

	private function isFiled(string $folder): bool
	{
		return DI::dba()->exists('post-category', ['uri-id' => 1, 'uid' => self::USER_ID, 'type' => Category::FILE, 'tid' => Tag::getID($folder)]);
	}

	/** @param array<string, string> $query */
	private function get(array $query): void
	{
		$_REQUEST = $query;

		$module = new Remove(
			DI::userSession(),
			DI::getDice()->create(PostCategory::class),
			DI::sysmsg(),
			DI::l10n(),
			DI::baseUrl(),
			DI::args(),
			DI::logger(),
			DI::profiler(),
			new Response(),
			[],
			[],
			DI::eventDispatcher(),
		);

		try {
			$module->handleRequest((new ServerRequest(Router::GET, DI::baseUrl() . '/folders/remove'))->withQueryParams($query));
		} catch (FoundException) {
			// Every outcome ends with a redirect to the folder overview.
		}
	}

	public function testRemoveDeletesFolder(): void
	{
		$this->login();
		self::assertTrue(DI::dba()->insert('post-category', ['uri-id' => 1, 'uid' => self::USER_ID, 'type' => Category::FILE, 'tid' => Tag::getID('Foo')]));

		$this->get(['file' => 'Foo', 't' => BaseModule::getFormSecurityToken('folder_remove')]);

		self::assertFalse($this->isFiled('Foo'));
		self::assertSame([], DI::sysmsg()->flushNotices());
	}

	public function testRemoveOfMissingFolderShowsNoticeWithoutCreatingTag(): void
	{
		$this->login();

		$this->get(['file' => 'Missing folder', 't' => BaseModule::getFormSecurityToken('folder_remove')]);

		self::assertSame([DI::l10n()->t('Folder was not removed')], DI::sysmsg()->flushNotices());
		self::assertFalse(DI::dba()->exists('tag', ['name' => 'Missing folder']));
	}

	public function testRemoveWithWrongTokenIsRejected(): void
	{
		$this->login();
		self::assertTrue(DI::dba()->insert('post-category', ['uri-id' => 1, 'uid' => self::USER_ID, 'type' => Category::FILE, 'tid' => Tag::getID('Foo')]));

		$this->get(['file' => 'Foo', 't' => 'invalid']);

		self::assertTrue($this->isFiled('Foo'));
	}

	public function testVisitorIsRejected(): void
	{
		$this->expectException(ForbiddenException::class);

		$this->get(['file' => 'Foo']);
	}
}

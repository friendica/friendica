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
use Friendica\Content\Widget\SidebarMenu;
use Friendica\DI;
use Friendica\Model\Post\Category;
use Friendica\Model\Tag;
use Friendica\Module\Folders\Rename;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;

class RenameTest extends FixtureTestCase
{
	private const USER_ID = 42; // Local user from api.fixture.php.

	private array $originalRequest;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalRequest = $_REQUEST;
		$this->useHttpMethod(Router::POST);
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

	private function fileIntoFolder(int $uriId, string $folder): void
	{
		self::assertTrue(DI::dba()->insert('post-category', ['uri-id' => $uriId, 'uid' => self::USER_ID, 'type' => Category::FILE, 'tid' => Tag::getID($folder)]));
	}

	private function isFiled(int $uriId, string $folder): bool
	{
		$tag = DI::dba()->selectFirst('tag', ['id'], ['name' => $folder, 'url' => '']);

		return !empty($tag) && DI::dba()->exists('post-category', ['uri-id' => $uriId, 'uid' => self::USER_ID, 'type' => Category::FILE, 'tid' => $tag['id']]);
	}

	/** @param array<string, string> $body */
	private function post(array $body): void
	{
		$_REQUEST = $body;

		$module = new Rename(
			DI::userSession(),
			DI::getDice()->create(PostCategory::class),
			DI::sysmsg(),
			DI::getDice()->create(SidebarMenu::class),
			DI::page(),
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
			$module->handleRequest((new ServerRequest(Router::POST, DI::baseUrl() . '/folders/rename'))->withParsedBody($body));
		} catch (FoundException) {
			// Every outcome ends with a redirect to the folder overview.
		}
	}

	public function testRenameMovesPosts(): void
	{
		$this->login();
		$this->fileIntoFolder(1, 'Foo');

		$this->post(['oldname' => 'Foo', 'newname' => 'Bar', 'form_security_token' => BaseModule::getFormSecurityToken('folder_rename')]);

		self::assertFalse($this->isFiled(1, 'Foo'));
		self::assertTrue($this->isFiled(1, 'Bar'));
		self::assertSame([], DI::sysmsg()->flushNotices());
	}

	public function testCaseOnlyRenameShowsDedicatedNotice(): void
	{
		$this->login();
		$this->fileIntoFolder(1, 'Bar');

		$this->post(['oldname' => 'Bar', 'newname' => 'bar', 'form_security_token' => BaseModule::getFormSecurityToken('folder_rename')]);

		self::assertTrue($this->isFiled(1, 'Bar'));
		self::assertSame([DI::l10n()->t('Folder names that only differ in upper and lower case are the same folder.')], DI::sysmsg()->flushNotices());
	}

	public function testRenameOfMissingFolderShowsNotice(): void
	{
		$this->login();

		$this->post(['oldname' => 'Missing folder', 'newname' => 'Bar', 'form_security_token' => BaseModule::getFormSecurityToken('folder_rename')]);

		self::assertSame([DI::l10n()->t('Folder was not renamed')], DI::sysmsg()->flushNotices());
	}

	public function testRenameWithoutTokenIsRejected(): void
	{
		$this->login();
		$this->fileIntoFolder(1, 'Foo');

		$this->post(['oldname' => 'Foo', 'newname' => 'Bar']);

		self::assertTrue($this->isFiled(1, 'Foo'));
		self::assertFalse($this->isFiled(1, 'Bar'));
	}

	public function testVisitorIsRejected(): void
	{
		$this->expectException(ForbiddenException::class);

		$this->post(['oldname' => 'Foo', 'newname' => 'Bar']);
	}
}

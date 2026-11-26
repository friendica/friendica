<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Friendica\Photoalbum;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Module\Api\Friendica\Photoalbum\Delete;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class DeleteTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testDeleteRemovesThePhotoPostsOfTheAlbum(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/photo/photo.fixture.php', DI::dba());
		DI::dba()->update('post-content', ['resource-id' => '709057080661a283a6aa598501504178'], ['uri-id' => 3]);
		DI::dba()->update('post-user', ['post-type' => Item::PT_IMAGE], ['id' => 3]);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/friendica/photoalbum/delete'))
			->withParsedBody(['album' => 'test_album']);

		$module   = new Delete(DI::postDeletion(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertFalse(DI::dba()->exists('photo', ['album' => 'test_album', 'uid' => 42]));
		self::assertTrue(DI::dba()->exists('post-user', ['id' => 3, 'deleted' => true]));
		self::assertTrue(DI::dba()->exists('post-user', ['id' => 1, 'deleted' => false]));
	}
}

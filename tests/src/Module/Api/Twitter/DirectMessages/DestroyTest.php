<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Api\Twitter\DirectMessages\Destroy;
use Friendica\Test\ApiTestCase;

class DestroyTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the api_direct_messages_destroy() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroy(): void
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);

		(new Destroy(DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);
	}

	/**
	 * Test the api_direct_messages_destroy() function with the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithVerbose(): void
	{
		$response = (new Destroy(DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id or parenturi not specified', $json->message);
	}

	/**
	 * Test the api_direct_messages_destroy() function without an authenticated user.
	 *
	 */
	public function testApiDirectMessagesDestroyWithoutAuthenticatedUser(): void
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_direct_messages_destroy('json');
		*/
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithId(): void
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		(new Destroy(DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'id' => 1,
			]);
	}

	/**
	 * Test the api_direct_messages_destroy() with a non-zero ID and the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithIdAndVerbose(): void
	{
		$response = (new Destroy(DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'id'                  => 1,
				'friendica_parenturi' => 'parent_uri',
				'friendica_verbose'   => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id not in database', $json->message);
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithCorrectId(): void
	{
		$this->loadFixture(__DIR__ . '/../../../../../Fixtures/mail/mail.fixture.php', DI::dba());
		$ids = DBA::selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$response = (new Destroy(DI::dba(), DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock, [
				'id'                => $id,
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('ok', $json->result);
		self::assertEquals('message deleted', $json->message);
	}
}

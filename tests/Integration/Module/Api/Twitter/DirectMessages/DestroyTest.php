<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Twitter\DirectMessages\Destroy;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\ServerRequest;

final class DestroyTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiDirectMessagesDestroy(): void
	{
		$this->expectException(BadRequestException::class);

		$request = new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy');

		$this->createModule()->handleRequest($request);
	}

	public function testApiDirectMessagesDestroyWithVerbose(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy'))
			->withParsedBody([
				'friendica_verbose' => 'true',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id or parenturi not specified', $json->message);
	}

	public function testApiDirectMessagesDestroyWithId(): void
	{
		$this->expectException(BadRequestException::class);

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy'))
			->withParsedBody([
				'id' => 1,
			]);

		$this->createModule()->handleRequest($request);
	}

	public function testApiDirectMessagesDestroyWithIdAndVerbose(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy'))
			->withParsedBody([
				'id'                  => 1,
				'friendica_parenturi' => 'parent_uri',
				'friendica_verbose'   => 'true',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id not in database', $json->message);
	}

	public function testApiDirectMessagesDestroyWithCorrectId(): void
	{
		DI::dba()->insert('mail', [
			'uid'           => 1042,
			'author-id'     => 3048,
			'contact-id'    => 3044,
			'uri-id'        => 20044,
			'parent-uri-id' => 20044,
			'thr-parent-id' => 20044,
			'guid'          => '123456',
			'from-name'     => 'Tester',
			'title'         => 'item_title',
			'body'          => '[b]item_body[/b]',
			'parent-uri'    => 'parent-uri-value',
			'created'       => DateTimeFormat::utcNow(),
		]);
		$mailId = DI::dba()->lastInsertId();

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy'))
			->withParsedBody([
				'id'                => $mailId,
				'friendica_verbose' => 'true',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('ok', $json->result);
		self::assertEquals('message deleted', $json->message);

		self::assertFalse(DI::dba()->exists('mail', ['id' => $mailId]));
	}

	public function testApiDirectMessagesDestroyWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/destroy'))
			->withParsedBody([
				'id' => 1,
			]);

		$module = $this->createModule();

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(array $parameters = []): Destroy
	{
		return new Destroy(
			DI::dba(),
			DI::mstdnError(),
			DI::appHelper(),
			DI::l10n(),
			DI::baseUrl(),
			DI::args(),
			DI::logger(),
			DI::profiler(),
			DI::apiResponse(),
			[],
			$parameters,
		);
	}
}

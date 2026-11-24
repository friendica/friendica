<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\NewDM;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\ServerRequest;

final class NewDMTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	public function testApiDirectMessagesNew(): void
	{
		$request = new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new');

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEmpty((string) $response->getBody());
	}

	public function testApiDirectMessagesNewWithUserId(): void
	{
		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3043,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals(-1, $json->error);
	}

	public function testApiDirectMessagesNewWithScreenName(): void
	{
		DI::session()->set('nickname', 'selfcontact');

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3044,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStringContainsString('message_text', $json->text);
		self::assertEquals('selfcontact', $json->sender_screen_name);
		self::assertEquals(1, $json->friendica_seen);
	}

	public function testApiDirectMessagesNewWithTitle(): void
	{
		DI::session()->set('nickname', 'selfcontact');

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3044,
				'title'   => 'message_title',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStringContainsString('message_text', $json->text);
		self::assertStringContainsString('message_title', $json->text);
		self::assertEquals('selfcontact', $json->sender_screen_name);
		self::assertEquals(1, $json->friendica_seen);
	}

	public function testApiDirectMessagesNewWithRss(): void
	{
		DI::session()->set('nickname', 'selfcontact');

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3044,
				'title'   => 'message_title',
			]);

		$module = $this->createModule([
			'extension' => ICanCreateResponses::TYPE_RSS,
		]);

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'direct-messages');
	}

	public function testApiDirectMessagesNewWithReplyTo(): void
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
		$parentMailId = DI::dba()->lastInsertId();

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3044,
				'replyto' => $parentMailId,
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertStringContainsString('item_title', $json->text);
		self::assertStringContainsString('message_text', $json->text);
		self::assertEquals('selfcontact', $json->sender_screen_name);
	}

	public function testApiDirectMessagesNewWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$request = (new ServerRequest('POST', 'https://friendica.local/api/direct_messages/new'))
			->withParsedBody([
				'text'    => 'message_text',
				'user_id' => 3044,
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

	private function createModule(array $parameters = []): NewDM
	{
		return new NewDM(
			new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser()),
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

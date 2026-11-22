<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Twitter\DirectMessages;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Sent;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use Friendica\Util\DateTimeFormat;
use GuzzleHttp\Psr7\ServerRequest;

final class SentTest extends ApiTestCase
{
	public function testApiDirectMessagesBox(): void
	{
		$request = (new ServerRequest('GET', 'https://friendica.local/api/direct_messages/sent'))
			->withQueryParams([
				'page'   => '1',
				'count'  => '20',
				'max_id' => '10',
			]);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertIsArray($json);
		self::assertEmpty($json);
	}

	public function testApiDirectMessagesBoxWithVerbose(): void
	{
		$request = (new ServerRequest('GET', 'https://friendica.local/api/direct_messages/sent'))
			->withQueryParams(['friendica_verbose' => 'true']);

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('no mails available', $json->message);
	}

	public function testApiDirectMessagesBoxWithRss(): void
	{
		$module = $this->createModule([
			'extension' => ICanCreateResponses::TYPE_RSS,
		]);

		$request = new ServerRequest('GET', 'https://friendica.local/api/direct_messages/sent.rss');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());
		self::assertEquals(ICanCreateResponses::TYPE_RSS, $response->getHeaderLine(ICanCreateResponses::X_HEADER));

		self::assertXml((string) $response->getBody(), 'direct-messages');
	}

	public function testApiDirectMessagesBoxWithSentMail(): void
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
			'created'       => DateTimeFormat::utcNow(),
		]);

		$request = new ServerRequest('GET', 'https://friendica.local/api/direct_messages/sent');

		$response = $this->createModule()->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

		$json = $this->toJson($response);

		self::assertCount(1, $json);
		self::assertEquals('item_title' . "\n" . 'item_body', $json[0]->text);
		self::assertEquals('selfcontact', $json[0]->sender_screen_name);
		self::assertEquals('friendcontact', $json[0]->recipient_screen_name);
	}

	public function testApiDirectMessagesBoxWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/direct_messages/sent');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());

			$json = $this->toJson($e->getResponse());

			self::assertObjectHasProperty('error', $json);
		}
	}

	private function createModule(array $parameters = []): Sent
	{
		return new Sent(
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

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Conversations;
use Friendica\Test\ApiTestCase;
use Friendica\Test\Util\AuthTestConfig;
use GuzzleHttp\Psr7\ServerRequest;

final class ConversationsTest extends ApiTestCase
{
	public function testApiConversationShow(): void
	{
		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/conversations');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$conversations = $this->toJson($e->getResponse());

			self::assertSame([], $conversations);
		}
	}

	public function testApiConversationShowWithId(): void
	{
		$this->insertMail(1);
		$this->insertMail(2);

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/conversations');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$conversations = $this->toJson($e->getResponse());

			self::assertCount(2, $conversations);
			self::assertEquals(['2', '1'], array_column($conversations, 'id'));
			self::assertFalse($conversations[0]->unread);
		}
	}

	public function testApiConversationShowWithMaxId(): void
	{
		$this->insertMail(1);
		$this->insertMail(2);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/conversations'))
			->withQueryParams(['max_id' => '2']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$conversations = $this->toJson($e->getResponse());

			self::assertCount(1, $conversations);
			self::assertEquals(['1'], array_column($conversations, 'id'));
		}
	}

	public function testApiConversationShowWithMinId(): void
	{
		$this->insertMail(1);
		$this->insertMail(2);

		$module = $this->createModule();

		$request = (new ServerRequest('GET', 'https://friendica.local/api/v1/conversations'))
			->withQueryParams(['min_id' => '1']);

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$conversations = $this->toJson($e->getResponse());

			self::assertCount(1, $conversations);
			self::assertEquals(['2'], array_column($conversations, 'id'));
		}
	}

	public function testApiConversationShowWithUnallowedUser(): void
	{
		AuthTestConfig::$authenticated = false;

		$module = $this->createModule();

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/conversations');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			self::assertEquals(401, $e->getResponse()->getStatusCode());
		}
	}

	/**
	 * Inserts a single message that forms a conversation on its own, since conversations are grouped by the parent URI.
	 */
	private function insertMail(int $id): void
	{
		DI::dba()->insert('mail', [
			'id'            => $id,
			'uid'           => 42,
			'author-id'     => 48,
			'contact-id'    => 44,
			'uri-id'        => 44,
			'parent-uri'    => 'conversation-' . $id,
			'parent-uri-id' => 44,
			'thr-parent-id' => 44,
			'guid'          => 'mail-' . $id,
			'from-name'     => 'Tester',
			'from-url'      => 'https://friendica.local/profile/friendcontact',
			'title'         => 'Conversation ' . $id,
			'body'          => 'Message ' . $id,
			'seen'          => true,
			'created'       => '2020-01-01 12:00:00',
		]);
	}

	private function createModule(): Conversations
	{
		return new Conversations(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);
	}
}

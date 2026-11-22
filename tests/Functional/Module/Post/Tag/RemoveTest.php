<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Module\Post\Tag;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Module\Post\Tag\Remove;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\FoundException;
use Friendica\Test\FixtureTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

final class RemoveTest extends FixtureTestCase
{
	private const UID               = 1042;
	private const PUBLIC_CONTACT_ID = 3048;
	private const POST_ID           = 1501;
	private const URI_ID            = 20001;
	private const TAG               = 'removetest';

	protected function setUp(): void
	{
		parent::setUp();

		Tag::store(self::URI_ID, Tag::HASHTAG, self::TAG);
	}

	protected function tearDown(): void
	{
		unset($_REQUEST['form_security_token']);

		parent::tearDown();
	}

	public function testAuthorCanRemoveTag(): void
	{
		DBA::update('post-user', ['author-id' => self::PUBLIC_CONTACT_ID, 'origin' => true], ['id' => self::POST_ID]);

		$this->removeTag();

		self::assertNotContains(self::TAG, $this->tagNames());
	}

	public function testTagOfPostBySomeoneElseIsKept(): void
	{
		// Fixture post 1502 is in the timeline of uid 1042 but authored by contact 3043.
		$this->removeTag();

		self::assertContains(self::TAG, $this->tagNames());
	}

	private function removeTag(): void
	{
		$tagText = Tag::getCSVByURIId(self::URI_ID, [Tag::HASHTAG]);
		self::assertNotSame('', $tagText);

		$_REQUEST['form_security_token'] = BaseModule::getFormSecurityToken('action');

		$serverRequest = $this->createStub(ServerRequestInterface::class);
		$serverRequest->method('getQueryParams')->willReturn(['tag' => [bin2hex($tagText) => 1]]);

		try {
			$this->module()->handleRequest($serverRequest);
			self::fail('Expected a redirect');
		} catch (FoundException) {
			// expected
		}
	}

	/**
	 * @return string[]
	 */
	private function tagNames(): array
	{
		return array_column(Tag::getByURIId(self::URI_ID, [Tag::HASHTAG]), 'name');
	}

	private function module(): Remove
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('getLocalUserId')->willReturn(self::UID);
		$session->method('getPublicContactId')->willReturn(self::PUBLIC_CONTACT_ID);

		$baseUrl = $this->createStub(BaseURL::class);
		$baseUrl->method('redirect')->willThrowException(new FoundException());

		$args = $this->createStub(Arguments::class);
		$args->method('getMethod')->willReturn('POST');
		$args->method('getQueryString')->willReturn('post/' . self::POST_ID . '/tag/remove');

		return new Remove($session, DI::l10n(), $baseUrl, $args, new NullLogger(), DI::profiler(), new Response(), [], ['item_id' => (string) self::POST_ID]);
	}
}

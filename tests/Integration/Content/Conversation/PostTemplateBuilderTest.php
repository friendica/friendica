<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Content\Conversation;

use Friendica\Content\Conversation\PostTemplateBuilder;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Test\FixtureTestCase;

final class PostTemplateBuilderTest extends FixtureTestCase
{
	private const USER_ID = 42; // Local user from api.fixture.php.

	public function testItemActionLinksCarryAValidToken(): void
	{
		DI::userSession()->set('authenticated', true);
		DI::userSession()->set('uid', self::USER_ID);

		$item = Post::selectFirstForUser(self::USER_ID, [], ['id' => 1]);
		$data = DI::postTemplateBuilder()->renderFlatItem($item, false, true, self::USER_ID, [], '');

		$token = $data['drop']['token'];
		self::assertTrue(DI::formSecurityToken()->isValid($token, PostTemplateBuilder::ACTION_TOKEN_TYPE));
		self::assertSame($token, $data['block']['token']);
		self::assertSame($token, $data['ignore_author']['token']);
		self::assertSame($token, $data['collapse']['token']);
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Functional\Post;

use Friendica\DI;
use Friendica\Test\FixtureTestCase;

class LinkResolverTest extends FixtureTestCase
{
	// post-user 1 of user 42 in api.fixture.php: uri "1", plink "https://friendica.local/display/1"
	private const UID = 42;

	public function testSearchByLinkFindsPostByUri(): void
	{
		self::assertSame(1, DI::postLinkResolver()->searchByLink('1', self::UID));
	}

	public function testSearchByLinkFindsPostByPlinkAlsoViaHttp(): void
	{
		self::assertSame(1, DI::postLinkResolver()->searchByLink('https://friendica.local/display/1', self::UID));
		self::assertSame(1, DI::postLinkResolver()->searchByLink('http://friendica.local/display/1', self::UID));
	}

	public function testSearchByLinkIgnoresOtherUsers(): void
	{
		self::assertSame(0, DI::postLinkResolver()->searchByLink('1', 4711));
	}

	public function testGetURIByLinkReturnsUriForPlink(): void
	{
		self::assertSame('1', DI::postLinkResolver()->getURIByLink('https://friendica.local/display/1'));
		self::assertSame('', DI::postLinkResolver()->getURIByLink('https://friendica.local/display/unknown'));
	}
}

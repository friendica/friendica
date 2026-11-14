<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Conversation;

use Friendica\Content\Conversation\PostTemplateBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for #15913: replying to a post must only pre-fill the "[abstract=apub]" content
 * warning when the post being replied to is actually marked "sensitive", not merely because it has
 * a summary.
 */
class PostTemplateBuilderTest extends TestCase
{
	public function testPlainPostAddsNoAbstract(): void
	{
		self::assertSame('', PostTemplateBuilder::getAbstractText('', false));
	}

	public function testContentWarningWithoutSensitiveAddsNoAbstract(): void
	{
		self::assertSame('', PostTemplateBuilder::getAbstractText('Spoiler', false));
	}

	public function testSensitiveWithoutContentWarningAddsNoAbstract(): void
	{
		self::assertSame('', PostTemplateBuilder::getAbstractText('', true));
	}

	public function testSensitiveContentWarningAddsAbstract(): void
	{
		self::assertSame("[abstract=apub]Spoiler[/abstract]\n", PostTemplateBuilder::getAbstractText('Spoiler', true));
	}
}

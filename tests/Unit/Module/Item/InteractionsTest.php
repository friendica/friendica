<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Module\Item;

use Friendica\App;
use Friendica\Content\Conversation\ConversationRenderer;
use Friendica\Content\Conversation\PostInteractions;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Item\Interactions;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Runs without a database: only requests that are rejected before the post is looked up.
 */
class InteractionsTest extends TestCase
{
	private function createModule(IHandleUserSessions $session, array $parameters, bool $blockPublic = false): Interactions
	{
		$l10n = $this->createStub(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		$config = $this->createStub(IManageConfigValues::class);
		$config->method('get')->willReturn($blockPublic);

		$pConfig = $this->createStub(IManagePersonalConfigValues::class);
		$pConfig->method('get')->willReturn(false);

		return new Interactions(
			$config,
			$session,
			$this->createStub(App\Page::class),
			new PostInteractions($l10n, $pConfig),
			(new ReflectionClass(ConversationRenderer::class))->newInstanceWithoutConstructor(),
			$this->createStub(EventDispatcherInterface::class),
			$l10n,
			$this->createStub(App\BaseURL::class),
			$this->createStub(App\Arguments::class),
			$this->createStub(LoggerInterface::class),
			$this->createStub(Profiler::class),
			$this->createStub(Response::class),
			[],
			$parameters,
		);
	}

	private function createAnonymousSession(): IHandleUserSessions
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('isAuthenticated')->willReturn(false);
		// The real session returns false (not 0) for visitors that aren't logged in
		$session->method('getLocalUserId')->willReturn(false);

		return $session;
	}

	public function testAnonymousVisitorGetsNotFoundForUnknownType(): void
	{
		$module = $this->createModule($this->createAnonymousSession(), ['guid' => 'abc', 'type' => 'unknown']);

		$this->expectException(HTTPException\NotFoundException::class);

		(new ReflectionMethod($module, 'content'))->invoke($module);
	}

	public function testBlockedPublicAccessIsRejectedForAnonymousVisitor(): void
	{
		$module = $this->createModule($this->createAnonymousSession(), ['guid' => 'abc', 'type' => PostInteractions::LIKES], true);

		$this->expectException(HTTPException\UnauthorizedException::class);

		(new ReflectionMethod($module, 'content'))->invoke($module);
	}
}

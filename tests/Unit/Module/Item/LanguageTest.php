<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Module\Item;

use Friendica\App;
use Friendica\Content\Item as ContentItem;
use Friendica\Core\EarlyExitException;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\Item\Language;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Runs without a database: the detection of a text that is still being composed.
 */
class LanguageTest extends TestCase
{
	private function createModule(ContentItem $item, bool $authenticated): Language
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('isAuthenticated')->willReturn($authenticated);

		$l10n = $this->createStub(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		return new Language(
			$item,
			$session,
			$this->createStub(EventDispatcherInterface::class),
			$l10n,
			$this->createStub(App\BaseURL::class),
			$this->createStub(App\Arguments::class),
			$this->createStub(LoggerInterface::class),
			$this->createStub(Profiler::class),
			(new ReflectionClass(ApiResponse::class))->newInstanceWithoutConstructor(),
			[],
		);
	}

	private function detect(ContentItem $item, string $body): array
	{
		$module = $this->createModule($item, true);

		try {
			(new ReflectionMethod($module, 'rawContent'))->invoke($module, ['body' => $body]);
		} catch (EarlyExitException $e) {
			self::assertSame(200, $e->getResponse()->getStatusCode());

			return json_decode((string) $e->getResponse()->getBody(), true);
		}

		self::fail('Expected EarlyExitException');
	}

	public function testDetectsLanguageOfBody(): void
	{
		$item = $this->createMock(ContentItem::class);
		$item->expects(self::once())->method('getLanguageArray')->with('Das ist ein deutscher Text', 1)->willReturn(['de' => 0.9]);

		self::assertSame(['lang' => 'de'], $this->detect($item, 'Das ist ein deutscher Text'));
	}

	public function testUndeterminedLanguageIsReturnedAsIs(): void
	{
		$item = $this->createStub(ContentItem::class);
		$item->method('getLanguageArray')->willReturn(['un' => 1]);

		self::assertSame(['lang' => 'un'], $this->detect($item, '???'));
	}

	public function testRequiresAuthentication(): void
	{
		$module = $this->createModule($this->createStub(ContentItem::class), false);

		$this->expectException(ForbiddenException::class);

		(new ReflectionMethod($module, 'rawContent'))->invoke($module, ['body' => 'Text']);
	}
}

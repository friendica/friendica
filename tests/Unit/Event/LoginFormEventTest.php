<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\LoginFormEvent;
use PHPUnit\Framework\TestCase;

class LoginFormEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new LoginFormEvent('<form></form>');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new LoginFormEvent('<form></form>');

		$this->assertSame(LoginFormEvent::NAME, $event->getName());
	}

	public function testGetHtmlReturnsHtml(): void
	{
		$event = new LoginFormEvent('<form></form>');

		$this->assertSame('<form></form>', $event->getHtml());
	}

	public function testSetHtmlUpdatesHtml(): void
	{
		$event = new LoginFormEvent('<form></form>');

		$event->setHtml('<div>changed</div>');

		$this->assertSame('<div>changed</div>', $event->getHtml());
	}
}

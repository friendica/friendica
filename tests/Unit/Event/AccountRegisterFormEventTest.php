<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\AccountRegisterFormEvent;
use PHPUnit\Framework\TestCase;

class AccountRegisterFormEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new AccountRegisterFormEvent('<form>{{$notices}}</form>');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new AccountRegisterFormEvent('<form>{{$notices}}</form>');

		$this->assertSame(AccountRegisterFormEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$markup = '<form>{{$notices}}</form>';
		$event = new AccountRegisterFormEvent($markup);

		$this->assertSame($markup, $event->getMarkupTemplate());

		$custom = '<div>Custom {{$regtitle}}</div>';
		$event->setMarkupTemplate($custom);
		$this->assertSame($custom, $event->getMarkupTemplate());
	}
}

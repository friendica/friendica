<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\TemplateVarsEvent;
use PHPUnit\Framework\TestCase;

class TemplateVarsEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new TemplateVarsEvent('test.tpl', []);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new TemplateVarsEvent('test.tpl', []);

		$this->assertSame(TemplateVarsEvent::NAME, $event->getName());
	}

	public function testGetTemplate(): void
	{
		$event = new TemplateVarsEvent('test.tpl', []);

		$this->assertSame('test.tpl', $event->getTemplate());
	}

	public function testGetVars(): void
	{
		$event = new TemplateVarsEvent('test.tpl', ['foo' => 'bar']);

		$this->assertSame(['foo' => 'bar'], $event->getVars());
	}

	public function testSetVars(): void
	{
		$event = new TemplateVarsEvent('test.tpl', []);

		$event->setVars(['foo' => 'baz']);

		$this->assertSame(['foo' => 'baz'], $event->getVars());
	}
}

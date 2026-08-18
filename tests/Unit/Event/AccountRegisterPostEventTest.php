<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\AccountRegisterPostEvent;
use PHPUnit\Framework\TestCase;

class AccountRegisterPostEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new AccountRegisterPostEvent(['username' => 'test']);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new AccountRegisterPostEvent(['username' => 'test']);

		$this->assertSame(AccountRegisterPostEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$post  = ['username' => 'test', 'email' => 'test@example.com'];
		$event = new AccountRegisterPostEvent($post);

		$this->assertSame($post, $event->getPostArray());

		$newPost = ['username' => 'changed'];
		$event->setPostArray($newPost);
		$this->assertSame($newPost, $event->getPostArray());
	}
}

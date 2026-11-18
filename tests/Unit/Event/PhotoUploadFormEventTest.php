<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\PhotoUploadFormEvent;
use PHPUnit\Framework\TestCase;

class PhotoUploadFormEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new PhotoUploadFormEvent(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PhotoUploadFormEvent(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true]);

		$this->assertSame(PhotoUploadFormEvent::NAME, $event->getName());
	}

	public function testGetSetFormArray(): void
	{
		$event = new PhotoUploadFormEvent(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true]);

		$this->assertSame(['post_url' => '/photos', 'addon_text' => '', 'default_upload' => true], $event->getFormArray());

		$event->setFormArray(['post_url' => '/photos', 'addon_text' => 'text', 'default_upload' => false]);

		$this->assertSame(['post_url' => '/photos', 'addon_text' => 'text', 'default_upload' => false], $event->getFormArray());
	}
}

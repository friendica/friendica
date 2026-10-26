<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Object;

use Friendica\Object\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
	private function createEmail(): Email
	{
		return new Email('From', 'from@example.com', 'reply@example.com', 'to@example.com', 'Subject', '<p>html</p>', 'text');
	}

	public function testWithMessageWithoutHtmlCreatesTextOnlyMail(): void
	{
		$email = $this->createEmail()->withMessage('encrypted');

		self::assertSame('encrypted', $email->getMessage(true));
		self::assertSame('', $email->getMessage());
	}

	public function testWithMessageKeepsOriginalUntouched(): void
	{
		$original = $this->createEmail();
		$original->withMessage('encrypted', '<p>new</p>');

		self::assertSame('text', $original->getMessage(true));
		self::assertSame('<p>html</p>', $original->getMessage());
	}
}

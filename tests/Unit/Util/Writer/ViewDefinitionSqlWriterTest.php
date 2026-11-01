<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Util\Writer;

use Friendica\Util\Writer\ViewDefinitionSqlWriter;
use PHPUnit\Framework\TestCase;

class ViewDefinitionSqlWriterTest extends TestCase
{
	private const STRUCTURE = [
		'fields' => ['id' => ['item', 'id']],
		'query'  => 'FROM `item`',
	];

	public function testCreateViewCreatesByDefault(): void
	{
		self::assertStringStartsWith('CREATE VIEW `test-view` AS SELECT', ViewDefinitionSqlWriter::createView('test-view', self::STRUCTURE));
	}

	public function testCreateViewReplacesInPlace(): void
	{
		self::assertStringStartsWith('CREATE OR REPLACE VIEW `test-view` AS SELECT', ViewDefinitionSqlWriter::createView('test-view', self::STRUCTURE, true));
	}
}

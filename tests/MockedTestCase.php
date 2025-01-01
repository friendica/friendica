<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use PHPUnit\Framework\TestCase;

/**
 * This class verifies each mock after each call
 */
abstract class MockedTestCase extends TestCase
{
	protected function tearDown(): void
	{
		\Mockery::close();

		parent::tearDown();
	}
}

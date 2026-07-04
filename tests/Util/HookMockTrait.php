<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Core\Hook;
use Mockery\MockInterface;

trait HookMockTrait
{
	/**
	 * @var ?MockInterface The Interface for mocking a renderer
	 */
	private $hookMock;

	/**
	 * Mocking a method 'Hook::call()' call
	 *
	 * @param string $name
	 * @param mixed  $capture
	 */
	public function mockHookCallAll(string $name, &$capture)
	{
		if (!isset($this->hookMock)) {
			$this->hookMock = \Mockery::mock('alias:' . Hook::class);
		}

		$this->hookMock
			->shouldReceive('callAll')
			->withArgs([$name, \Mockery::capture($capture)]);
	}
}

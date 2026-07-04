<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Friendica\Core\Storage\Capability\ICanConfigureStorage;

abstract class StorageConfigTestCase extends MockedTestCase
{
	/** @return ICanConfigureStorage */
	abstract protected function getInstance();

	abstract protected function assertOption(ICanConfigureStorage $storage);

	/**
	 * Test if the "getOption" is asserted
	 */
	public function testGetOptions(): void
	{
		$instance = $this->getInstance();

		$this->assertOption($instance);
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\Core\Lock\Type\DatabaseLock;
use Friendica\Test\LockTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;

class DatabaseLockDriverTest extends LockTestCase
{
	use CreateDatabaseTrait;

	protected $pid = 123;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		$this->setUpDb();

		parent::setUp();
	}

	protected function getInstance(): ICanLock
	{
		return new DatabaseLock($this->getDbInstance(), $this->pid);
	}

	protected function tearDown(): void
	{
		parent::tearDown();

		$this->tearDownDb();
	}
}

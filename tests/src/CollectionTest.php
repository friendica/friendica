<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src;

use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\CollectionDouble;
use Friendica\Test\Util\EntityDouble;

class CollectionTest extends MockedTestCase
{
	/**
	 * Test if the BaseCollection::column() works as expected
	 */
	public function testGetArrayCopy(): void
	{
		$collection = new CollectionDouble();
		$collection->append(new EntityDouble('test', 23, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest'));
		$collection->append(new EntityDouble('test2', 25, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest23'));

		self::assertEquals(['test', 'test2'], $collection->column('protString'));
		self::assertEmpty($collection->column('privString'));
		self::assertEquals([23,25], $collection->column('protInt'));
	}
}

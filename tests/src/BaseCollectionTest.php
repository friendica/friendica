<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src;

use Friendica\BaseCollection;
use Friendica\BaseEntity;
use PHPUnit\Framework\TestCase;

class BaseCollectionTest extends TestCase
{
	public function testChunk(): void
	{
		$entity1 = \Mockery::mock(BaseEntity::class);
		$entity2 = \Mockery::mock(BaseEntity::class);
		$entity3 = \Mockery::mock(BaseEntity::class);
		$entity4 = \Mockery::mock(BaseEntity::class);

		$collection = new BaseCollection([$entity1, $entity2]);

		$this->assertEquals([new BaseCollection([$entity1]), new BaseCollection([$entity2])], $collection->chunk(1));
		$this->assertEquals([new BaseCollection([$entity1, $entity2])], $collection->chunk(2));

		$collection = new BaseCollection([$entity1, $entity2, $entity3]);

		$this->assertEquals([new BaseCollection([$entity1]), new BaseCollection([$entity2]), new BaseCollection([$entity3])], $collection->chunk(1));
		$this->assertEquals([new BaseCollection([$entity1, $entity2]), new BaseCollection([$entity3])], $collection->chunk(2));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3])], $collection->chunk(3));

		$collection = new BaseCollection([$entity1, $entity2, $entity3, $entity4]);

		$this->assertEquals([new BaseCollection([$entity1, $entity2]), new BaseCollection([$entity3, $entity4])], $collection->chunk(2));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3]), new BaseCollection([$entity4])], $collection->chunk(3));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3, $entity4])], $collection->chunk(4));
	}

	public function testChunkLengthException(): void
	{
		$this->expectException(\RangeException::class);

		$entity1 = \Mockery::mock(BaseEntity::class);

		$collection = new BaseCollection([$entity1]);

		$collection->chunk(0);
	}
}

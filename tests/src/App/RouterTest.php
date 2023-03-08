<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\App;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	/** @var L10n|MockInterface */
	private $l10n;
	/**
	 * @var ICanCache
	 */
	private $cache;
	/**
	 * @var ICanLock
	 */
	private $lock;
	/**
	 * @var IManageConfigValues
	 */
	private $config;
	/**
	 * @var Dice
	 */
	private $dice;
	/**
	 * @var Arguments
	 */
	private $arguments;

	protected function setUp(): void
	{
		parent::setUp();

		self::markTestIncomplete('Router tests need refactoring!');

		/*
		$this->l10n = Mockery::mock(L10n::class);
		$this->l10n->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->cache = Mockery::mock(ICanCache::class);
		$this->cache->shouldReceive('get')->andReturn(null);
		$this->cache->shouldReceive('set')->andReturn(false);

		$this->lock = Mockery::mock(ICanLock::class);
		$this->lock->shouldReceive('acquire')->andReturn(true);
		$this->lock->shouldReceive('isLocked')->andReturn(false);

		$this->config = Mockery::mock(IManageConfigValues::class);

		$this->dice = new Dice();

		$this->arguments = Mockery::mock(Arguments::class);
		*/
	}

	public function test()
	{

	}
}

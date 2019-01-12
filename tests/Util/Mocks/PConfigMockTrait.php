<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

/**
 * Trait to Mock PConfig settings
 */
trait PConfigMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\PConfig
	 */
	private $pconfigMock;

	/**
	 * Mocking a config setting
	 *
	 * @param string $uid The user ID of the config double
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 */
	public function mockPConfigGet($uid, $family, $key, $value, $times = null)
	{
		if (!isset($this->pconfigMock)) {
			$this->pconfigMock = \Mockery::mock('alias:Friendica\Core\PConfig');
		}

		$this->pconfigMock
			->shouldReceive('get')
			->times($times)
			->with($uid, $family, $key)
			->andReturn($value);
	}

	/**
	 * Mocking setting a new config entry and expect at least getting it once
	 *
	 * @param string $uid The user ID of the config double
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 * @param bool $return Return value of the set (default is true)
	 */
	public function mockPConfigSetGet($uid, $family, $key, $value, $times = null, $return = true)
	{
		if (!isset($this->pconfigMock)) {
			$this->pconfigMock = \Mockery::mock('alias:Friendica\Core\PConfig');
		}

		$this->mockPConfigGet($family, $key, false, 1);
		if ($return) {
			$this->mockPConfigGet($family, $key, $value, 1);
		}

		$this->pconfigMock
			->shouldReceive('set')
			->times($times)
			->with($uid, $family, $key, $value)
			->andReturn($return);
	}

	/**
	 * Mocking setting a new config entry
	 *
	 * @param string $uid The user ID of the config double
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 * @param bool $return Return value of the set (default is true)
	 */
	public function mockPConfigSet($uid, $family, $key, $value, $times = null, $return = true)
	{
		if (!isset($this->pconfigMock)) {
			$this->pconfigMock = \Mockery::mock('alias:Friendica\Core\PConfig');
		}

		$this->pconfigMock
			->shouldReceive('set')
			->times($times)
			->with($uid, $family, $key, $value)
			->andReturn($return);
	}
}

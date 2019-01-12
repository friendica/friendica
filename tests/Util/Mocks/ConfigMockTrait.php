<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

/**
 * Trait to Mock Config settings
 */
trait ConfigMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Config
	 */
	private $configMock;

	/**
	 * Mocking a config setting
	 *
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 */
	public function mockConfigGet($family, $key, $value, $times = null)
	{
		if (!isset($this->configMock)) {
			$this->configMock = \Mockery::mock('alias:Friendica\Core\Config');
		}

		$this->configMock
			->shouldReceive('get')
			->times($times)
			->with($family, $key)
			->andReturn($value);
	}

	/**
	 * Mocking setting a new config entry and expect at least getting it once
	 *
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 * @param bool $return Return value of the set (default is true)
	 */
	public function mockConfigSetGet($family, $key, $value, $times = null, $return = true)
	{
		if (!isset($this->configMock)) {
			$this->configMock = \Mockery::mock('alias:Friendica\Core\Config');
		}

		$this->mockConfigGet($family, $key, false, 1);
		if ($return) {
			$this->mockConfigGet($family, $key, $value, 1);
		}

		$this->configMock
			->shouldReceive('set')
			->times($times)
			->with($family, $key, $value)
			->andReturn($return);
	}

	/**
	 * Mocking setting a new config entry
	 *
	 * @param string $family The family of the config double
	 * @param string $key The key of the config double
	 * @param mixed $value The value of the config double
	 * @param null|int $times How often the Config will get used
	 * @param bool $return Return value of the set (default is true)
	 */
	public function mockConfigSet($family, $key, $value, $times = null, $return = true)
	{
		if (!isset($this->configMock)) {
			$this->configMock = \Mockery::mock('alias:Friendica\Core\Config');
		}

		$this->configMock
			->shouldReceive('set')
			->times($times)
			->with($family, $key, $value)
			->andReturn($return);
	}
}

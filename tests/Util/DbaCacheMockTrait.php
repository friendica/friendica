<?php

namespace Friendica\Test\Util;

use Friendica\Core\Cache\Cache;

trait DbaCacheMockTrait
{
	/**
	 * @var
	 */
	protected $dba;

	public function __construct()
	{
	}

	protected function mockDelete($key, $return = true, $times = null)
	{
		$this->mockDBADelete('cache', ['k' => $key], $return, $times);
	}

	protected function mockGet($key, $return = null, $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		$value = @serialize($return);

		$this->mockSelectFirst('cache', ['v'], ['`k` = ? AND (`expires` >= ? OR `expires` = -1)', $key, $time], ['v' => $value], $times);
		$this->mockIsResult(['v' => $value], isset($return), $times);
	}

	protected function mockSet($key, $value, $ttl = Cache::FIVE_MINUTES, $time = null, $return = true, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		if ($ttl > 0) {
			$this->mockUtc('now + ' . $ttl . 'seconds', $time + $ttl, $times);
			$fields = [
				'v' => serialize($value),
				'expires' => $time + $ttl,
				'updated' => $time
			];
		} else {
			$fields = [
				'v' => serialize($value),
				'expires' => -1,
				'updated' => $time
			];
		}

		$this->mockDBAUpdate('cache', $fields, ['k' => $key], true, $return, $times);
	}

	protected function mockClear($outdated = true, $return = true, $times = null)
	{
		if ($outdated) {
			$this->mockDBADelete('cache', ['`expires` < NOW()'], $return, $times);
		} else {
			$this->mockDBADelete('cache', ['`k` IS NOT NULL '], $return, $times);
		}
	}

	protected function mockGetAllKeys($prefix = null, $return = [], $time = null, $times = null)
	{
		if ($time === null) {
			$time = time();
		}

		if (empty($prefix)) {
			$where = ['`expires` >= ?', $time];
		} else {
			$where = ['`expires` >= ? AND `k` LIKE CONCAT(?, \'%\')', $time, $prefix];
		}

		$this->mockSelect('cache', ['k'], $where, $return, $times);
		$this->mockFetchLoop($return, $times);
		$this->mockDbaClose(true, $times);
	}
}

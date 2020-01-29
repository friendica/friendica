<?php

namespace Friendica\Core\Lock;

use Friendica\Core\BaseLock;
use Friendica\Core\Cache\Duration;
use Psr\Log\LoggerInterface;

class SemaphoreLock extends BaseLock
{
	private static $semaphore = [];

	/** @var LoggerInterface */
	private $logger;

	/**
	 * SemaphoreLock constructor.
	 *
	 * @param LoggerInterface $logger
	 *
	 * @throws \Exception
	 */
	public function __construct(LoggerInterface $logger)
	{
		if (!function_exists('sem_get')) {
			throw new \Exception('Semaphore lock not supported');
		}

		$this->logger = $logger;
	}

	/**
	 * (@inheritdoc)
	 */
	private static function semaphoreKey($key)
	{
		$success = true;

		$temp = get_temppath();

		$file = $temp . '/' . $key . '.sem';

		if (!file_exists($file)) {
			$success = !empty(file_put_contents($file, $key));
		}

		return $success ? ftok($file, 'f') : false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire($key, $timeout = 120, $ttl = Duration::FIVE_MINUTES)
	{
		try {
			self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
			if (!empty(self::$semaphore[$key])) {
				if ((bool)sem_acquire(self::$semaphore[$key], ($timeout === 0))) {
					$this->markAcquire($key);
					return true;
				} else {
					$this->logger->notice('Couldnt\'t acquire semaphore lock.', ['key' => $key]);
				}
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Couldn\'t acquire semaphore lock.', ['key' => $key, 'exception' => $e]);
		}

		return false;
	}

	/**
	 * (@inheritdoc)
	 *
	 * @param bool $override not necessary parameter for semaphore locks since the lock lives as long as the execution
	 *                       of the using function
	 */
	public function release($key, $override = false)
	{
		$success = false;

		if (!empty(self::$semaphore[$key])) {
			try {
				$success = sem_release(self::$semaphore[$key]);
				unset(self::$semaphore[$key]);
				$this->markRelease($key);
			} catch (\Throwable $e) {
				$this->logger->warning('Couldn\'t release semaphore lock.', ['key' => $key, 'exception' => $e]);
			}
		}

		return $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		return isset(self::$semaphore[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::SEMAPHORE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = '')
	{
		// We can just return our own semaphore keys, since we don't know
		// the state of other semaphores, even if the .sem files exists
		$keys = array_keys(self::$semaphore);

		if (empty($prefix)) {
			return $keys;
		} else {
			$result = [];

			foreach ($keys as $key) {
				if (strpos($key, $prefix) === 0) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll($override = false)
	{
		// Semaphores are just alive during a run, so there is no need to release
		// You can just release your own locks
		return parent::releaseAll($override);
	}
}

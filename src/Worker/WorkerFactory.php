<?php

namespace Friendica\Worker;

use Friendica\App;
use Psr\Log\LoggerInterface;

/**
 * Creating a Worker instance
 * @see AbstractWorker
 */
class WorkerFactory
{
	/**
	 * @param string $workerName
	 * @param LoggerInterface $logger
	 * @return AbstractWorker#
	 */
	public static function create($workerName, App $app, LoggerInterface $logger)
	{
		$className = sprintf('Friendica\Worker\%s', $workerName);
		return new $className($app, $logger);
	}
}

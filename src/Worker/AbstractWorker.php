<?php

namespace Friendica\Worker;

use Friendica\App;
use Psr\Log\LoggerInterface;

/**
 * Defines a abstract worker
 */
abstract class AbstractWorker
{
	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @param App             $app    The Application instance of this call
	 * @param LoggerInterface $logger The Logger for the current worker
	 */
	public function __construct(App $app, LoggerInterface $logger)
	{
		$this->logger = $logger;
		$this->app    = $app;
	}

	/**
	 * Executes the current worker with given arguments
	 *
	 * @param array $parameters parameter, which are used for execution
	 * @return void
	 */
	public abstract function execute(array $parameters = []);

	/**
	 * Global check for given parameters
	 *
	 * @param array $parameter     The parameters
	 * @param int   $expectedCount The expected cound
	 * @return bool true, if check is successful
	 */
	protected function checkParameters(array $parameter, $expectedCount)
	{
		if (count($parameter) !== $expectedCount) {
			$this->logger->alert('Invoked with wrong parameters', ['count' => $parameter, 'parameter' => $parameter]);
			return false;
		} else {
			return true;
		}
	}
}

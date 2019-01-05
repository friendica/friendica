<?php

namespace Friendica\Util\Logger;

use Psr\Log\LoggerInterface;

/**
 * Extends the FriendicaLoggerInterface for Friendica relevant methods
 */
interface FriendicaLoggerInterface extends LoggerInterface
{
	/**
	 * Gets the channel name of the current logger
	 *
	 * @return string
	 */
	function getChannel();

	/**
	 * Enabling PHP error logging to a given logger
	 *
	 * @return string the channel name
	 */
	function enablePHPError();

	/**
	 * This method enables the test mode of a given logger
	 *
	 * @return mixed the handler for tests
	 */
	function enableTest();

	/**
	 * Adding a handler to a given logger instance
	 *
	 * @param array $handlerConfig The configuration of the handler
	 *
	 * @return void
	 *
	 * @throws \Exception if the handler config is incompatible to the logger
	 */
	function addHandler(array $handlerConfig);
}

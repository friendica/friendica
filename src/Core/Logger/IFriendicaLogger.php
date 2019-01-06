<?php

namespace Friendica\Core\Logger;

use Friendica\Core\Config\IConfigurable;
use Friendica\Core\Logger\Handler\ILogHandler;
use Psr\Log\LoggerInterface;

/**
 * Extends the FriendicaLoggerInterface for Friendica relevant methods
 */
interface IFriendicaLogger extends LoggerInterface, IConfigurable
{
	/**
	 * Gets the channel name of the current logger
	 *
	 * @return string
	 */
	function getChannel();

	/**
	 * This method enables the test mode of a given logger
	 *
	 * @return mixed the handler for tests
	 */
	function enableTest();

	/**
	 * Returns all handler names for this logger instance
	 *
	 * @return array all handler
	 */
	function getHandlerNames();

	/**
	 * Adding a handler to a given logger instance
	 *
	 * @param ILogHandler $handler The log handler
	 *
	 * @return void
	 *
	 * @throws \Exception if the handler config is incompatible to the logger
	 */
	function addHandler(ILogHandler $handler);
}

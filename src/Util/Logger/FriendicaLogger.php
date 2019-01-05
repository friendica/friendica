<?php

namespace Friendica\Util\Logger;

use Friendica\Core\Logger;
use Monolog;
use Psr\Log\LogLevel;

/**
 * A Logger instance of Friendica and extends the Monolog logger
 * Implements an interface for the possibility to switch to other logging-frameworks if necessary
 */
class FriendicaLogger extends Monolog\Logger implements FriendicaLoggerInterface
{
	/**
	 * {@inheritdoc}
	 */
	function getChannel()
	{
		return $this->name;
	}

	/**
	 * {@inheritdoc}
	 */
	function enablePHPError()
	{
		Monolog\ErrorHandler::register($this);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return Monolog\Handler\TestHandler the Handling for tests
	 */
	function enableTest()
	{
		// enable the test handler
		$fileHandler = new Monolog\Handler\TestHandler();
		$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
		$fileHandler->setFormatter($formatter);

		$this->setHandlers(
			[$fileHandler]
		);

		return $fileHandler;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addHandler(array $handlerConfig)
	{
		if (isset($handlerConfig['enabled']) && $handlerConfig['enabled']) {
			if (isset($handlerConfig['type']) && $handlerConfig['type'] === 'stream') {
				$loglevel = (isset($handlerConfig['loglevel'])) ? $handlerConfig['loglevel'] : LogLevel::NOTICE;
				$logfile = (isset($handlerConfig['logfile'])) ? $handlerConfig['logfile'] : 'friendica.log';

				if (is_int($loglevel)) {
					$loglevel = Logger::mapLegacyConfigDebugLevel($loglevel);
				}

				$fileHandler = new Monolog\Handler\StreamHandler($logfile, Monolog\Logger::toMonologLevel($loglevel));

				$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
				$fileHandler->setFormatter($formatter);

				$this->pushHandler($fileHandler);
			}

			if (isset($handlerConfig['errors']) && $handlerConfig['errors']) {
				$this->enablePHPError();
			}
		}
	}
}

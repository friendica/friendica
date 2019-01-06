<?php

namespace Friendica\Core;

use Friendica\Core\Logger\Handler\ILogHandler;
use Friendica\Core\Logger\Handler\MonologStreamHandler;
use Friendica\Core\Logger\Handler\MonologDevelopHandler;
use Friendica\Core\Logger\IFriendicaLogger;
use Friendica\Core\Logger\MonologLogger;
use Monolog;
use Psr\Log\LogLevel;

/**
 * A logger factory
 */
class LoggerFactory
{
	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param string $channel            The channel of the logger instance
	 *
	 * @return IFriendicaLogger  The PSR-3 compliant logger instance
	 */
	public static function create($channel)
	{
		// create the default channel
		$logger = new MonologLogger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
		$logger->pushProcessor(new Monolog\Processor\UidProcessor());

		// Add more information in case of a warning and more
		$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(LogLevel::WARNING, [], 1));

		return $logger;
	}


	/**
	 * Creates a new PSR-3 compliant logger for profiling the app
	 *
	 * @param string $channel      The channel of the logger instance
	 *
	 * @return IFriendicaLogger The PSR-3 compliant logger instance
	 */
	public static function createProf($channel = 'performance')
	{
		$logger = new MonologLogger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
		$logger->pushProcessor(new Monolog\Processor\UidProcessor());

		$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(LogLevel::DEBUG, [], 1));
		$logger->pushProcessor(new Monolog\Processor\MemoryUsageProcessor());

		return $logger;
	}

	/**
	 * Creates a new PSR-3 compliant develop logger
	 *
	 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
	 * you'll use this logger instance for the duration of your work.
	 *
	 * It should never get filled during normal usage of Friendica
	 *
	 * @param string $channel      The channel of the logger instance
	 *
	 * @return IFriendicaLogger The PSR-3 compliant logger instance
	 */
	public static function createDev($channel = 'develop')
	{
		$logger = new MonologLogger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());

		$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(Loglevel::DEBUG, [], 1));

		return $logger;
	}

	/**
	 * Creates a new Log Handler for Friendica
	 *
	 * Depending on the $name of the handler, different Handler are creating
	 *
	 * @param $name
	 * @return ILogHandler
	 * @throws \Exception
	 */
	public static function createHandler($name)
	{
		$type = Config::get('log_handler', sprintf("%s.type", $name), 'stream');

		switch ($type) {
			case 'stream':
				return new MonologStreamHandler($name);
				break;
			case 'develop':
				return new MonologDevelopHandler($name);
				break;
			default:
				return new MonologStreamHandler($name);
				break;
		}
	}
}

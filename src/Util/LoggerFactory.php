<?php

namespace Friendica\Util;

use Friendica\Util\Logger\FriendicaDevelopHandler;
use Friendica\Util\Logger\FriendicaLogger;
use Friendica\Util\Logger\FriendicaLoggerInterface;
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
	 * @return FriendicaLoggerInterface  The PSR-3 compliant logger instance
	 */
	public static function create($channel)
	{
		// create the default channel
		$logger = new FriendicaLogger($channel);
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
	 * @return FriendicaLoggerInterface The PSR-3 compliant logger instance
	 */
	public static function createProf($channel = 'performance')
	{
		$logger = new FriendicaLogger($channel);
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
	 * @param string $developerIp  The IP of the developer who wants to use the logger
	 * @param string $channel      The channel of the logger instance
	 *
	 * @return FriendicaLoggerInterface The PSR-3 compliant logger instance
	 */
	public static function createDev($developerIp, $channel = 'develop')
	{
		$logger = new FriendicaLogger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());

		$logger->pushProcessor(new Monolog\Processor\IntrospectionProcessor(Loglevel::DEBUG, [], 1));

		$logger->pushHandler(new FriendicaDevelopHandler($developerIp));

		return $logger;
	}
}

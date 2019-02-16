<?php

namespace Friendica\Factory;

use Friendica\Core\Config\ConfigCache;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Logger\FriendicaDevelopHandler;
use Friendica\Util\Logger\FriendicaIntrospectionProcessor;
use Monolog;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A logger factory
 *
 * Currently only Monolog is supported
 */
class LoggerFactory
{
	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param string      $channel The channel of the logger instance
	 * @param ConfigCache $config  The config
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public static function create($channel, ConfigCache $config = null)
	{
		$logger = new Monolog\Logger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
		$logger->pushProcessor(new Monolog\Processor\UidProcessor());
		$logger->pushProcessor(new FriendicaIntrospectionProcessor(LogLevel::DEBUG, ['Friendica\\Core\\Logger', 'Friendica\\Util\\WorkerLogger']));

		if (isset($config)) {
			$debugging = $config->get('system', 'debugging');
			$stream = $config->get('system', 'logfile');
			$level = $config->get('system', 'loglevel');

			if ($debugging) {
				static::addStreamHandler($logger, $stream, $level);
			}
		}

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
	 * @param string $developerIp  The IP of the developer who wants to use the logger
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public static function createDev($channel, $developerIp)
	{
		$logger = new Monolog\Logger($channel);
		$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
		$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
		$logger->pushProcessor(new Monolog\Processor\UidProcessor());
		$logger->pushProcessor(new FriendicaIntrospectionProcessor(LogLevel::DEBUG, ['Friendica\\Core\\Logger']));


		$logger->pushHandler(new FriendicaDevelopHandler($developerIp));

		return $logger;
	}

	/**
	 * Adding a handler to a given logger instance
	 *
	 * @param LoggerInterface $logger  The logger instance
	 * @param mixed           $stream  The stream which handles the logger output
	 * @param string          $level   The level, for which this handler at least should handle logging
	 *
	 * @return void
	 *
	 * @throws InternalServerErrorException if the logger is incompatible to the logger factory
	 * @throws \Exception in case of general failures
	 */
	public static function addStreamHandler($logger, $stream, $level = LogLevel::NOTICE)
	{
		if ($logger instanceof Monolog\Logger) {
			$loglevel = Monolog\Logger::toMonologLevel($level);

			// fallback to notice if an invalid loglevel is set
			if (!is_int($loglevel)) {
				$loglevel = LogLevel::NOTICE;
			}
			$fileHandler = new Monolog\Handler\StreamHandler($stream, $loglevel);

			$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
			$fileHandler->setFormatter($formatter);

			$logger->pushHandler($fileHandler);
		} else {
			throw new InternalServerErrorException('Logger instance incompatible for MonologFactory');
		}
	}

	/**
	 * This method enables the test mode of a given logger
	 *
	 * @param LoggerInterface $logger The logger
	 *
	 * @return Monolog\Handler\TestHandler the Handling for tests
	 *
	 * @throws InternalServerErrorException if the logger is incompatible to the logger factory
	 */
	public static function enableTest($logger)
	{
		if ($logger instanceof Monolog\Logger) {
			// disable every handler so far
			$logger->pushHandler(new Monolog\Handler\NullHandler());

			// enable the test handler
			$fileHandler = new Monolog\Handler\TestHandler();
			$formatter = new Monolog\Formatter\LineFormatter("%datetime% %channel% [%level_name%]: %message% %context% %extra%\n");
			$fileHandler->setFormatter($formatter);

			$logger->pushHandler($fileHandler);

			return $fileHandler;
		} else {
			throw new InternalServerErrorException('Logger instance incompatible for MonologFactory');
		}
	}
}

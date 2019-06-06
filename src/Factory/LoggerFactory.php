<?php

namespace Friendica\Factory;

use Friendica\Core\Config\Configuration;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Introspection;
use Friendica\Util\Logger\Monolog\DevelopHandler;
use Friendica\Util\Logger\Monolog\IntrospectionProcessor;
use Friendica\Util\Logger\ProfilerLogger;
use Friendica\Util\Logger\StreamLogger;
use Friendica\Util\Logger\SyslogLogger;
use Friendica\Util\Logger\VoidLogger;
use Friendica\Util\Profiler;
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
	 * A list of classes, which shouldn't get logged
	 *
	 * @var array
	 */
	private static $ignoreClassList = [
		Logger::class,
		Profiler::class,
		'Friendica\\Util\\Logger',
	];

	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param string        $channel  The channel of the logger instance
	 * @param Configuration $config   The config
	 * @param Profiler      $profiler The profiler of the app
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws \Exception
	 * @throws InternalServerErrorException
	 */
	public static function create($channel, Database $database, Configuration $config, Profiler $profiler)
	{
		if (empty($config->get('system', 'debugging', false))) {
			$logger = new VoidLogger();
			$database->setLogger($logger);
			Logger::init($logger);
			return $logger;
		}

		$introspection = new Introspection(self::$ignoreClassList);
		$level         = $config->get('system', 'loglevel');
		$loglevel      = self::mapLegacyConfigDebugLevel((string)$level);

		switch ($config->get('system', 'logger_config', 'stream')) {
			case 'monolog':
				$loggerTimeZone = new \DateTimeZone('UTC');
				Monolog\Logger::setTimezone($loggerTimeZone);

				$logger = new Monolog\Logger($channel);
				$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
				$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
				$logger->pushProcessor(new Monolog\Processor\UidProcessor());
				$logger->pushProcessor(new IntrospectionProcessor($introspection, LogLevel::DEBUG));

				$stream = $config->get('system', 'logfile');

				// just add a stream in case it's either writable or not file
				if (!is_file($stream) || is_writable($stream)) {
					static::addStreamHandler($logger, $stream, $loglevel);
				}
				break;

			case 'syslog':
				$logger = new SyslogLogger($channel, $introspection, $loglevel);
				break;

			case 'stream':
			default:
				$stream = $config->get('system', 'logfile');
				// just add a stream in case it's either writable or not file
				if (!is_file($stream) || is_writable($stream)) {
					$logger = new StreamLogger($channel, $stream, $introspection, $loglevel);
				} else {
					$logger = new VoidLogger();
				}
				break;
		}

		$profiling = $config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerLogger around the current logger
		if (isset($profiling) && $profiling !== false) {
			$logger = new ProfilerLogger($logger, $profiler);
		}

		$database->setLogger($logger);
		Logger::init($logger);

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
	 * @param string        $channel  The channel of the logger instance
	 * @param Configuration $config   The config
	 * @param Profiler      $profiler The profiler of the app
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws InternalServerErrorException
	 * @throws \Exception
	 */
	public static function createDev($channel, Configuration $config, Profiler $profiler)
	{
		$debugging   = $config->get('system', 'debugging');
		$stream      = $config->get('system', 'dlogfile');
		$developerIp = $config->get('system', 'dlogip');

		if ((!isset($developerIp) || !$debugging) &&
		    (!is_file($stream) || is_writable($stream))) {
			$logger = new VoidLogger();
			Logger::setDevLogger($logger);
			return $logger;
		}

		$loggerTimeZone = new \DateTimeZone('UTC');
		Monolog\Logger::setTimezone($loggerTimeZone);

		$introspection = new Introspection(self::$ignoreClassList);

		switch ($config->get('system', 'logger_config', 'stream')) {

			case 'monolog':
				$loggerTimeZone = new \DateTimeZone('UTC');
				Monolog\Logger::setTimezone($loggerTimeZone);

				$logger = new Monolog\Logger($channel);
				$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
				$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
				$logger->pushProcessor(new Monolog\Processor\UidProcessor());
				$logger->pushProcessor(new IntrospectionProcessor($introspection, LogLevel::DEBUG));

				$logger->pushHandler(new DevelopHandler($developerIp));

				static::addStreamHandler($logger, $stream, LogLevel::DEBUG);
				break;

			case 'syslog':
				$logger = new SyslogLogger($channel, $introspection, LogLevel::DEBUG);
				break;

			case 'stream':
			default:
				$logger = new StreamLogger($channel, $stream, $introspection, LogLevel::DEBUG);
				break;
		}

		$profiling = $config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerLogger around the current logger
		if (isset($profiling) && $profiling !== false) {
			$logger = new ProfilerLogger($logger, $profiler);
		}

		Logger::setDevLogger($logger);

		return $logger;
	}

	/**
	 * Mapping a legacy level to the PSR-3 compliant levels
	 *
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 * @param string $level the level to be mapped
	 *
	 * @return string the PSR-3 compliant level
	 */
	private static function mapLegacyConfigDebugLevel($level)
	{
		switch ($level) {
			// legacy WARNING
			case "0":
				return LogLevel::ERROR;
			// legacy INFO
			case "1":
				return LogLevel::WARNING;
			// legacy TRACE
			case "2":
				return LogLevel::NOTICE;
			// legacy DEBUG
			case "3":
				return LogLevel::INFO;
			// legacy DATA
			case "4":
				return LogLevel::DEBUG;
			// legacy ALL
			case "5":
				return LogLevel::DEBUG;
			// default if nothing set
			default:
				return $level;
		}
	}

	/**
	 * Adding a handler to a given logger instance
	 *
	 * @param LoggerInterface $logger The logger instance
	 * @param mixed           $stream The stream which handles the logger output
	 * @param string          $level  The level, for which this handler at least should handle logging
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

	public static function addVoidHandler($logger)
	{
		if ($logger instanceof Monolog\Logger) {
			$logger->pushHandler(new Monolog\Handler\NullHandler());
		}
	}
}

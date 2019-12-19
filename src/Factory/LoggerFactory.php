<?php

namespace Friendica\Factory;

use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\FileSystem;
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
	const DEV_CHANNEL = 'dev';

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

	private $channel;

	public function __construct(string $channel)
	{
		$this->channel = $channel;
	}

	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param Database      $database The Friendica Database instance
	 * @param IConfiguration $config   The config
	 * @param Profiler      $profiler The profiler of the app
	 * @param FileSystem    $fileSystem FileSystem utils
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public function create(Database $database, IConfiguration $config, Profiler $profiler, FileSystem $fileSystem)
	{
		if (empty($config->get('system', 'debugging', false))) {
			$logger = new VoidLogger();
			$database->setLogger($logger);
			return $logger;
		}

		$introspection = new Introspection(self::$ignoreClassList);
		$level         = $config->get('system', 'loglevel');
		$loglevel      = self::mapLegacyConfigDebugLevel((string)$level);

		switch ($config->get('system', 'logger_config', 'stream')) {
			case 'monolog':
				$loggerTimeZone = new \DateTimeZone('UTC');
				Monolog\Logger::setTimezone($loggerTimeZone);

				$logger = new Monolog\Logger($this->channel);
				$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
				$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
				$logger->pushProcessor(new Monolog\Processor\UidProcessor());
				$logger->pushProcessor(new IntrospectionProcessor($introspection, LogLevel::DEBUG));

				$stream = $config->get('system', 'logfile');

				// just add a stream in case it's either writable or not file
				if (!is_file($stream) || is_writable($stream)) {
					try {
						static::addStreamHandler($logger, $stream, $loglevel);
					} catch (\Throwable $e) {
						// No Logger ..
						$logger = new VoidLogger();
					}
				}
				break;

			case 'syslog':
				try {
					$logger = new SyslogLogger($this->channel, $introspection, $loglevel);
				} catch (\Throwable $e) {
					// No logger ...
					$logger = new VoidLogger();
				}
				break;

			case 'stream':
			default:
				$stream = $config->get('system', 'logfile');
				// just add a stream in case it's either writable or not file
				if (!is_file($stream) || is_writable($stream)) {
					try {
						$logger = new StreamLogger($this->channel, $stream, $introspection, $fileSystem, $loglevel);
					} catch (\Throwable $t) {
						// No logger ...
						$logger = new VoidLogger();
					}
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
	 * @param IConfiguration $config   The config
	 * @param Profiler      $profiler The profiler of the app
	 * @param FileSystem    $fileSystem FileSystem utils
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 *
	 * @throws InternalServerErrorException
	 * @throws \Exception
	 */
	public static function createDev(IConfiguration $config, Profiler $profiler, FileSystem $fileSystem)
	{
		$debugging   = $config->get('system', 'debugging');
		$stream      = $config->get('system', 'dlogfile');
		$developerIp = $config->get('system', 'dlogip');

		if ((!isset($developerIp) || !$debugging) &&
		    (!is_file($stream) || is_writable($stream))) {
			$logger = new VoidLogger();
			return $logger;
		}

		$loggerTimeZone = new \DateTimeZone('UTC');
		Monolog\Logger::setTimezone($loggerTimeZone);

		$introspection = new Introspection(self::$ignoreClassList);

		switch ($config->get('system', 'logger_config', 'stream')) {

			case 'monolog':
				$loggerTimeZone = new \DateTimeZone('UTC');
				Monolog\Logger::setTimezone($loggerTimeZone);

				$logger = new Monolog\Logger(self::DEV_CHANNEL);
				$logger->pushProcessor(new Monolog\Processor\PsrLogMessageProcessor());
				$logger->pushProcessor(new Monolog\Processor\ProcessIdProcessor());
				$logger->pushProcessor(new Monolog\Processor\UidProcessor());
				$logger->pushProcessor(new IntrospectionProcessor($introspection, LogLevel::DEBUG));

				$logger->pushHandler(new DevelopHandler($developerIp));

				static::addStreamHandler($logger, $stream, LogLevel::DEBUG);
				break;

			case 'syslog':
				$logger = new SyslogLogger(self::DEV_CHANNEL, $introspection, LogLevel::DEBUG);
				break;

			case 'stream':
			default:
				$logger = new StreamLogger(self::DEV_CHANNEL, $stream, $introspection, $fileSystem, LogLevel::DEBUG);
				break;
		}

		$profiling = $config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerLogger around the current logger
		if (isset($profiling) && $profiling !== false) {
			$logger = new ProfilerLogger($logger, $profiler);
		}

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
		}
	}

	public static function addVoidHandler($logger)
	{
		if ($logger instanceof Monolog\Logger) {
			$logger->pushHandler(new Monolog\Handler\NullHandler());
		}
	}
}

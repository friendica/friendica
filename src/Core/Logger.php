<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\DI;
use Friendica\Util\Logger\WorkerLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @brief Logger functions
 */
class Logger
{
	/**
	 * @see Logger::error()
	 */
	const WARNING = LogLevel::ERROR;
	/**
	 * @see Logger::warning()
	 */
	const INFO = LogLevel::WARNING;
	/**
	 * @see Logger::notice()
	 */
	const TRACE = LogLevel::NOTICE;
	/**
	 * @see Logger::info()
	 */
	const DEBUG = LogLevel::INFO;
	/**
	 * @see Logger::debug()
	 */
	const DATA = LogLevel::DEBUG;
	/**
	 * @see Logger::debug()
	 */
	const ALL = LogLevel::DEBUG;

	/**
	 * @var LoggerInterface The default Logger type
	 */
	const TYPE_LOGGER = LoggerInterface::class;
	/**
	 * @var WorkerLogger A specific worker logger type, which can be anabled
	 */
	const TYPE_WORKER = WorkerLogger::class;
	/**
	 * @var LoggerInterface The current logger type
	 */
	private static $type = self::TYPE_LOGGER;

	/**
	 * @var array the legacy loglevels
	 * @deprecated 2019.03 use PSR-3 loglevels
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 */
	public static $levels = [
		self::WARNING => 'Warning',
		self::INFO => 'Info',
		self::TRACE => 'Trace',
		self::DEBUG => 'Debug',
		self::DATA => 'Data',
	];

	/**
	 * @return LoggerInterface
	 */
	private static function getWorker()
	{
		if (self::$type === self::TYPE_LOGGER) {
			return DI::logger();
		} else {
			return DI::workerLogger();
		}
	}

	/**
	 * Enable additional logging for worker usage
	 *
	 * @param string $functionName The worker function, which got called
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function enableWorker(string $functionName)
	{
		self::$type = self::TYPE_WORKER;
		self::getWorker()->setFunctionName($functionName);
	}

	/**
	 * Disable additional logging for worker usage
	 */
	public static function disableWorker()
	{
		self::$type = self::TYPE_LOGGER;
	}

	/**
	 * System is unusable.
	 *
	 * @see LoggerInterface::emergency()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function emergency($message, $context = [])
	{
		self::getWorker()->emergency($message, $context);
	}

	/**
	 * Action must be taken immediately.
	 * @see LoggerInterface::alert()
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function alert($message, $context = [])
	{
		self::getWorker()->alert($message, $context);
	}

	/**
	 * Critical conditions.
	 * @see LoggerInterface::critical()
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function critical($message, $context = [])
	{
		self::getWorker()->critical($message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 * @see LoggerInterface::error()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function error($message, $context = [])
	{
		self::getWorker()->error($message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 * @see LoggerInterface::warning()
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function warning($message, $context = [])
	{
		self::getWorker()->warning($message, $context);
	}

	/**
	 * Normal but significant events.
	 * @see LoggerInterface::notice()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function notice($message, $context = [])
	{
		self::getWorker()->notice($message, $context);
	}

	/**
	 * Interesting events.
	 * @see LoggerInterface::info()
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function info($message, $context = [])
	{
		self::getWorker()->info($message, $context);
	}

	/**
	 * Detailed debug information.
	 * @see LoggerInterface::debug()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function debug($message, $context = [])
	{
		self::getWorker()->debug($message, $context);
	}

	    /**
	 * @brief Logs the given message at the given log level
	 *
	 * @param string $msg
	 * @param string $level
	 *
	 * @throws \Exception
	 * @deprecated since 2019.03 Use Logger::debug() Logger::info() , ... instead
	 */
	public static function log($msg, $level = LogLevel::INFO)
	{
		self::getWorker()->log($level, $msg);
	}

	/**
	 * @brief An alternative logger for development.
	 * Works largely as log() but allows developers
	 * to isolate particular elements they are targetting
	 * personally without background noise
	 *
	 * @param string $msg
	 * @param string $level
	 * @throws \Exception
	 */
	public static function devLog($msg, $level = LogLevel::DEBUG)
	{
		DI::devLogger()->log($level, $msg);
	}
}

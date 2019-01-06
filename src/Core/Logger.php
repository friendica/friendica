<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Util\Logger\FriendicaLoggerInterface;
use Friendica\Util\LoggerFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @brief Logger functions
 */
class Logger extends BaseObject
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
		self::ALL => 'All',
	];

	/**
	 * @var FriendicaLoggerInterface A PSR-3 compliant logger instance
	 */
	private static $logger;

	/**
	 * @var FriendicaLoggerInterface PSR-3 compliant logger instance for developing only
	 */
	private static $devLogger;

	/**
	 * @var FriendicaLoggerInterface PSR-3 compliant logger instance for performance profiling
	 */
	private static $profLogger;

	/**
	 * Sets the default logging handler for Friendica.
	 *
	 * @param FriendicaLoggerInterface $logger The Logger instance of this Application
	 */
	public static function init($logger)
	{
		self::$logger = $logger;

		// if default logging is enabled
		if(Config::get('system', 'debugging') && isset($logger)) {
			$handlerList = Config::get('log_channel', self::$logger->getChannel());
			foreach ($handlerList as $handler) {
				self::$logger->addHandler(Config::get('log_handler', $handler));
			}
		}

		// if additional performance profiling is enabled
		if(Config::get('system', 'profiler')) {
			self::$profLogger = LoggerFactory::createProf();

			$handlerList = Config::get('log_channel', self::$profLogger->getChannel());
			foreach ($handlerList as $handler) {
				self::$profLogger->addHandler(Config::get('log_handler', $handler));
			}
		}

		// if additional develop (set due to the developer IP config) is enabled
		$developIp = Config::get('system', 'dlogip');
		if (isset($developIp)) {
			self::$devLogger = LoggerFactory::createDev($developIp);

			$handlerList = Config::get('log_channel', self::$devLogger->getChannel());
			foreach ($handlerList as $handler) {
				self::$devLogger->addHandler(Config::get('log_handler', $handler));
			}
		}
	}

	/**
	 * Mapping a legacy level to the PSR-3 compliant levels
	 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#5-psrlogloglevel
	 *
	 * @param int $level the level to be mapped
	 *
	 * @return string the PSR-3 compliant level
	 */
	public static function mapLegacyConfigDebugLevel($level)
	{
		switch ($level) {
			// legacy WARNING
			case 0:
				return LogLevel::ERROR;
			// legacy INFO
			case 1:
				return LogLevel::WARNING;
			// legacy TRACE
			case 2:
				return LogLevel::NOTICE;
			// legacy DEBUG
			case 3:
				return LogLevel::INFO;
			// legacy DATA
			case 4:
				return LogLevel::DEBUG;
			// legacy ALL
			case 5:
				return LogLevel::DEBUG;
			// default if nothing set
			default:
				return LogLevel::NOTICE;
		}
	}

	/**
	 * System is unusable.
	 * @see LoggerInterface::emergency()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function emergency($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->emergency($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
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
	 *
	 */
	public static function alert($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->alert($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
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
	 *
	 */
	public static function critical($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->critical($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
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
	 *
	 */
	public static function error($message, $context = [])
	{
		if (!isset(self::$logger)) {
			echo "not set!?\n";
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->error($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
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
	 *
	 */
	public static function warning($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->warning($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Normal but significant events.
	 * @see LoggerInterface::notice()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 *
	 */
	public static function notice($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->notice($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
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
	 *
	 */
	public static function info($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->info($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

	/**
	 * Detailed debug information.
	 * @see LoggerInterface::debug()
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public static function debug($message, $context = [])
	{
		if (!isset(self::$logger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$logger->debug($message, $context);
		self::getApp()->saveTimestamp($stamp1, 'file');
	}

    /**
     * @brief Logs the given message at the given log level
     *
     * @param string $msg
     * @param int $level
	 *
	 * @deprecated since 2019.03 Use Logger::debug() Logger::info() , ... instead
     */
    public static function log($msg, $level = LogLevel::NOTICE)
    {
		if (!isset(self::$logger)) {
			return;
		}

        $stamp1 = microtime(true);
		self::$logger->log($level, $msg);
        self::getApp()->saveTimestamp($stamp1, "file");
    }

    /**
     * @brief An alternative logger for development.
     * Works largely as log() but allows developers
     * to isolate particular elements they are targetting
     * personally without background noise
     *
     * @param string $msg
	 * @param string $level
     */
    public static function develop($msg, $level = LogLevel::DEBUG)
    {
		if (!isset(self::$logger)) {
			return;
		}

        $stamp1 = microtime(true);
        self::$devLogger->log($level, $msg);
        self::getApp()->saveTimestamp($stamp1, "file");
    }

	/**
	 * @brief explicit performance logger for profiling the app
	 *
	 * @param string $msg
	 * @param string $level
	 */
    public static function profile($msg, $level = LogLevel::DEBUG)
	{
		if (!isset(self::$profLogger)) {
			return;
		}

		$stamp1 = microtime(true);
		self::$profLogger->log($level, $msg);
		self::getApp()->saveTimestamp($stamp1, "file");
	}
}

<?php
/**
 * @file src/Core/Logger.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Logger\IFriendicaLogger;
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
	 * @var IFriendicaLogger A PSR-3 compliant logger instance
	 */
	private static $logger;

	/**
	 * @var IFriendicaLogger PSR-3 compliant logger instance for developing only
	 */
	private static $devLogger;

	/**
	 * @var IFriendicaLogger PSR-3 compliant logger instance for performance profiling
	 */
	private static $profLogger;

	/**
	 * Sets the default logging handler for Friendica.
	 *
	 * @param IFriendicaLogger $logger The Logger instance of this Application
	 */
	public static function init($logger)
	{
		self::$logger = $logger;

		// if default logging is enabled
		if (!Config::get('system', 'debugging') || !isset($logger)) {
			return;
		}

		$handlerList = [];

		$handlerNames = Config::get('log_handler', 'names');

		foreach ($handlerNames as $name) {
			$handler = LoggerFactory::createHandler($name);
			$handler->loadConfig();
			$handlerList[] = [
				$name => $handler,
			];
		}

		self::$logger->loadConfig();
		foreach (self::$logger->getHandlerNames() as $name) {
			self::$logger->addHandler($handlerList[$name]);
		}

		self::$profLogger = LoggerFactory::createProf();
		self::$profLogger->loadConfig();

		foreach (self::$profLogger->getHandlerNames() as $name) {
			self::$profLogger->addHandler($handlerList[$name]);
		}

		self::$devLogger = LoggerFactory::createDev();
		self::$devLogger->loadConfig();

		foreach (self::$devLogger->getHandlerNames() as $name) {
			self::$devLogger->addHandler($handlerList[$name]);
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

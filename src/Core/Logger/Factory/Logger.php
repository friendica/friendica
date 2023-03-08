<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Logger\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capabilities\ICanManageInstances;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Core\Logger\Type\ProfilerLogger;
use Friendica\Core\Logger\Type\StreamLogger;
use Friendica\Core\Logger\Type\SyslogLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * A logger factory
 */
class Logger
{
	const DEV_CHANNEL = 'dev';

	/** @var string The log-channel (app, worker, ...) */
	protected $channel;
	/** @var ICanManageInstances */
	protected $instanceManager;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(string $channel, ICanManageInstances $instanceManager, IManageConfigValues $config, string $logfile = null)
	{
		$this->channel         = $channel;
		$this->instanceManager = $instanceManager;
		$this->config          = $config;

		$this->instanceManager
			->registerStrategy(LoggerInterface::class, 'syslog', SyslogLogger::class)
			->registerStrategy(LoggerInterface::class, 'stream', StreamLogger::class, isset($logfile) ? [$logfile] : null);

		if ($this->config->get('system', 'profiling') ?? false) {
			$this->instanceManager->registerDecorator(LoggerInterface::class, ProfilerLogger::class);
		}
	}

	/**
	 * Creates a new PSR-3 compliant logger instances
	 *
	 * @param string|null $loglevel (optional) A given loglevel in case the loglevel in the config isn't applicable
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 */
	public function create(string $loglevel = null): LoggerInterface
	{
		if (empty($this->config->get('system', 'debugging') ?? false)) {
			return new NullLogger();
		}

		$loglevel = $loglevel ?? static::mapLegacyConfigDebugLevel($this->config->get('system', 'loglevel'));
		$name     = $this->config->get('system', 'logger_config') ?? 'stream';

		try {
			/** @var LoggerInterface */
			return $this->instanceManager->getInstance(LoggerInterface::class, $name, [$this->channel, $loglevel]);
		} catch (LogLevelException $exception) {
			// If there's a wrong config value for loglevel, try again with standard
			$logger = $this->create(LogLevel::NOTICE);
			$logger->warning('Invalid loglevel set in config.', ['loglevel' => $loglevel]);
			return $logger;
		} catch (\Throwable $e) {
			// No logger ...
			return new NullLogger();
		}
	}

	/**
	 * Creates a new PSR-3 compliant develop logger
	 *
	 * If you want to debug only interactions from your IP or the IP of a remote server for federation debug,
	 * you'll use this logger instance for the duration of your work.
	 *
	 * It should never get filled during normal usage of Friendica
	 *
	 * @return LoggerInterface The PSR-3 compliant logger instance
	 * @throws \Exception
	 */
	public function createDev()
	{
		$debugging   = $this->config->get('system', 'debugging');
		$stream      = $this->config->get('system', 'dlogfile');
		$developerIp = $this->config->get('system', 'dlogip');

		if ((!isset($developerIp) || !$debugging) &&
			(!is_file($stream) || is_writable($stream))) {
			return new NullLogger();
		}

		$name = $this->config->get('system', 'logger_config') ?? 'stream';

		/** @var LoggerInterface */
		return $this->instanceManager->getInstance(LoggerInterface::class, $name, [self::DEV_CHANNEL, LogLevel::DEBUG, $stream]);
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
	private static function mapLegacyConfigDebugLevel(string $level): string
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
}

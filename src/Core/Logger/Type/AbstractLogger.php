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

namespace Friendica\Core\Logger\Type;

use Friendica\Core\Logger\Capabilities\IHaveCallIntrospections;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * This class contains all necessary dependencies and calls for Friendica
 * Every new Logger should extend this class and define, how addEntry() works
 *
 * Additional information for each Logger, who extends this class:
 * - Introspection
 * - UID for each call
 * - Channel of the current call (i.e. index, worker, daemon, ...)
 */
abstract class AbstractLogger implements LoggerInterface
{
	/**
	 * The output channel of this logger
	 * @var string
	 */
	protected $channel;

	/**
	 * The Introspection for the current call
	 * @var IHaveCallIntrospections
	 */
	protected $introspection;

	/**
	 * The UID of the current call
	 * @var string
	 */
	protected $logUid;

	/**
	 * Adds a new entry to the log
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	abstract protected function addEntry($level, string $message, array $context = []);

	/**
	 * @param string        $channel       The output channel
	 * @param IHaveCallIntrospections $introspection The introspection of the current call
	 *
	 * @throws LoggerException
	 */
	public function __construct(string $channel, IHaveCallIntrospections $introspection)
	{
		$this->channel       = $channel;
		$this->introspection = $introspection;

		try {
			$this->logUid = Strings::getRandomHex(6);
		} catch (\Exception $exception) {
			throw new LoggerException('Cannot generate log Id', $exception);
		}
	}

	/**
	 * Simple interpolation of PSR-3 compliant replacements ( variables between '{' and '}' )
	 *
	 * @see https://www.php-fig.org/psr/psr-3/#12-message
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return string the interpolated message
	 */
	protected function psrInterpolate(string $message, array $context = []): string
	{
		$replace = [];
		foreach ($context as $key => $value) {
			// check that the value can be casted to string
			if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
				$replace['{' . $key . '}'] = $value;
			} elseif (is_array($value)) {
				$replace['{' . $key . '}'] = @json_encode($value);
			}
		}

		return strtr($message, $replace);
	}

	/**
	 * JSON Encodes a complete array including objects with "__toString()" methods
	 *
	 * @param array $input an Input Array to encode
	 *
	 * @return false|string The json encoded output of the array
	 */
	protected function jsonEncodeArray(array $input)
	{
		$output = [];

		foreach ($input as $key => $value) {
			if (is_object($value) && method_exists($value, '__toString')) {
				$output[$key] = $value->__toString();
			} else {
				$output[$key] = $value;
			}
		}

		return @json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * {@inheritdoc}
	 */
	public function emergency($message, array $context = [])
	{
		$this->addEntry(LogLevel::EMERGENCY, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert($message, array $context = [])
	{
		$this->addEntry(LogLevel::ALERT, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical($message, array $context = [])
	{
		$this->addEntry(LogLevel::CRITICAL, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function error($message, array $context = [])
	{
		$this->addEntry(LogLevel::ERROR, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning($message, array $context = [])
	{
		$this->addEntry(LogLevel::WARNING, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice($message, array $context = [])
	{
		$this->addEntry(LogLevel::NOTICE, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function info($message, array $context = [])
	{
		$this->addEntry(LogLevel::INFO, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug($message, array $context = [])
	{
		$this->addEntry(LogLevel::DEBUG, (string) $message, $context);
	}

	/**
	 * {@inheritdoc}
	 */
	public function log($level, $message, array $context = [])
	{
		$this->addEntry($level, (string) $message, $context);
	}
}

<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Security\EJabberd\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Logger\Exception\LoggerException;
use Friendica\Core\Logger\Type\SyslogLogger;
use Friendica\Core\Logger\Util\Introspection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Logger factory specific for the auth_jabberd external authentication plugin
 */
class Logger
{
	const CHANNEL = 'auth_jabberd';

	/** @var IManageConfigValues */
	protected $config;

	public function __construct(IManageConfigValues $config)
	{
		$this->config = $config;
	}

	/**
	 * Creates a syslog instance
	 *
	 * @return LoggerInterface
	 * @throws LoggerException
	 */
	public function create(): LoggerInterface
	{
		$introspection = new Introspection(\Friendica\Core\Logger\Factory\Logger::$ignoreClassList);

		return new SyslogLogger(self::CHANNEL, $introspection,
			$this->config->get('jabber', 'debug', LogLevel::NOTICE),
			$this->config->get('system', 'syslog_flags', SyslogLogger::DEFAULT_FLAGS),
			$this->config->get('system', 'syslog_facility', SyslogLogger::DEFAULT_FACILITY));
	}
}

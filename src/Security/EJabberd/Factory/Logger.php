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
use Friendica\Database\Database;
use Friendica\Util\FileSystem;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger
{
	const CHANNEL = 'auth_jabberd';

	/** @var \Friendica\Core\Logger\Factory\Logger */
	protected $loggerFac;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(\Friendica\Core\Logger\Factory\Logger $loggerFac, IManageConfigValues $config)
	{
		$this->loggerFac = $loggerFac;
		$this->config    = $config;
	}

	public function create(Database $database, Profiler $profiler, FileSystem $filesystem): LoggerInterface
	{
		return $this->loggerFac->create($database, $this->config, $profiler, $filesystem, $this->config->get('jabber', 'debug', LogLevel::NOTICE));
	}
}

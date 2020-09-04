<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Model;

use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

class Host
{
	/**
	 * Defines the environment variable, which includes the current node name instead of the detected hostname
	 *
	 * @var string
	 *
	 * @notice This is used for cluster environments, where the each node defines it own hostname.
	 */
	const ENV_VARIABLE = 'NODE_NAME';

	/** @var int|null The host id */
	private $id = null;
	/** @var string The host name */
	private $name;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(Database $dba,  LoggerInterface $logger, array $server = [])
	{
		$this->logger = $logger;

		$this->detectHostname($dba, $server);
	}

	private function detectHostname(Database $dba, array $server)
	{
		$hostname = $server[self::ENV_VARIABLE] ?? null;

		if (empty($hostname)) {
			$hostname = php_uname('n');
		}

		// Trim whitespaces first to avoid getting an empty hostname
		// For linux the hostname is read from file /proc/sys/kernel/hostname directly
		$hostname = trim($hostname);
		if (empty($hostname)) {
			$this->logger->error('Empty hostname is invalid.');
			$this->id = null;
			$this->name = "";
			return;
		}

		$this->name = strtolower($hostname);

		$host = $dba->selectFirst('host', ['id'], ['name' => $this->name]);
		if (!empty($host['id'])) {
			$this->id = (int)$host['id'];
		} else {
			$dba->replace('host', ['name' => $hostname]);

			$host = $dba->selectFirst('host', ['id'], ['name' => $hostname]);
			if (empty($host['id'])) {
				$this->logger->warning('Host name could not be inserted', ['name' => $hostname]);
			} else {
				$this->id = (int)$host['id'];
			}
		}
	}

	/**
	 * Get the id for a given host
	 *
	 * @return integer host name id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get the name of the hostname
	 *
	 * @return string host name
	 */
	public function getName()
	{
		return $this->name;
	}
}

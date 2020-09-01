<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Util;

use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Log\LoggerInterface;

/**
 * Contains node specific information
 */
class Node
{
	/**
	 * Defines the environment variable, which includes the current node name instead of the detected hostname
	 *
	 * @var string
	 *
	 * @notice This is used for cluster environments, where the each node defines it own hostname.
	 */
	const ENV_VARIABLE = 'NODE_NAME';

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string The hostname of this node
	 */
	private $hostname;

	public function __construct(LoggerInterface $logger, array $server = [])
	{
		$this->logger = $logger;
		$this->detectHostname($server);
	}

	/**
	 * Detects the hostname of the current node
	 *
	 * @param array $server
	 *
	 * @throws InternalServerErrorException If the hostname cannot get detected
	 */
	private function detectHostname(array $server = []) {
		$hostname = $server[self::ENV_VARIABLE] ?? null;

		if (empty($hostname)) {
			try {
				$nodeName = php_uname('n');
				if (empty($nodeName)) {
					throw new InternalServerErrorException('Couldn\'t determine hostname');
				}
				$hostname = $nodeName;
			} catch (\Error $error) {
				throw new InternalServerErrorException('Couldn\'t determine hostname', $error);
			}
		}

		// Trim whitespaces first to avoid getting an empty hostname
		// For linux the hostname is read from file /proc/sys/kernel/hostname directly
		$hostname = trim($hostname);
		if (empty($hostname)) {
			$this->logger->error('Empty hostname is invalid.');
			$this->hostname = "";
		}

		$this->hostname = strtolower($hostname);
	}

	/**
	 * Returns the OS's hostname
	 *
	 * @return string The hostname
	 */
	public function getHostname()
	{
		return $this->hostname;
	}
}

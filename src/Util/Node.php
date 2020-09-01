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
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Returns the OS's hostname if hostnameOverride is empty, otherwise returns hostnameOverride
	 *
	 * @param string|null $overrideHostname Optional hostname to override
	 *
	 * @return string The hostname
	 *
	 * @throws InternalServerErrorException If the hostname cannot get detected
	 */
	public function getHostname(string $overrideHostname = null)
	{
		$hostname = $overrideHostname;
		if (empty($hostname)) {
			try {
				$nodeName = php_uname('n');
				if (empty($nodeName)) {
					throw new InternalServerErrorException('Couldn\'t determine hostname');
				}
			} catch (\Error $error) {
				throw new InternalServerErrorException('Couldn\'t determine hostname', $error);
			}
		}

		// Trim whitespaces first to avoid getting an empty hostname
		// For linux the hostname is read from file /proc/sys/kernel/hostname directly
		$hostname = trim($hostname);
		if (empty($hostname)) {
			$this->logger->error('Empty hostname is invalid.');
			return "";
		}

		return strtolower($hostname);
	}
}

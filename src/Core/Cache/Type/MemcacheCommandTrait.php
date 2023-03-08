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

namespace Friendica\Core\Cache\Type;

use Friendica\Core\Cache\Exception\CachePersistenceException;

/**
 * Trait for Memcache to add a custom version of the
 * method getAllKeys() since this isn't working anymore
 *
 * Adds the possibility to directly communicate with the memcache too
 */
trait MemcacheCommandTrait
{
	/**
	 * @var string server address
	 */
	protected $server;

	/**
	 * @var int server port
	 */
	protected $port;

	/**
	 * Retrieves the stored keys of the memcache instance
	 * Uses custom commands, which aren't bound to the used instance of the class
	 *
	 * @todo Due the fact that we use a custom command, there are race conditions possible:
	 *       - $this->memcache(d) adds a key
	 *       - $this->getMemcacheKeys is called directly "after"
	 *       - But $this->memcache(d) isn't finished adding the key, so getMemcacheKeys doesn't find it
	 *
	 * @return array All keys of the memcache instance
	 *
	 * @throws CachePersistenceException
	 */
	protected function getMemcacheKeys(): array
	{
		$string = $this->sendMemcacheCommand("stats items");
		$lines  = explode("\r\n", $string);
		$keys   = [];

		foreach ($lines as $line) {
			if (preg_match("/STAT items:([\d]+):number ([\d]+)/", $line, $matches) &&
				isset($matches[1]) &&
				!in_array($matches[1], $keys)) {
				$string = $this->sendMemcacheCommand("stats cachedump " . $matches[1] . " " . $matches[2]);
				preg_match_all("/ITEM (.*?) /", $string, $matches);
				$keys = array_merge($keys, $matches[1]);
			}
		}

		return $keys;
	}

	/**
	 * Taken directly from memcache PECL source
	 * Sends a command to the memcache instance and returns the result
	 * as a string
	 *
	 * http://pecl.php.net/package/memcache
	 *
	 * @param string $command The command to send to the Memcache server
	 *
	 * @return string The returned buffer result
	 *
	 * @throws CachePersistenceException In case the memcache server isn't available (anymore)
	 */
	protected function sendMemcacheCommand(string $command): string
	{
		$s = @fsockopen($this->server, $this->port);
		if (!$s) {
			throw new CachePersistenceException("Cant connect to:" . $this->server . ':' . $this->port);
		}

		fwrite($s, $command . "\r\n");
		$buf = '';

		while (!feof($s)) {
			$buf .= fgets($s, 256);

			if (strpos($buf, "END\r\n") !== false) { // stat says end
				break;
			}

			if (strpos($buf, "DELETED\r\n") !== false || strpos($buf, "NOT_FOUND\r\n") !== false) { // delete says these
				break;
			}

			if (strpos($buf, "OK\r\n") !== false) { // flush_all says ok
				break;
			}
		}

		fclose($s);
		return ($buf);
	}
}

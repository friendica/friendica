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

namespace Friendica\Util;

/**
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Web/HTTPHeaders.php
 */
class HTTPHeaders
{
	private $in_progress = [];
	private $parsed = [];

	function __construct($headers)
	{
		$lines = explode("\n", str_replace("\r", '', $headers));

		if ($lines) {
			foreach ($lines as $line) {
				if (preg_match('/^\s+/', $line, $matches) && trim($line)) {
					if (!empty($this->in_progress['k'])) {
						$this->in_progress['v'] .= ' ' . ltrim($line);
						continue;
					}
				} else {
					if (!empty($this->in_progress['k'])) {
						$this->parsed[] = [$this->in_progress['k'] => $this->in_progress['v']];
						$this->in_progress = [];
					}

					$this->in_progress['k'] = strtolower(substr($line, 0, strpos($line, ':')));
					$this->in_progress['v'] = ltrim(substr($line, strpos($line, ':') + 1));
				}
			}

			if (!empty($this->in_progress['k'])) {
				$this->parsed[$this->in_progress['k']] = $this->in_progress['v'];
				$this->in_progress = [];
			}
		}
	}

	function fetch()
	{
		return $this->parsed;
	}
}

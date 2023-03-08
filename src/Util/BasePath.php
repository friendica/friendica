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

class BasePath
{
	/**
	 * @var string
	 */
	private $baseDir;
	/**
	 * @var array
	 */
	private $server;

	/**
	 * @param string|null $baseDir The default base path
	 * @param array       $server  server arguments
	 */
	public function __construct(string $baseDir, array $server = [])
	{
		$this->baseDir = $baseDir;
		$this->server = $server;
	}

	/**
	 * Returns the base Friendica filesystem path without trailing slash
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @return string
	 *
	 * @throws \Exception if directory isn't usable
	 */
	public function getPath()
	{
		$baseDir = $this->baseDir;
		$server = $this->server;

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['DOCUMENT_ROOT'])) {
			$baseDir = $server['DOCUMENT_ROOT'];
		}

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['PWD'])) {
			$baseDir = $server['PWD'];
		}

		$baseDir = self::getRealPath($baseDir);

		if (!is_dir($baseDir)) {
			throw new \Exception(sprintf('\'%s\' is not a valid basepath', $baseDir));
		}

		return rtrim($baseDir, '/');
	}

	/**
	 * Returns a normalized file path
	 *
	 * This is a wrapper for the "realpath" function.
	 * That function cannot detect the real path when some folders aren't readable.
	 * Since this could happen with some hosters we need to handle this.
	 *
	 * @param string $path The path that is about to be normalized
	 * @return string normalized path - when possible
	 */
	public static function getRealPath($path)
	{
		$normalized = realpath($path);

		if (!is_bool($normalized)) {
			return $normalized;
		} else {
			return $path;
		}
	}
}

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

namespace Friendica\Protocol;

use GuzzleHttp\Psr7\Uri;

class WebFingerUri
{
	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var int|null
	 */
	private $port;
	/**
	 * @var string|null
	 */
	private $path;

	private function __construct(string $user, string $host, int $port = null, string $path = null)
	{
		$this->user = $user;
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;

		$this->validate();
	}

	/**
	 * @param string $addr
	 * @return WebFingerUri
	 */
	public static function fromString(string $addr): WebFingerUri
	{
		$uri = new Uri('acct://' . preg_replace('/^acct:/', '', $addr));

		return new self($uri->getUserInfo(), $uri->getHost(), $uri->getPort(), $uri->getPath());
	}

	private function validate()
	{
		if (!$this->user) {
			throw new \InvalidArgumentException('WebFinger URI User part is required');
		}

		if (!$this->host) {
			throw new \InvalidArgumentException('WebFinger URI Host part is required');
		}
	}

	public function getUser(): string
	{
		return $this->user;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getFullHost(): string
	{
		return $this->host
			. ($this->port ? ':' . $this->port : '') .
			($this->path ?: '');
	}

	public function getLongForm(): string
	{
		return 'acct:' . $this->getShortForm();
	}

	public function getShortForm(): string
	{
		return $this->user . '@' . $this->getFullHost();
	}

	public function getAddr(): string
	{
		return $this->getShortForm();
	}

	public function __toString(): string
	{
		return $this->getShortForm();
	}
}

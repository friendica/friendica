<?php

/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU APGL version 3 or any later version
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
 * Used to check/generate entities for the Friendica codebase
 */

declare(strict_types=1);

namespace Friendica\Domain\Entity;

use Friendica\BaseEntity;

/**
 * Entity class for table addon
 *
 * registered addons
 */
class Addon extends BaseEntity
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 * addon base (file)name
	 */
	private $name = '';

	/**
	 * @var string
	 * currently unused
	 */
	private $version = '';

	/**
	 * @var bool
	 * currently always 1
	 */
	private $installed = '0';

	/**
	 * @var bool
	 * currently unused
	 */
	private $hidden = '0';

	/**
	 * @var int
	 * file timestamp to check for reloads
	 */
	private $timestamp = '0';

	/**
	 * @var bool
	 * 1 = has admin config, 0 = has no admin config
	 */
	private $pluginAdmin = '0';

	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'version' => $this->version,
			'installed' => $this->installed,
			'hidden' => $this->hidden,
			'timestamp' => $this->timestamp,
			'plugin_admin' => $this->pluginAdmin,
		];
	}

	/**
	 * @return int
	 * Get
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 * Get addon base (file)name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * Set addon base (file)name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 * Get currently unused
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 * Set currently unused
	 */
	public function setVersion(string $version)
	{
		$this->version = $version;
	}

	/**
	 * @return bool
	 * Get currently always 1
	 */
	public function getInstalled()
	{
		return $this->installed;
	}

	/**
	 * @param bool $installed
	 * Set currently always 1
	 */
	public function setInstalled(bool $installed)
	{
		$this->installed = $installed;
	}

	/**
	 * @return bool
	 * Get currently unused
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * @param bool $hidden
	 * Set currently unused
	 */
	public function setHidden(bool $hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * @return int
	 * Get file timestamp to check for reloads
	 */
	public function getTimestamp()
	{
		return $this->timestamp;
	}

	/**
	 * @param int $timestamp
	 * Set file timestamp to check for reloads
	 */
	public function setTimestamp(int $timestamp)
	{
		$this->timestamp = $timestamp;
	}

	/**
	 * @return bool
	 * Get 1 = has admin config, 0 = has no admin config
	 */
	public function getPluginAdmin()
	{
		return $this->pluginAdmin;
	}

	/**
	 * @param bool $pluginAdmin
	 * Set 1 = has admin config, 0 = has no admin config
	 */
	public function setPluginAdmin(bool $pluginAdmin)
	{
		$this->pluginAdmin = $pluginAdmin;
	}
}

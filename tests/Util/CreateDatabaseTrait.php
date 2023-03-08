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

namespace Friendica\Test\Util;

use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

trait CreateDatabaseTrait
{
	use DatabaseTestTrait;
	use VFSTrait;

	/** @var Database|null */
	protected $dba = null;

	public function getDbInstance(): Database
	{
		if (isset($this->dba)) {
			return $this->dba;
		}

		$configFileManager = new ConfigFileManager($this->root->url(), $this->root->url() . '/config/', $this->root->url() . '/static/');
		$config            = new ReadOnlyFileConfig(new Cache([
			'database' => [
				'disable_pdo' => true
			],
		]));

		$database = new StaticDatabase($config, (new DbaDefinition($this->root->url()))->load(), (new ViewDefinition($this->root->url()))->load());
		$database->setTestmode(true);

		return $database;
	}
}

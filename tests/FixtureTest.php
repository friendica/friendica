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
 * FixtureTest class.
 */

namespace Friendica\Test;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type\Memory;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;

/**
 * Parent class for test cases requiring fixtures
 */
abstract class FixtureTest extends DatabaseTest
{
	use VFSTrait;

	/** @var Dice */
	protected $dice;

	/**
	 * Create variables used by tests.
	 */
	protected function setUp(): void
	{
		$this->setUpVfsDir();

		parent::setUp();

		$server                   = $_SERVER;
		$server['REQUEST_METHOD'] = Router::GET;

		$this->dice = (new Dice())
			->addRules(include __DIR__ . '/../static/dependencies.config.php')
			->addRule(ConfigFileManager::class, [
				'instanceOf' => Config::class,
				'call'       => [['createConfigFileLoader', [$this->root->url(), $server,],
								  Dice::CHAIN_CALL]]])
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(IHandleSessions::class, ['instanceOf' => Memory::class, 'shared' => true, 'call' => null])
			->addRule(Arguments::class, [
				'instanceOf' => Arguments::class,
				'call'       => [
					['determine', [$server, $_GET], Dice::CHAIN_CALL],
				],
			]);
		DI::init($this->dice);

		$configCache = $this->dice->create(Cache::class);
		$configCache->set('database', 'disable_pdo', true);

		/** @var Database $dba */
		$dba = $this->dice->create(Database::class);

		$dba->setTestmode(true);

		DBStructure::checkInitialValues();

		// Load the API dataset for the whole API
		$this->loadFixture(__DIR__ . '/datasets/api.fixture.php', $dba);
	}

	protected function useHttpMethod(string $method = Router::GET)
	{
		$server                   = $_SERVER;
		$server['REQUEST_METHOD'] = $method;

		$this->dice = $this->dice
			->addRule(Arguments::class, [
				'instanceOf' => Arguments::class,
				'call'       => [
					['determine', [$server, $_GET], Dice::CHAIN_CALL],
				],
			]);

		DI::init($this->dice);
	}
}

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
 */

namespace Friendica\Test\src\Console;

use Friendica\Console\ServerBlock;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Mockery;

class ServerBlockConsoleTest extends ConsoleTest
{
	protected $defaultBlockList = [
		[
			'domain' => 'social.nobodyhasthe.biz',
			'reason' => 'Illegal content',
		],
		[
			'domain' => 'pod.ordoevangelistarum.com',
			'reason' => 'Illegal content',
		]
	];
	/**
	 * @var IManageConfigValues|Mockery\LegacyMockInterface|Mockery\MockInterface
	 */
	private $configMock;

	protected function setUp() : void
	{
		parent::setUp();

		$this->configMock = Mockery::mock(IManageConfigValues::class);
	}

	/**
	 * Test to list the default blocked servers
	 */
	public function testBlockedServersList()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$txt = $this->dumpExecute($console);

		$output = <<<CONS
+----------------------------+-----------------+
| Domain                     | Reason          |
+----------------------------+-----------------+
| social.nobodyhasthe.biz    | Illegal content |
| pod.ordoevangelistarum.com | Illegal content |
+----------------------------+-----------------+


CONS;

		self::assertEquals($output, $txt);
	}

	/**
	 * Test blockedservers add command
	 */
	public function testAddBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => 'I like it!',
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$console->setArgument(2, 'I like it!');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain \'testme.now\' is now blocked. (Reason: \'I like it!\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command with the default reason
	 */
	public function testAddBlockedServerWithDefaultReason()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => ServerBlock::DEFAULT_REASON,
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain \'testme.now\' is now blocked. (Reason: \'' . ServerBlock::DEFAULT_REASON . '\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command on existed domain
	 */
	public function testUpdateBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
			[
				'domain' => 'pod.ordoevangelistarum.com',
				'reason' => 'Other reason',
			]
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$console->setArgument(2, 'Other reason');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain \'pod.ordoevangelistarum.com\' is now updated. (Reason: \'Other reason\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command
	 */
	public function testRemoveBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain \'pod.ordoevangelistarum.com\' is not more blocked' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers with a wrong command
	 */
	public function testBlockedServersWrongCommand()
	{
		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'wrongcommand');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Unknown command', $txt);
	}

	/**
	 * Test blockedservers remove with not existing domain
	 */
	public function testRemoveBlockedServerNotExist()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'not.exiting');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain \'not.exiting\' is not blocked.' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command without argument
	 */
	public function testAddBlockedServerMissingArgument()
	{
		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Add needs a domain and optional a reason.', $txt);
	}

	/**
	 * Test blockedservers add command without save
	 */
	public function testAddBlockedServerNoSave()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => ServerBlock::DEFAULT_REASON,
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(false)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$txt = $this->dumpExecute($console);

		self::assertEquals('Couldn\'t save \'testme.now\' as blocked server' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command without save
	 */
	public function testRemoveBlockedServerNoSave()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist', [])
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(false)
			->once();

		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		self::assertEquals('Couldn\'t remove \'pod.ordoevangelistarum.com\' from blocked servers' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command without argument
	 */
	public function testRemoveBlockedServerMissingArgument()
	{
		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Remove needs a second parameter.', $txt);
	}

	/**
	 * Test the blockedservers help
	 */
	public function testBlockedServersHelp()
	{
		$console = new ServerBlock($this->configMock, $this->consoleArgv);
		$console->setOption('help', true);
		$txt = $this->dumpExecute($console);

		$help = <<<HELP
console serverblock - Manage blocked server domain patterns
Usage
    bin/console serverblock [-h|--help|-?] [-v]
    bin/console serverblock add <pattern> <reason> [-h|--help|-?] [-v]
    bin/console serverblock remove <pattern> [-h|--help|-?] [-v]
    bin/console serverblock export <filename>
    bin/console serverblock import <filename>

Description
    With this tool, you can list the current blocked server domain patterns
    or you can add / remove a blocked server domain pattern from the list.
    Using the export and import options you can share your server blocklist
    with other node admins by CSV files.

    Patterns are case-insensitive shell wildcard comprising the following special characters:
    - * : Any number of characters
    - ? : Any single character
    - [<char1><char2>...] : char1 or char2 or...

Options
    -h|--help|-? Show help information
    -v           Show more debug information.

HELP;

		self::assertEquals($help, $txt);
	}
}

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

namespace Friendica\Test\src\Console;

use Friendica\Console\ServerBlock;
use Friendica\Moderation\DomainPatternBlocklist;
use Friendica\Test\FixtureTestTrait;
use Mockery;

class ServerBlockConsoleTest extends ConsoleTest
{
	use FixtureTestTrait;

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
	 * @var DomainPatternBlocklist|Mockery\LegacyMockInterface|Mockery\MockInterface
	 */
	private $blocklistMock;

	protected function setUp() : void
	{
		parent::setUp();

		$this->setUpFixtures();

		$this->blocklistMock = Mockery::mock(DomainPatternBlocklist::class);
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
	}

	/**
	 * Test to list the default blocked servers
	 */
	public function testBlockedServersList()
	{
		$this->blocklistMock
			->shouldReceive('get')
			->andReturn($this->defaultBlockList)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$txt = $this->dumpExecute($console);

		$php_eol = PHP_EOL;

		$output = <<<CONS
+----------------------------+-----------------+$php_eol| Pattern                    | Reason          |$php_eol+----------------------------+-----------------+$php_eol| social.nobodyhasthe.biz    | Illegal content |$php_eol| pod.ordoevangelistarum.com | Illegal content |$php_eol+----------------------------+-----------------+$php_eol

CONS;

		self::assertEquals($output, $txt);
	}

	/**
	 * Test blockedservers add command
	 */
	public function testAddBlockedServer()
	{
		$this->blocklistMock
			->shouldReceive('addPattern')
			->with('testme.now', 'I like it!')
			->andReturn(1)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$console->setArgument(2, 'I like it!');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain pattern \'testme.now\' is now blocked. (Reason: \'I like it!\')' . "\n", $txt);
	}

	/**
	 * Test blockedservers add command on existed domain
	 */
	public function testUpdateBlockedServer()
	{
		$this->blocklistMock
			->shouldReceive('addPattern')
			->with('pod.ordoevangelistarum.com', 'Other reason')
			->andReturn(2)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$console->setArgument(2, 'Other reason');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain pattern \'pod.ordoevangelistarum.com\' is now updated. (Reason: \'Other reason\')' . "\n", $txt);
	}

	/**
	 * Test blockedservers remove command
	 */
	public function testRemoveBlockedServer()
	{
		$this->blocklistMock
			->shouldReceive('removePattern')
			->with('pod.ordoevangelistarum.com')
			->andReturn(2)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain pattern \'pod.ordoevangelistarum.com\' isn\'t blocked anymore' . "\n", $txt);
	}

	/**
	 * Test blockedservers with a wrong command
	 */
	public function testBlockedServersWrongCommand()
	{
		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'wrongcommand');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Unknown command', $txt);
	}

	/**
	 * Test blockedservers remove with not existing domain
	 */
	public function testRemoveBlockedServerNotExist()
	{
		$this->blocklistMock
			->shouldReceive('removePattern')
			->with('not.existing')
			->andReturn(1)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'not.existing');
		$txt = $this->dumpExecute($console);

		self::assertEquals('The domain pattern \'not.existing\' wasn\'t blocked.' . "\n", $txt);
	}

	/**
	 * Test blockedservers add command without argument
	 */
	public function testAddBlockedServerMissingArgument()
	{
		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Add needs a domain pattern and a reason.', $txt);

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Add needs a domain pattern and a reason.', $txt);
	}

	/**
	 * Test blockedservers add command without save
	 */
	public function testAddBlockedServerNoSave()
	{
		$this->blocklistMock
			->shouldReceive('addPattern')
			->with('testme.now', 'I like it!')
			->andReturn(0)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$console->setArgument(2, 'I like it!');
		$txt = $this->dumpExecute($console);

		self::assertEquals('Couldn\'t save \'testme.now\' as blocked domain pattern' . "\n", $txt);
	}

	/**
	 * Test blockedservers remove command without save
	 */
	public function testRemoveBlockedServerNoSave()
	{
		$this->blocklistMock
			->shouldReceive('removePattern')
			->with('pod.ordoevangelistarum.com')
			->andReturn(0)
			->once();

		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		self::assertEquals('Couldn\'t remove \'pod.ordoevangelistarum.com\' from blocked domain patterns' . "\n", $txt);
	}

	/**
	 * Test blockedservers remove command without argument
	 */
	public function testRemoveBlockedServerMissingArgument()
	{
		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
		$console->setArgument(0, 'remove');
		$txt = $this->dumpExecute($console);

		self::assertStringStartsWith('[Warning] Remove needs a second parameter.', $txt);
	}

	/**
	 * Test the blockedservers help
	 */
	public function testBlockedServersHelp()
	{
		$console = new ServerBlock($this->blocklistMock, $this->consoleArgv);
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

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

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Asika\SimpleConsole\Console;
use Console_Table;
use Friendica\Core\Worker;
use Friendica\Moderation\DomainPatternBlocklist;

/**
 * Manage blocked servers
 *
 * With this tool, you can list the current blocked servers
 * or you can add / remove a blocked server from the list
 */
class ServerBlock extends Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/** @var DomainPatternBlocklist */
	private $blocklist;

	protected function getHelp(): string
	{
		return <<<HELP
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
	}

	public function __construct(DomainPatternBlocklist $blocklist, $argv = null)
	{
		parent::__construct($argv);

		$this->blocklist = $blocklist;
	}

	protected function doExecute(): int
	{
		if (count($this->args) == 0) {
			$this->printBlockedServers();
			return 0;
		}

		switch ($this->getArgument(0)) {
			case 'add':
				return $this->addBlockedServer();
			case 'remove':
				return $this->removeBlockedServer();
			case 'export':
				return $this->exportBlockedServers();
			case 'import':
				return $this->importBlockedServers();
			default:
				throw new CommandArgsException('Unknown command.');
		}
	}

	/**
	 * Exports the list of blocked domain patterns including the reason for the
	 * block to a CSV file.
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function exportBlockedServers(): int
	{
		$filename = $this->getArgument(1);

		if (empty($filename)) {
			$this->out('A file name is required, e.g. ./bin/console serverblock export backup.csv');
			return 1;
		}

		$this->blocklist->exportToFile($filename);

		// Success
		return 0;
	}

	/**
	 * Imports a list of domain patterns and a reason for the block from a CSV
	 * file, e.g. created with the export function.
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function importBlockedServers(): int
	{
		$filename = $this->getArgument(1);

		$newBlockList = $this->blocklist::extractFromCSVFile($filename);

		if ($this->blocklist->append($newBlockList)) {
			$this->out(sprintf("Entries from %s that were not blocked before are now blocked", $filename));
			Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');
			return 0;
		} else {
			$this->out("Couldn't save the block list");
			return 1;
		}
	}

	/**
	 * Prints the whole list of blocked domain patterns including the reason
	 */
	private function printBlockedServers(): void
	{
		$table = new Console_Table();
		$table->setHeaders(['Pattern', 'Reason']);
		foreach ($this->blocklist->get() as $pattern) {
			$table->addRow($pattern);
		}

		$this->out($table->getTable());
	}

	/**
	 * Adds a domain pattern to the block list
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function addBlockedServer(): int
	{
		if (count($this->args) != 3) {
			throw new CommandArgsException('Add needs a domain pattern and a reason.');
		}

		$pattern = $this->getArgument(1);
		$reason  = $this->getArgument(2);

		$result = $this->blocklist->addPattern($pattern, $reason);
		if ($result) {
			if ($result == 2) {
				$this->out(sprintf("The domain pattern '%s' is now updated. (Reason: '%s')", $pattern, $reason));
			} else {
				$this->out(sprintf("The domain pattern '%s' is now blocked. (Reason: '%s')", $pattern, $reason));
			}
			Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');
			return 0;
		} else {
			$this->out(sprintf("Couldn't save '%s' as blocked domain pattern", $pattern));
			return 1;
		}
	}

	/**
	 * Removes a domain pattern from the block list
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function removeBlockedServer(): int
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Remove needs a second parameter.');
		}

		$pattern = $this->getArgument(1);

		$result = $this->blocklist->removePattern($pattern);
		if ($result) {
			if ($result == 2) {
				$this->out(sprintf("The domain pattern '%s' isn't blocked anymore", $pattern));
				Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');
				return 0;
			} else {
				$this->out(sprintf("The domain pattern '%s' wasn't blocked.", $pattern));
				return 1;
			}
		} else {
			$this->out(sprintf("Couldn't remove '%s' from blocked domain patterns", $pattern));
			return 1;
		}
	}
}

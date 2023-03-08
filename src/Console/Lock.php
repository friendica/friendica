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
use Friendica\App;
use Friendica\Core\Lock\Capability\ICanLock;
use RuntimeException;

/**
 * tool to access the locks from the CLI
 *
 * With this script you can access the locks of your node from the CLI.
 * You can read current locks and set/remove locks.
 */
class Lock extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;

	/**
	 * @var ICanLock
	 */
	private $lock;

	protected function getHelp()
	{
		$help = <<<HELP
console lock - Manage node locks
Synopsis
	bin/console lock list [<prefix>] [-h|--help|-?] [-v]
	bin/console lock set <lock> [<timeout> [<ttl>]] [-h|--help|-?] [-v]
	bin/console lock del <lock> [-h|--help|-?] [-v]
	bin/console lock clear [-h|--help|-?] [-v]

Description
	bin/console lock list [<prefix>]
		List all locks, optionally filtered by a prefix

	bin/console lock set <lock> [<timeout> [<ttl>]]
		Sets manually a lock, optionally with the provided TTL (time to live) with a default of five minutes.

	bin/console lock del <lock>
		Deletes a lock.

	bin/console lock clear
		Clears all locks

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, ICanLock $lock, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->lock    = $lock;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (!$this->appMode->has(App\Mode::DBAVAILABLE)) {
			$this->out('Database isn\'t ready or populated yet, database cache won\'t be available');
		}

		if ($this->getOption('v')) {
			$this->out('Lock Driver Name: ' . $this->lock->getName());
			$this->out('Lock Driver Class: ' . get_class($this->lock));
		}

		switch ($this->getArgument(0)) {
			case 'list':
				$this->executeList();
				break;
			case 'set':
				$this->executeSet();
				break;
			case 'del':
				$this->executeDel();
				break;
			case 'clear':
				$this->executeClear();
				break;
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		return 0;
	}

	private function executeList()
	{
		$prefix = $this->getArgument(1, '');
		$keys   = $this->lock->getLocks($prefix);

		if (empty($prefix)) {
			$this->out('Listing all Locks:');
		} else {
			$this->out('Listing all Locks starting with "' . $prefix . '":');
		}

		$count = 0;
		foreach ($keys as $key) {
			$this->out($key);
			$count++;
		}

		$this->out($count . ' locks found');
	}

	private function executeDel()
	{
		if (count($this->args) >= 2) {
			$lock = $this->getArgument(1);

			if ($this->lock->release($lock, true)) {
				$this->out(sprintf('Lock \'%s\' released.', $lock));
			} else {
				$this->out(sprintf('Couldn\'t release Lock \'%s\'', $lock));
			}

		} else {
			throw new CommandArgsException('Too few arguments for del.');
		}
	}

	private function executeSet()
	{
		if (count($this->args) >= 2) {
			$lock    = $this->getArgument(1);
			$timeout = intval($this->getArgument(2, false));
			$ttl     = intval($this->getArgument(3, false));

			if ($this->lock->isLocked($lock)) {
				throw new RuntimeException(sprintf('\'%s\' is already set.', $lock));
			}

			if (!empty($ttl) && !empty($timeout)) {
				$result = $this->lock->acquire($lock, $timeout, $ttl);
			} elseif (!empty($timeout)) {
				$result = $this->lock->acquire($lock, $timeout);
			} else {
				$result = $this->lock->acquire($lock);
			}

			if ($result) {
				$this->out(sprintf('Lock \'%s\' acquired.', $lock));
			} else {
				throw new RuntimeException(sprintf('Unable to lock \'%s\'.', $lock));
			}
		} else {
			throw new CommandArgsException('Too few arguments for set.');
		}
	}

	private function executeClear()
	{
		$result = $this->lock->releaseAll(true);
		if ($result) {
			$this->out('Locks successfully cleared.');
		} else {
			throw new RuntimeException('Unable to clear the locks.');
		}
	}
}

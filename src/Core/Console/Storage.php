<?php

namespace Friendica\Core\Console;

use Asika\SimpleConsole\CommandArgsException;
use Friendica\Core\StorageManager;

/**
 * @brief tool to manage storage backend and stored data from CLI
 *
 */
class Storage extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console storage - manage storage backend and stored data
Synopsis
    bin/console storage [-h|--help|-?] [-v]
        Show this help
    
    bin/console storage list
        List available storage backends
    
    bin/console storage set <name>
        Set current storage backend
            name        storage backend to use. see "list".
    
    bin/console storage move [table]
        Move stored data to current storage backend.
            table       one of "photo" or "attach". default to both
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return -1;
		}

		switch($this->args[0]) {
		case 'list':
			return $this->do_list();
			break;
		case 'set':
			return $this->do_set();
			break;
		case 'move':
			return $this->do_move();
			break;
		}

		$this->out(sprintf('Invalid action "%s"', $this->args[0]));
		return -1;
	}

	protected function do_list()
	{
		$rowfmt = ' %-3s | %-20s';
		$current = StorageManager::getBackend();
		$this->out(sprintf($rowfmt, 'Sel', 'Name'));
		$this->out('-----------------------');
		$isregisterd = false;
		foreach(StorageManager::listBackends() as $name => $class) {
			$issel = ' ';
			if ($current === $class) {
				$issel = '*';
				$isregisterd = true;
			};
			$this->out(sprintf($rowfmt, $issel , $name ));
		}

		if ($current === '') {
			$this->out();
			$this->out('This sistem is using legacy storage system');
		}
		if ($current !== '' && !$isregisterd) {
			$this->out();
			$this->out('The current storage class (' . $current . ') is not registered!');
		}
		return 0;
	}

	protected function do_set()
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Invalid arguments');
		}

		$name = $this->args[1];
		$class = StorageManager::getByName($name);

		if ($class === '') {
			$this->out($name . ' is not a registered backend.');
			return -1;
		}

		StorageManager::setBackend($class);
		return 0;
	}

	protected function do_move()
	{
		$table = null;
		if (count($this->args) < 1 || count($this->args) > 2) {
			throw new CommandArgsException('Invalid arguments');
		}
		if (count($this->args) == 2) {
			$table = strtolower($this->args[1]);
			if (!in_array($table, ['photo', 'attach'])) {
				throw new CommandArgsException('Invalid table');
			}
		}

		$current = StorageManager::getBackend();
		$r = StorageManager::move($current);
		$this->out(sprintf('Moved %d files', $r));
	}
}

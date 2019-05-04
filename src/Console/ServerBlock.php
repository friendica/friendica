<?php

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Console_Table;
use Friendica\Core\Config\Configuration;

/**
 * @brief Manage blocked servers
 *
 * With this tool, you can list the current blocked servers
 * or you can add / remove a blocked server from the list
 */
class ServerBlock extends AbstractAppConsole
{
	const DEFAULT_REASON = 'blocked';

	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console serverblock - Manage blocked servers
Usage
	bin/console serverblock [-h|--help|-?] [-v]
	bin/console serverblock add <server> <reason> [-h|--help|-?] [-v]
	bin/console serverblock remove <server> [-h|--help|-?] [-v]

Description
	With this tool, you can list the current blocked servers
    or you can add / remove a blocked server from the list

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		$a = $this->app;

		if (count($this->args) == 0) {
			$this->printBlockedServers($a->getConfig());
			return 0;
		}

		switch ($this->getArgument(0)) {
			case 'add':
				return $this->addBlockedServer($a->getConfig());
			case 'remove':
				return $this->removeBlockedServer($a->getConfig());
			default:
				throw new CommandArgsException('Unknown command.');
				break;
		}
	}

	/**
	 * Prints the whole list of blocked domains including the reason
	 *
	 * @param Configuration $config
	 */
	private function printBlockedServers(Configuration $config)
	{
		$table = new Console_Table();
		$table->setHeaders(['Domain', 'Reason']);
		$blocklist = $config->get('system', 'blocklist');
		foreach ($blocklist as $domain) {
			$table->addRow($domain);
		}
		$this->out($table->getTable());
	}

	/**
	 * Adds a server to the blocked list
	 *
	 * @param Configuration $config
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function addBlockedServer(Configuration $config)
	{
		if (count($this->args) < 2 || count($this->args) > 3) {
			throw new CommandArgsException('Add needs a domain and optional a reason.');
		}

		$domain = $this->getArgument(1);
		$reason = (count($this->args) === 3) ? $this->getArgument(2) : self::DEFAULT_REASON;

		$update = false;

		$currBlocklist = $config->get('system', 'blocklist');
		$newBlockList = [];
		foreach ($currBlocklist  as $blocked) {
			if ($blocked['domain'] === $domain) {
				$update = true;
				$newBlockList[] = [
					'domain' => $domain,
					'reason' => $reason,
				];
			} else {
				$newBlockList[] = $blocked;
			}
		}

		if (!$update) {
			$newBlockList[] = [
				'domain' => $domain,
				'reason' => $reason,
			];
		}

		if ($config->set('system', 'blocklist', $newBlockList)) {
			if ($update) {
				$this->out(sprintf("The domain '%s' is now updated. (Reason: '%s')", $domain, $reason));
			} else {
				$this->out(sprintf("The domain '%s' is now blocked. (Reason: '%s')", $domain, $reason));
			}
			return 0;
		} else {
			$this->out(sprintf("Couldn't save '%s' as blocked server", $domain));
			return 1;
		}
	}

	/**
	 * Removes a server from the blocked list
	 *
	 * @param Configuration $config
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function removeBlockedServer(Configuration $config)
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Remove needs a second parameter.');
		}

		$domain = $this->getArgument(1);

		$found = false;

		$currBlocklist = $config->get('system', 'blocklist');
		$newBlockList = [];
		foreach ($currBlocklist as $blocked) {
			if ($blocked['domain'] === $domain) {
				$found = true;
			} else {
				$newBlockList[] = $blocked;
			}
		}

		if (!$found) {
			$this->out(sprintf("The domain '%s' is not blocked.", $domain));
			return 1;
		}

		if ($config->set('system', 'blocklist', $newBlockList)) {
			$this->out(sprintf("The domain '%s' is not more blocked", $domain));
			return 0;
		} else {
			$this->out(sprintf("Couldn't remove '%s' from blocked servers", $domain));
			return 1;
		}
	}
}

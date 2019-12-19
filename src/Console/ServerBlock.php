<?php

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Asika\SimpleConsole\Console;
use Console_Table;
use Friendica\Core\Config\IConfiguration;

/**
 * @brief Manage blocked servers
 *
 * With this tool, you can list the current blocked servers
 * or you can add / remove a blocked server from the list
 */
class ServerBlock extends Console
{
	const DEFAULT_REASON = 'blocked';

	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var IConfiguration
	 */
	private $config;

	protected function getHelp()
	{
		$help = <<<HELP
console serverblock - Manage blocked server domain patterns
Usage
	bin/console serverblock [-h|--help|-?] [-v]
	bin/console serverblock add <pattern> <reason> [-h|--help|-?] [-v]
	bin/console serverblock remove <pattern> [-h|--help|-?] [-v]

Description
	With this tool, you can list the current blocked server domain patterns
    or you can add / remove a blocked server domain pattern from the list.
    
    Patterns are case-insensitive shell wildcard comprising the following special characters:
    - * : Any number of characters
    - ? : Any single character
    - [<char1><char2>...] : char1 or char2 or...

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(IConfiguration $config, $argv = null)
	{
		parent::__construct($argv);

		$this->config = $config;
	}

	protected function doExecute()
	{
		if (count($this->args) == 0) {
			$this->printBlockedServers($this->config);
			return 0;
		}

		switch ($this->getArgument(0)) {
			case 'add':
				return $this->addBlockedServer($this->config);
			case 'remove':
				return $this->removeBlockedServer($this->config);
			default:
				throw new CommandArgsException('Unknown command.');
				break;
		}
	}

	/**
	 * Prints the whole list of blocked domains including the reason
	 *
	 * @param IConfiguration $config
	 */
	private function printBlockedServers(IConfiguration $config)
	{
		$table = new Console_Table();
		$table->setHeaders(['Domain', 'Reason']);
		$blocklist = $config->get('system', 'blocklist', []);
		foreach ($blocklist as $domain) {
			$table->addRow($domain);
		}
		$this->out($table->getTable());
	}

	/**
	 * Adds a server to the blocked list
	 *
	 * @param IConfiguration $config
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function addBlockedServer(IConfiguration $config)
	{
		if (count($this->args) < 2 || count($this->args) > 3) {
			throw new CommandArgsException('Add needs a domain and optional a reason.');
		}

		$domain = $this->getArgument(1);
		$reason = (count($this->args) === 3) ? $this->getArgument(2) : self::DEFAULT_REASON;

		$update = false;

		$currBlocklist = $config->get('system', 'blocklist', []);
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
	 * @param IConfiguration $config
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function removeBlockedServer(IConfiguration $config)
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Remove needs a second parameter.');
		}

		$domain = $this->getArgument(1);

		$found = false;

		$currBlocklist = $config->get('system', 'blocklist', []);
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

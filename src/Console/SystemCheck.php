<?php

namespace Friendica\Console;

use Console_Table;

/**
 * @brief tool to check the current system status
 *
 * With this script you can check, if your node is setup correctly.
 *
 * @author Philipp Holzer <admin@philipp.info>
 */
class SystemCheck extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var \Friendica\Util\SystemCheck
	 */
	private $systemCheck;

	protected function getHelp()
	{
		$help = <<<HELP
console check - Checks the system status
Synopsis
	bin/console check [-h|--help|-?] [-v]
	bin/console check db  [-h|--help|-?] [-v]
	bin/console check env [-h|--help|-?] [-v]

Description
	bin/console check
		Checks every possible check for this node

	bin/console check db
		Checks if the database is setup correctly

	bin/console check env
		Checks if the environment is setup correctly
		
Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(\Friendica\Util\SystemCheck $systemCheck, array $argv = null)
	{
		parent::__construct($argv);

		$this->systemCheck = $systemCheck;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if ($this->systemCheck->check()) {
			$this->out('System status healthy');
		}

		$this->printSummary();
	}

	private function printSummary()
	{
		$checks = $this->systemCheck->getChecks();

		$table = new Console_Table();

		$table->setHeaders(['Check', 'Status', 'Required', 'Help', 'Error']);
		foreach ($checks as $check) {
			$table->addRow($check);
		}
		$this->out($table->getTable());
	}
}

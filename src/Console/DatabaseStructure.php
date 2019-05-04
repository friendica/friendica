<?php

namespace Friendica\Console;

use Friendica\Core;
use Friendica\Core\Update;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use RuntimeException;

/**
 * @brief Performs database updates from the command line
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class DatabaseStructure extends AbstractAppConsole
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console dbstructure - Performs database updates
Usage
	bin/console dbstructure <command> [-h|--help|-?] |-f|--force] [-v]

Commands
	dryrun   Show database update schema queries without running them
	update   Update database schema
	dumpsql  Dump database schema
	toinnodb Convert all tables from MyISAM to InnoDB

Options
    -h|--help|-?       Show help information
    -v                 Show more debug information.
    -f|--force         Force the update command (Even if the database structure matches)
    -o|--override      Override running or stalling updates
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if (!DBA::connected()) {
			throw new RuntimeException('Unable to connect to database');
		}

		Core\Config::load();

		$a = $this->app;

		switch ($this->getArgument(0)) {
			case "dryrun":
				$output = DBStructure::update($a->getBasePath(), true, false);
				break;
			case "update":
				$force    = $this->getOption(['f', 'force'], false);
				$override = $this->getOption(['o', 'override'], false);
				$output = Update::run($a->getBasePath(), $force, $override,true, false);
				break;
			case "dumpsql":
				ob_start();
				DBStructure::printStructure($a->getBasePath());
				$output = ob_get_clean();
				break;
			case "toinnodb":
				ob_start();
				DBStructure::convertToInnoDB();
				$output = ob_get_clean();
				break;
			default:
				$output = 'Unknown command: ' . $this->getArgument(0);
		}

		$this->out($output);

		return 0;
	}
}

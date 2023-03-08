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

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Update;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Util\BasePath;
use Friendica\Util\Writer\DbaDefinitionSqlWriter;
use Friendica\Util\Writer\DocWriter;
use Friendica\Util\Writer\ViewDefinitionSqlWriter;
use RuntimeException;

/**
 * Performs database updates from the command line
 */
class DatabaseStructure extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/** @var Database */
	private $dba;

	/** @var IManageConfigValues */
	private $config;

	/** @var DbaDefinition */
	private $dbaDefinition;

	/** @var ViewDefinition */
	private $viewDefinition;

	/** @var string */
	private $basePath;

	protected function getHelp()
	{
		$help = <<<HELP
console dbstructure - Performs database updates
Usage
	bin/console dbstructure <command> [options]

Commands
    drop     Show tables that aren't in use by Friendica anymore and can be dropped
       -e|--execute    Execute the removal

    update   Update database schema
       -f|--force      Force the update command (Even if the database structure matches)
       -o|--override   Override running or stalling updates

    dryrun   Show database update schema queries without running them
    dumpsql  Dump database schema
    toinnodb Convert all tables from MyISAM or InnoDB in the Antelope file format to InnoDB in the Barracuda file format
    initial  Set needed initial values in the tables
    version  Set the database to a given number

General Options
    -h|--help|-?       Show help information
    -v                 Show more debug information.
HELP;
		return $help;
	}

	public function __construct(Database $dba, DbaDefinition $dbaDefinition, ViewDefinition $viewDefinition, BasePath $basePath, IManageConfigValues $config, $argv = null)
	{
		parent::__construct($argv);

		$this->dba = $dba;
		$this->dbaDefinition = $dbaDefinition;
		$this->viewDefinition = $viewDefinition;
		$this->config = $config;
		$this->basePath = $basePath->getPath();
	}

	protected function doExecute(): int
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

		if ((count($this->args) > 1) && ($this->getArgument(0) != 'version')) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		} elseif ((count($this->args) != 2) && ($this->getArgument(0) == 'version')) {
			throw new \Asika\SimpleConsole\CommandArgsException('This command needs two arguments');
		}

		if (!$this->dba->isConnected()) {
			throw new RuntimeException('Unable to connect to database');
		}

		$basePath = $this->config->get('system', 'basepath');

		switch ($this->getArgument(0)) {
			case "dryrun":
				$output = DBStructure::dryRun();
				break;
			case "update":
				$force    = $this->getOption(['f', 'force'], false);
				$override = $this->getOption(['o', 'override'], false);
				$output = Update::run($basePath, $force, $override,true, false);
				break;
			case "drop":
				$execute = $this->getOption(['e', 'execute'], false);
				ob_start();
				DBStructure::dropTables($execute);
				$output = ob_get_clean();
				break;
			case "dumpsql":
				DocWriter::writeDbDefinition($this->dbaDefinition, $this->basePath);
				$output = DbaDefinitionSqlWriter::create($this->dbaDefinition);
				$output .= ViewDefinitionSqlWriter::create($this->viewDefinition);
				break;
			case "toinnodb":
				ob_start();
				DBStructure::convertToInnoDB();
				$output = ob_get_clean();
				break;
			case "version":
				ob_start();
				DBStructure::setDatabaseVersion($this->getArgument(1));
				$output = ob_get_clean();
				break;
			case "initial":
				ob_start();
				DBStructure::checkInitialValues(true);
				$output = ob_get_clean();
				break;
			default:
				$output = 'Unknown command: ' . $this->getArgument(0);
		}

		$this->out(trim($output));

		return 0;
	}
}

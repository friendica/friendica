<?php

namespace Friendica\Core\Console;

use Asika\SimpleConsole\Console;
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Install;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use RuntimeException;

require_once 'mod/install.php';
require_once 'include/dba.php';

class AutomaticInstallation extends Console
{
	protected function getHelp()
	{
		return <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a]

Description
    Installs Friendica with data based on the htconfig.php file

Notes:
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
    -a           All setup checks are required (except .htaccess)
    -f           prepared config file (e.g. ".htconfig.php" itself)
HELP;
	}

	protected function doExecute()
	{
		// Initialise the app
		$this->out("Initializing setup...\n");

		$a = get_app();
		$db_host = '';
		$db_user = '';
		$db_pass = '';
		$db_data = '';

		$config_file = $this->getOption('f', 'htconfig.php');

		$this->out("Using config $config_file...\n");
		require_once $config_file;

		Install::setInstallMode();

		$this->out(" Complete!\n\n");

		// Check basic setup
		$this->out("Checking basic setup...\n");

		$checkResults = [];
		$checkResults['basic'] = $this->runBasicChecks($a);
		$errorMessage = $this->extractErrors($checkResults['basic']);

		if ($errorMessage !== '') {
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Check database connection
		$this->out("Checking database...\n");

		$checkResults['db'] = array();
		$checkResults['db'][] = $this->runDatabaseCheck($db_host, $db_user, $db_pass, $db_data);
		$errorMessage = $this->extractErrors($checkResults['db']);

		if ($errorMessage !== '') {
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Install database
		$this->out("Inserting data into database...\n");

		$checkResults['data'] = Install::installDatabaseStructure();

		if ($checkResults['data'] !== '') {
			throw new RuntimeException("ERROR: DB Database creation error. Is the DB empty?\n");
		}

		$this->out(" Complete!\n\n");

		// Install theme
		$this->out("Installing theme\n");
		if (!empty(Config::get('system', 'theme'))) {
			Theme::install(Config::get('system', 'theme'));
			$this->out(" Complete\n\n");
		} else {
			$this->out(" Theme setting is empty. Please check the file htconfig.php\n\n");
		}

		// Copy config file
		$this->out("Saving config file...\n");
		if ($config_file != '.htconfig.php' && !copy($config_file, '.htconfig.php')) {
			throw new RuntimeException("ERROR: Saving config file failed. Please copy '$config_file' to '.htconfig.php' manually.\n");
		}
		$this->out(" Complete!\n\n");
		$this->out("\nInstallation is finished\n");

		return 0;
	}

	/**
	 * @param App $app
	 * @return array
	 */
	private function runBasicChecks($app)
	{
		$checks = [];

		Install::checkFunctions($checks);
		Install::checkImagick($checks);
		Install::checkLocalIni($checks);
		Install::checkSmarty3($checks);
		Install::checkKeys($checks);

		if (!empty(Config::get('config', 'php_path'))) {
			Install::checkPHP(Config::get('config', 'php_path'), $checks);
		} else {
			throw new RuntimeException(" ERROR: The php_path is not set in the config.\n");
		}

		$this->out(" NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.\n");

		return $checks;
	}

	/**
	 * @param $db_host
	 * @param $db_user
	 * @param $db_pass
	 * @param $db_data
	 * @return array
	 */
	private function runDatabaseCheck($db_host, $db_user, $db_pass, $db_data)
	{
		$result = array(
			'title' => 'MySQL Connection',
			'required' => true,
			'status' => true,
			'help' => '',
		);


		if (!DBA::connect($db_host, $db_user, $db_pass, $db_data)) {
			$result['status'] = false;
			$result['help'] = 'Failed, please check your MySQL settings and credentials.';
		}

		return $result;
	}

	/**
	 * @param array $results
	 * @return string
	 */
	private function extractErrors($results)
	{
		$errorMessage = '';
		$allChecksRequired = $this->getOption('a') !== null;

		foreach ($results as $result) {
			if (($allChecksRequired || $result['required'] === true) && $result['status'] === false) {
				$errorMessage .= "--------\n";
				$errorMessage .= $result['title'] . ': ' . $result['help'] . "\n";
			}
		}

		return $errorMessage;
	}
}

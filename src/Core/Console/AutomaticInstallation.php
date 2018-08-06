<?php

namespace Friendica\Core\Console;

use Asika\SimpleConsole\Console;
use Friendica\App;
use Friendica\BaseObject;
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
	bin/console autoinstall [-h|--help|-?] [-v] [-a] [-f]

Description
    Installs Friendica with data based on the local.ini.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
    -a           All setup checks are required (except .htaccess)
    -f|--file    prepared config file (e.g. "config/local.ini.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb  Save the DB credentials to the file (if environment variables is used)
    -h|--dbhost  The host of the mysql database (env MYSQL_HOST)
    -p|--dbport  The port of the mysql database (env MYSQL_PORT)
    -d|--dbdata  The name of the mysql database (env MYSQL_DATABASE)
    -U|--dbuser  The username of the mysql database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass  The password of the mysql database login (env MYSQL_PASSWORD)
    -b|--phppath The path of the PHP binary (env FRIENDICA_PHP_PATH) 
    -A|--admin   The admin email address of Friendica (env FRIENDICA_ADMIN_MAIL)
    -T|--tz      The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang    The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql database login (USERNAME is for mysql, USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql database login
   MYSQL_DATABASE              The name of the mysql database
   FRIENDICA_PHP_PATH          The path of the PHP binary
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica
   FRIENDICA_TZ                The timezone of Friendica
   FRIENDICA_LANG              The langauge of Friendica
   
Examples
	bin/console autoinstall -f 'input.ini.php
		Installs Friendica with the prepared 'input.ini.php' file

	bin/console autoinstall --savedb
		Installs Friendica with environment variables and saves them to the 'config/local.ini.php' file

	bin/console autoinstall -h localhost -p 3365 -U user -P passwort1234 -d friendica
		Installs Friendica with a local mysql database with credentials 
   
HELP;
	}

	protected function doExecute()
	{
		// Initialise the app
		$this->out("Initializing setup...\n");

		// if a config file is set,
		$config_file = $this->getOption(['f', 'file']);

		if (!empty($config_file)) {
			if ($config_file != 'config/local.ini.php') {
				// Copy config file
				$this->out("Copying config file...\n");
				if (!copy($config_file, 'config/local.ini.php')) {
					throw new RuntimeException("ERROR: Saving config file failed. Please copy '$config_file' to 'config/local.ini.php' manually.\n");
				}
			}

			// load the app after copying the file
			$a = BaseObject::getApp();

			$db_host = $a->getConfigValue('database', 'hostname');
			$db_user = $a->getConfigValue('database', 'username');
			$db_pass = $a->getConfigValue('database', 'password');
			$db_data = $a->getConfigValue('database', 'database');
		} else {
			// Creating config file
			$this->out("Creating config file...\n");

			// load the app first (for the template engine)
			$a = BaseObject::getApp();

			$save_db = $this->getOption(['s', 'savedb'], false);

			$db_host = $this->getOption(['h', 'dbhost'], ($save_db) ? getenv('MYSQL_HOST') : '');
			$db_port = $this->getOption(['p', 'dbport'], ($save_db) ? getenv('MYSQL_PORT') : null);
			$db_data = $this->getOption(['d', 'dbdata'], ($save_db) ? getenv('MYSQL_DATABASE') : '');
			$db_user = $this->getOption(['U', 'dbuser'], ($save_db) ? getenv('MYSQL_USER') . getenv('MYSQL_USERNAME') : '');
			$db_pass = $this->getOption(['P', 'dbpass'], ($save_db) ? getenv('MYSQL_PASSWORD') : '');
			$php_path = $this->getOption(['b', 'phppath'], (!empty('FRIENDICA_PHP_PATH')) ? getenv('FRIENDICA_PHP_PATH') : '');
			$admin_mail = $this->getOption(['A', 'admin'], (!empty('FRIENDICA_ADMIN_MAIL')) ? getenv('FRIENDICA_ADMIN_MAIL') : '');
			$tz = $this->getOption(['T', 'tz'], (!empty('FRIENDICA_TZ')) ? getenv('FRIENDICA_TZ') : '');
			$lang = $this->getOption(['L', 'lang'], (!empty('FRIENDICA_LANG')) ? getenv('FRIENDICA_LANG') : '');

			// creating config file
			$this->out("Creating config file...\n");

			Install::createConfig(
				$php_path,
				$db_host,
				$db_user,
				$db_pass,
				$db_data,
				$php_path,
				$tz,
				$lang,
				$admin_mail
			);
		}

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
			$this->out(" Theme setting is empty. Please check the file 'config/local.ini.php'\n\n");
		}

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

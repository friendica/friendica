<?php

namespace Friendica\Core\Console;

use Asika\SimpleConsole\Console;
use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\Installer;
use Friendica\Core\Theme;
use RuntimeException;

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
    Installs Friendica with data based on the local.config.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -a                      All setup checks are required (except .htaccess)
    -f|--file <config>      prepared config file (e.g. "config/local.config.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb             Save the DB credentials to the file (if environment variables is used)
    -H|--dbhost <host>      The host of the mysql/mariadb database (env MYSQL_HOST)
    -p|--dbport <port>      The port of the mysql/mariadb database (env MYSQL_PORT)
    -d|--dbdata <database>  The name of the mysql/mariadb database (env MYSQL_DATABASE)
    -U|--dbuser <username>  The username of the mysql/mariadb database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass <password>  The password of the mysql/mariadb database login (env MYSQL_PASSWORD)
    -u|--urlpath <url_path> The URL path of Friendica - f.e. '/friendica' (env FRIENDICA_URL_PATH) 
    -b|--phppath <php_path> The path of the PHP binary (env FRIENDICA_PHP_PATH) 
    -A|--admin <mail>       The admin email address of Friendica (env FRIENDICA_ADMIN_MAIL)
    -T|--tz <timezone>      The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang <language>    The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql/mariadb database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql/mariadb database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql/mariadb database login (MYSQL_USERNAME is for mysql, MYSQL_USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql/mariadb database login
   MYSQL_DATABASE              The name of the mysql/mariadb database
   FRIENDICA_URL_PATH          The URL path of Friendica (f.e. '/friendica')
   FRIENDICA_PHP_PATH          The path of the PHP binary
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica (this email will be used for admin access)
   FRIENDICA_TZ                The timezone of Friendica
   FRIENDICA_LANG              The langauge of Friendica
   
Examples
	bin/console autoinstall -f 'input.config.php
		Installs Friendica with the prepared 'input.config.php' file

	bin/console autoinstall --savedb
		Installs Friendica with environment variables and saves them to the 'config/local.config.php' file

	bin/console autoinstall -h localhost -p 3365 -U user -P passwort1234 -d friendica
		Installs Friendica with a local mysql database with credentials
HELP;
	}

	protected function doExecute()
	{
		// Initialise the app
		$this->out("Initializing setup...\n");

		$a = BaseObject::getApp();

		$installer = new Installer();

		$this->out(" Complete!\n\n");

		// Check Environment
		$this->out("Checking environment...\n");

		$installer->resetChecks();

		if (!$this->runBasicChecks($installer)) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// if a config file is set,
		$config_file = $this->getOption(['f', 'file']);

		if (!empty($config_file)) {
			if ($config_file != 'config' . DIRECTORY_SEPARATOR . 'local.config.php') {
				// Copy config file
				$this->out("Copying config file...\n");
				if (!copy($a->getBasePath() . DIRECTORY_SEPARATOR . $config_file, $a->getBasePath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.config.php')) {
					throw new RuntimeException("ERROR: Saving config file failed. Please copy '$config_file' to '" . $a->getBasePath() . "'"  . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "local.config.php' manually.\n");
				}
			}

			$db_host = $a->getConfigValue('database', 'hostname');
			$db_user = $a->getConfigValue('database', 'username');
			$db_pass = $a->getConfigValue('database', 'password');
			$db_data = $a->getConfigValue('database', 'database');
		} else {
			// Creating config file
			$this->out("Creating config file...\n");

			$save_db = $this->getOption(['s', 'savedb'], false);

			$db_host = $this->getOption(['H', 'dbhost'], ($save_db) ? getenv('MYSQL_HOST') : '');
			$db_port = $this->getOption(['p', 'dbport'], ($save_db) ? getenv('MYSQL_PORT') : null);
			$db_data = $this->getOption(['d', 'dbdata'], ($save_db) ? getenv('MYSQL_DATABASE') : '');
			$db_user = $this->getOption(['U', 'dbuser'], ($save_db) ? getenv('MYSQL_USER') . getenv('MYSQL_USERNAME') : '');
			$db_pass = $this->getOption(['P', 'dbpass'], ($save_db) ? getenv('MYSQL_PASSWORD') : '');
			$url_path = $this->getOption(['u', 'urlpath'], !empty('FRIENDICA_URL_PATH') ? getenv('FRIENDICA_URL_PATH') : null);
			$php_path = $this->getOption(['b', 'phppath'], !empty('FRIENDICA_PHP_PATH') ? getenv('FRIENDICA_PHP_PATH') : null);
			$admin_mail = $this->getOption(['A', 'admin'], !empty('FRIENDICA_ADMIN_MAIL') ? getenv('FRIENDICA_ADMIN_MAIL') : '');
			$tz = $this->getOption(['T', 'tz'], !empty('FRIENDICA_TZ') ? getenv('FRIENDICA_TZ') : '');
			$lang = $this->getOption(['L', 'lang'], !empty('FRIENDICA_LANG') ? getenv('FRIENDICA_LANG') : '');

			if (empty($php_path)) {
				$php_path = $installer->getPHPPath();
			}

			$installer->createConfig(
				$php_path,
				$url_path,
				(!empty($db_port) ? $db_host . ':' . $db_port : $db_host),
				$db_user,
				$db_pass,
				$db_data,
				$tz,
				$lang,
				$admin_mail,
				$a->getBasePath()
			);
		}

		$this->out(" Complete!\n\n");

		// Check database connection
		$this->out("Checking database...\n");

		$installer->resetChecks();

		if (!$installer->checkDB($db_host, $db_user, $db_pass, $db_data)) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Install database
		$this->out("Inserting data into database...\n");

		$installer->resetChecks();

		if (!$installer->installDatabase()) {
			$errorMessage = $this->extractErrors($installer->getChecks());
			throw new RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Install theme
		$this->out("Installing theme\n");
		if (!empty(Config::get('system', 'theme'))) {
			Theme::install(Config::get('system', 'theme'));
			$this->out(" Complete\n\n");
		} else {
			$this->out(" Theme setting is empty. Please check the file 'config/local.config.php'\n\n");
		}

		$this->out("\nInstallation is finished\n");

		return 0;
	}

	/**
	 * @param Installer $installer the Installer instance
	 *
	 * @return bool true if checks were successfully, otherwise false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function runBasicChecks(Installer $installer)
	{
		$checked = true;

		$installer->resetChecks();
		if (!$installer->checkFunctions())		{
			$checked = false;
		}
		if (!$installer->checkImagick()) {
			$checked = false;
		}
		if (!$installer->checkLocalIni()) {
			$checked = false;
		}
		if (!$installer->checkSmarty3()) {
			$checked = false;
		}
		if (!$installer->checkKeys()) {
			$checked = false;
		}

		$php_path = null;
		if (!empty(Config::get('config', 'php_path'))) {
			$php_path = Config::get('config', 'php_path');
		}

		if (!$installer->checkPHP($php_path, true)) {
			$checked = false;
		}

		$this->out(" NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.\n");

		return $checked;
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

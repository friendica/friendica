<?php

namespace Friendica\Test\src\Core\Console;

use org\bovigo\vfs\vfsStream;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires PHP 7.0
 */
class AutomaticInstallationConsoleTest extends ConsoleTest
{
	private $db_host;
	private $db_port;
	private $db_data;
	private $db_user;
	private $db_pass;

	public function setUp()
	{
		parent::setUp();

		if ($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php')) {
			$this->root->getChild('config')
				->removeChild('local.ini.php');
		}

		$this->db_host = getenv('MYSQL_HOST');
		$this->db_port = (!empty(getenv('MYSQL_PORT'))) ? getenv('MYSQL_PORT') : null;
		$this->db_data = getenv('MYSQL_DATABASE');
		$this->db_user = getenv('MYSQL_USERNAME') . getenv('MYSQL_USER');
		$this->db_pass = getenv('MYSQL_PASSWORD');
	}

	private function assertConfig($family, $key, $value)
	{
		$config = $this->execute(['config', $family, $key]);
		$this->assertEquals($family . "." . $key . " => " . $value . "\n", $config);
	}

	private function assertFinished($txt, $withconfig = false, $copyfile = false)
	{
		$cfg = '';

		if ($withconfig) {
			$cfg = <<<CFG


Creating config file...
CFG;
		}

		if ($copyfile) {
			$cfg = <<<CFG


Copying config file...
CFG;
		}

		$finished = <<<FIN
Initializing setup...{$cfg}

 Complete!


Checking basic setup...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!


Checking database...

 Complete!


Inserting data into database...

 Complete!


Installing theme

 Complete



Installation is finished


FIN;
		$this->assertEquals($finished, $txt);
	}

	private function assertStuckDB($txt)
	{
		$finished = <<<FIN
Initializing setup...

Creating config file...

 Complete!


Checking basic setup...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!


Checking database...

[Error] --------
MySQL Connection: Failed, please check your MySQL settings and credentials.


FIN;

		$this->assertEquals($finished, $txt);
	}

	/**
	 * @medium
	 */
	public function testWithConfig()
	{
		$config = <<<CONF
<?php return <<<INI

[database]
hostname = 
username = 
password = 
database = 
charset = utf8mb4


; ****************************************************************
; The configuration below will be overruled by the admin panel.
; Changes made below will only have an effect if the database does
; not contain any configuration for the friendica system.
; ****************************************************************

[config]
admin_email =

sitename = Friendica Social Network

register_policy = REGISTER_OPEN
register_text =

[system]
default_timezone = UTC

language = en
INI;
// Keep this line

CONF;

		vfsStream::newFile('prepared.ini.php')
			->at($this->root)
			->setContent($config);

		$txt = $this->execute(['autoinstall', '-f', 'prepared.ini.php']);

		$this->assertFinished($txt, false, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));
	}

	/**
	 * @medium
	 */
	public function testWithEnvironmentAndSave()
	{
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));

		$txt = $this->execute(['autoinstall', '--savedb']);

		$this->assertFinished($txt, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertConfig('database', 'hostname', $this->db_host . (!empty($this->db_port) ? ':' . $this->db_port : ''));
		$this->assertConfig('database', 'username', $this->db_user);
		$this->assertConfig('database', 'database', $this->db_data);
		$this->assertConfig('config', 'admin_email', 'admin@friendica.local');
		$this->assertConfig('system', 'default_timezone', 'Europe/Berlin');
		$this->assertConfig('system', 'language', 'de');
	}


	/**
	 * @medium
	 */
	public function testWithEnvironmentWithoutSave()
	{
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));

		$txt = $this->execute(['autoinstall']);

		$this->assertFinished($txt, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertConfig('database', 'hostname', '');
		$this->assertConfig('database', 'username', '');
		$this->assertConfig('database', 'database', '');
		$this->assertConfig('config', 'admin_email', 'admin@friendica.local');
		$this->assertConfig('system', 'default_timezone', 'Europe/Berlin');
		$this->assertConfig('system', 'language', 'de');
	}

	/**
	 * @medium
	 */
	public function testWithArguments()
	{
		$args = ['autoinstall'];
		array_push($args, '--dbhost');
		array_push($args, $this->db_host);
		array_push($args, '--dbuser');
		array_push($args, $this->db_user);
		if (!empty($this->db_pass)) {
			array_push($args, '--dbpass');
			array_push($args, $this->db_pass);
		}
		if (!empty($this->db_port)) {
			array_push($args, '--dbport');
			array_push($args, $this->db_port);
		}
		array_push($args, '--dbdata');
		array_push($args, $this->db_data);

		array_push($args, '--admin');
		array_push($args, 'admin@friendica.local');
		array_push($args, '--tz');
		array_push($args, 'Europe/Berlin');
		array_push($args, '--lang');
		array_push($args, 'de');

		$txt = $this->execute($args);

		$this->assertFinished($txt, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));

		$this->assertConfig('database', 'hostname', $this->db_host . (!empty($this->db_port) ? ':' . $this->db_port : ''));
		$this->assertConfig('database', 'username', $this->db_user);
		$this->assertConfig('database', 'database', $this->db_data);
		$this->assertConfig('config', 'admin_email', 'admin@friendica.local');
		$this->assertConfig('system', 'default_timezone', 'Europe/Berlin');
		$this->assertConfig('system', 'language', 'de');
	}

	public function testNoDatabaseConnection()
	{
		$this->assertTrue(putenv('MYSQL_USERNAME='));
		$this->assertTrue(putenv('MYSQL_PASSWORD='));
		$this->assertTrue(putenv('MYSQL_DATABASE='));

		$txt = $this->execute(['autoinstall']);

		$this->assertStuckDB($txt);
	}

	public function testGetHelp()
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a] [-f]

Description
    Installs Friendica with data based on the local.ini.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-?           Show help information
    -v                     Show more debug information.
    -a                     All setup checks are required (except .htaccess)
    -f|--file <config>     prepared config file (e.g. "config/local.ini.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb            Save the DB credentials to the file (if environment variables is used)
    -H|--dbhost <host>     The host of the mysql/mariadb database (env MYSQL_HOST)
    -p|--dbport <port>     The port of the mysql/mariadb database (env MYSQL_PORT)
    -d|--dbdata <database> The name of the mysql/mariadb database (env MYSQL_DATABASE)
    -U|--dbuser <username> The username of the mysql/mariadb database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass <password> The password of the mysql/mariadb database login (env MYSQL_PASSWORD)
    -b|--phppath <path>    The path of the PHP binary (env FRIENDICA_PHP_PATH) 
    -A|--admin <mail>      The admin email address of Friendica (env FRIENDICA_ADMIN_MAIL)
    -T|--tz <timezone>     The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang <language>   The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql/mariadb database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql/mariadb database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql/mariadb database login (MYSQL_USERNAME is for mysql, MYSQL_USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql/mariadb database login
   MYSQL_DATABASE              The name of the mysql/mariadb database
   FRIENDICA_PHP_PATH          The path of the PHP binary
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica (this email will be used for admin access)
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

		$txt = $this->execute(['autoinstall', '-h']);

		$this->assertEquals($txt, $theHelp);
	}
}

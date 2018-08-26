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

	private function assertFinished($txt, $withconfig = false)
	{
		$cfg = '';

		if ($withconfig) {
			$cfg = <<<CFG


Creating config file...
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

		vfsStream::newFile('local.ini.php')
			->at($this->root->getChild('config'))
			->setContent($config);

		$txt = $this->execute(['autoinstall', '-f', 'config/local.ini.php']);

		$this->assertFinished($txt);
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
}

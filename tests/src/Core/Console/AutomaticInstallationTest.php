<?php

namespace Friendica\Test\src\Core\Console;

use PHPUnit\Framework\TestCase;

class AutomaticInstallationTest extends TestCase
{
	private $db_host;
	private $db_port;
	private $db_data;
	private $db_user;
	private $db_pass;

	private $db_cmd;

	public function setUp()
	{
		parent::setUp();

		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		$this->db_host = getenv('MYSQL_HOST');
		$this->db_port = (!empty(getenv('MYSQL_PORT'))) ? getenv('MYSQL_PORT') : '3306';
		$this->db_user = getenv('MYSQL_USERNAME');
		$this->db_pass = getenv('MYSQL_PASSWORD');
		$this->db_data = getenv('MYSQL_DATABASE');

		$this->db_cmd = 'MYSQL_PWD=' . $this->db_pass . ' mysql -u' . $this->db_user . ' -h' . $this->db_host . ' -P' . $this->db_port . ' ';

		$this->resetFriendica();
	}

	public function tearDown()
	{
		parent::tearDown();

		$this->resetFriendica();
	}

	private function resetFriendica() {
		$cmd = escapeshellcmd($this->db_cmd . '-e "drop database friendica"');
		shell_exec($cmd);

		$cmd = escapeshellcmd($this->db_cmd . '-e "create database friendica"');
		shell_exec($cmd);

		if (file_exists('config/local.ini.php')) {
			unlink('config/local.ini.php');
		}
	}

	private function assertConfig($family, $key, $value) {
		$cmd = escapeshellcmd("php bin/console.php config " . $family . " " . $key);
		$shellVal = shell_exec($cmd);
		$this->assertEquals($family . "." . $key . " => " . $value . "\n", $shellVal);
	}

	private function assertFinished($txt) {
		$finished = "Installation is finished\n\n";
		$length = strlen($finished );

		$lasttext = substr($txt, -$length);

		$this->assertEquals($finished, $lasttext);
	}

	private function assertStuckDB($txt) {
		$finished = "Checking database...\n\n";
		$length = strlen($finished );

		$lasttext = substr($txt, -$length);

		$this->assertEquals($finished, $lasttext);
	}

	/**
	 * @medium
	 */
	public function testWithConfig() {
		$file = 'config/local.ini.php';

		$config = <<<CONF
<?php return <<<INI

[database]
hostname = $this->db_host
username = $this->db_user
password = $this->db_pass
database = $this->db_data
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

		file_put_contents($file, $config, FILE_APPEND | LOCK_EX);

		$cmd = escapeshellcmd("php bin/console.php autoinstall -f 'config/local.ini.php'");
		$txt = shell_exec($cmd);

		$this->assertFinished($txt);
	}

	/**
	 * @medium
	 */
	public function testWithEnvironmentAndSave() {
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));

		$cmd = escapeshellcmd("php bin/console.php autoinstall --saveenv");
		$txt = shell_exec($cmd);

		$this->assertFinished($txt);

		$this->assertConfig('database', 'hostname', $this->db_host);
		$this->assertConfig('database', 'username', $this->db_user);
		$this->assertConfig('database', 'database', $this->db_data);
		$this->assertConfig('config', 'admin_email', 'admin@friendica.local');
		$this->assertConfig('system', 'default_timezone', 'Europe/Berlin');
		$this->assertConfig('system', 'language', 'de');
	}


	/**
	 * @medium
	 */
	public function testWithEnvironmentWithoutSave() {
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=admin@friendica.local'));
		$this->assertTrue(putenv('FRIENDICA_TZ=Europe/Berlin'));
		$this->assertTrue(putenv('FRIENDICA_LANG=de'));

		$cmd = escapeshellcmd("php bin/console.php autoinstall");
		$txt = shell_exec($cmd);

		$this->assertFinished($txt);

		$this->assertConfig('database', 'hostname', '');
		$this->assertConfig('database', 'username', '');
		$this->assertConfig('database', 'database', '');
		$this->assertConfig('config', 'admin_email', '');
		$this->assertConfig('system', 'default_timezone', '');
		$this->assertConfig('system', 'language', '');
	}

	/**
	 * @medium
	 */
	public function testWithArguments() {
		$args  = '--dbhost ' . $this->db_host;
		$args .= ' --dbuser ' . $this->db_user;
		$args .= ' --dbpass ' . $this->db_pass;
		$args .= ' --dbport ' . $this->db_port;
		$args .= ' --dbdata ' . $this->db_data;

		$args .= ' --admin admin@friendica.local';
		$args .= ' --tz Europe/Berlin';
		$args .= ' --lang de';

		$cmd = escapeshellcmd("php bin/console.php autoinstall " . $args);
		$txt = shell_exec($cmd);

		$this->assertFinished($txt);

		$this->assertConfig('database', 'hostname', $this->db_host);
		$this->assertConfig('database', 'username', $this->db_user);
		$this->assertConfig('database', 'database', $this->db_data);
		$this->assertConfig('config', 'admin_email', 'admin@friendica.local');
		$this->assertConfig('system', 'default_timezone', 'Europe/Berlin');
		$this->assertConfig('system', 'language', 'de');
	}

	public function testNoDatabaseConnection() {
		$this->assertTrue(putenv('MYSQL_USERNAME='));
		$this->assertTrue(putenv('MYSQ_PASSWORD='));

		$cmd = escapeshellcmd("php bin/console.php autoinstall");
		$txt = shell_exec($cmd);

		$this->assertStuckDB($txt);
	}
}

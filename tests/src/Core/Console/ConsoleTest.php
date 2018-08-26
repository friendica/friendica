<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Test\Util\Intercept;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

abstract class ConsoleTest extends TestCase
{
	/**
	 * @var MultiUseConsole Extension of the basic Friendica Console for testing purpose
	 */
	private $console;
	/**
	 * @var App The Friendica App
	 */
	protected $app;

	/**
	 * @var vfsStreamDirectory The Stream Directory
	 */
	protected $root;

	protected $stdout;

	protected function setUp()
	{
		parent::setUp();

		Intercept::setUp();

		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		$this->setUpVfsDir();

		// Reusable App object
		$this->app = new App($this->root->url());
		BaseObject::setApp($this->app);
		$this->console = new MultiUseConsole();
	}

	public function execute($args) {
		DBA::disconnect();
		$this->app->reload();

		array_unshift($args, $this->getExecutablePath());
		Intercept::reset();
		$this->console->reset();
		$this->console->parseTestArgv($args);
		$this->console->execute();

		$returnStr = Intercept::$cache;
		Intercept::reset();
		return $returnStr;
	}

	/**
	 * @return string returns the path to the console executable during tests
	 */
	protected function getExecutablePath() {
		return $this->root->getChild('bin' . DIRECTORY_SEPARATOR . 'console.php')->url();
	}

	private function setUpVfsDir() {
		// the used directories inside the App class
		$structure = [
			'config' => [],
			'bin' => []
		];

		// create a virtual directory and copy all needed files and folders to it
		$this->root = vfsStream::setup('friendica', null, $structure);

		$this->setConfigFile('config.ini.php');
		$this->setConfigFile('settings.ini.php');
		$this->setConfigFile('local.ini.php');
		$this->setConfigFile('dbstructure.json');

		// fake console.php for setting an executable
		vfsStream::newFile('console.php')
			->at($this->root->getChild('bin'))
			->setContent('<? php');
	}

	private function setConfigFile($filename)
	{
		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			$filename;

		if (file_exists($file)) {
			vfsStream::newFile($filename)
				->at($this->root->getChild('config'))
				->setContent(file_get_contents($file));
		}
	}
}

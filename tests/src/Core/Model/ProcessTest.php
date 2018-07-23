<?php

namespace Friendica\Test\src\Model;

use dba;
use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Process;
use Friendica\Test\DatabaseTest;

/**
 * @runTestsInSeparateProcesses
 */
class ProcessTest extends DatabaseTest
{
	protected function setUp()
	{
		global $a;
		parent::setUp();

		// Reusable App object
		$this->app = new App(__DIR__.'/../');
		$a = $this->app;

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	public function tearDown()
	{
		dba::delete('process', ['parent' => Process::getID()]);
		dba::delete('process', ['id' => Process::getID()]);
		parent::tearDown();
	}

	private function assertProcessCode($code, $type, $pid, $id) {
		$assertCode = $type
			. ':'
			. str_pad($pid . ':', 8, '0')
			. ':'
			. str_pad($id . ':', 10, '0');

		$this->assertEquals($assertCode, $code, 'Generated Code is not equal to expected.');
	}

	public function testChangedPID() {
		$command = 'test.php';
		Process::start($command, Process::PROC_APP);

		$id = Process::getID();
		$this->assertInternalType('int', $id);

		$this->assertProcessCode(Process::toString(), Process::PROC_APP, getmypid(), Process::getID());

		Process::updatePID(123);
		$this->assertProcessCode(Process::toString(), Process::PROC_APP, 123, Process::getID());

		Process::updatePID(getmypid());
		$this->assertProcessCode(Process::toString(), Process::PROC_APP, getmypid(), Process::getID());
	}

	public function testProcess() {
		$command = 'test.php';
		Process::start($command, Process::PROC_APP);

		$id = Process::getID();
		$this->assertInternalType('int', $id);

		$this->assertProcessCode(Process::toString(), Process::PROC_APP, getmypid(), Process::getID());

		$this->assertTrue(Process::isRunning());
		$this->assertTrue(Process::isRunning($id));
	}

	/**
	 * @expectedException Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testFailedProcessCode() {
		$command = 'test.php';
		Process::start($command, '123456');
	}

	public function testStopProcess() {
		$this->markTestIncomplete('Process::stop() kills php as well');
	}

	public function testParentProcess() {
		$command = 'test.php';
		Process::start($command, Process::PROC_DAEMON);

		$childCommand = "bin/worker.php";
		Process::startChild($childCommand);

		$this->assertProcessCode(Process::toString(), Process::PROC_DAEMON, getmypid(), Process::getID());

		return Process::getID();
	}

	/**
	 * @param int $parentID ID of the parent process
	 *
	 * @depends testParentProcess
	 */
	public function testChildProcess($parentID) {

		$ret = dba::selectFirst('process', ['id'], ['parent' => $parentID]);
		if (!DBM::is_result($ret)) {
			$this->fail('cannot find child process in process table');
		}
		Process::load($ret['id']);

		$this->assertEquals($ret['id'], Process::getID());

		$this->assertProcessCode(Process::toString(), Process::PROC_WORKER, getmypid(), Process::getID());
	}
}

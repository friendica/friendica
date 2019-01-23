<?php
/**
 * BaseObjectTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Util\LoggerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the BaseObject class.
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BaseObjectTest extends TestCase
{

	/**
	 * Create variables used in tests.
	 */
	protected function setUp()
	{
		$this->baseObject = new BaseObject();
	}

	/**
	 * Test the getApp() function.
	 * @return void
	 */
	public function testGetApp()
	{
		$this->assertInstanceOf(App::class, $this->baseObject->getApp());
	}

	/**
	 * Test the setApp() function.
	 * @return void
	 */
	public function testSetApp()
	{
		$logger = LoggerFactory::create('test');
		$app = new App(__DIR__ . '/../../', $logger);
		LoggerFactory::enableTest($logger);
		$this->assertNull($this->baseObject->setApp($app));
		$this->assertEquals($app, $this->baseObject->getApp());
	}
}

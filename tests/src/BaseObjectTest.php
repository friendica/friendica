<?php
/**
 * BaseObjectTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Container;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the BaseObject class.
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
		$container = new Container();
		include __DIR__ . '/../../bin/dev/dependencies.php';
		$app = new App(__DIR__ . '/../../', $container);
		$this->assertNull($this->baseObject->setApp($app));
		$this->assertEquals($app, $this->baseObject->getApp());
	}
}

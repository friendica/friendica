<?php
/**
 * BaseObjectTest class.
 */

namespace Friendica\Test\src;

use Friendica\BaseObject;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\VFSTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the BaseObject class.
 */
class BaseObjectTest extends TestCase
{
	use VFSTrait;
	use AppMockTrait;

	/**
	 * @var BaseObject
	 */
	private $baseObject;

	/**
	 * Test the setApp() and getApp() function.
	 * @return void
	 */
	public function testGetSetApp()
	{
		$baseObject = new BaseObject();
		$this->setUpVfsDir();
		$this->mockApp($this->root);

		$baseObject->setApp($this->app);
		$this->assertEquals($this->app, $baseObject->getApp());
	}

	/**
	 * Test the getApp() function without App
	 * @expectedException \Exception
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testGetAppFailed()
	{
		BaseObject::getApp();
	}
}

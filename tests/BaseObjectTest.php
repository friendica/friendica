<?php
/**
 * BaseObjectTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\BaseObject;
// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}
/**
 * Tests for the BaseObject class.
 */
class BaseObjectTest extends \PHPUnit\Framework\TestCase
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
		$app = new App(__DIR__.'/../');
		$this->assertNull($this->baseObject->setApp($app));
		$this->assertEquals($app, $this->baseObject->getApp());
	}
}

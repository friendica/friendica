<?php
/**
 * BaseObjectTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\BaseObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the BaseObject class.
 */
class BaseObjectTest extends TestCase
{
	/**
	 * @var \Friendica\BaseObject
	 */
	private $baseObject;

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
	 *
	 * @throws \Exception couldn't create app
	 */
	public function testSetApp()
	{
		$app = new App(__DIR__.'/../');
		$this->baseObject->setApp($app);
		$this->assertEquals($app, $this->baseObject->getApp());
	}
}

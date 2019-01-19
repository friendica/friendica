<?php

namespace Friendica\Test\API\Friendica\Notification;

use Friendica\Test\API\ApiTest;

class NotificationTest extends ApiTest
{
	/**
	 * Test the api_friendica_notification() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_friendica_notification('json');
	}

	/**
	 * Test the api_friendica_notification() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$_SESSION['authenticated'] = false;
		api_friendica_notification('json');
	}

	/**
	 * Test the api_friendica_notification() function with an argument count.
	 * @return void
	 */
	public function testWithArgumentCount()
	{
		$this->app->argv = ['api', 'friendica', 'notification'];
		$this->app->argc = count($this->app->argv);
		$result = api_friendica_notification('json');
		$this->assertEquals(['note' => false], $result);
	}

	/**
	 * Test the api_friendica_notification() function with an XML result.
	 * @return void
	 */
	public function testWithXmlResult()
	{
		$this->app->argv = ['api', 'friendica', 'notification'];
		$this->app->argc = count($this->app->argv);
		$result = api_friendica_notification('xml');
		$this->assertXml($result, 'notes');
	}
}

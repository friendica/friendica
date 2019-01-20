<?php

namespace Friendica\Test\API\Favorites;

use Friendica\Test\API\ApiTest;

class CreateDestroyTest extends ApiTest
{
	/**
	 * Test the api_favorites_create_destroy() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'create'];
		$this->app->argc = count($this->app->argv);
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with an invalid ID.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithInvalidId()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'create', '12.json'];
		$this->app->argc = count($this->app->argv);
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with an invalid action.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithInvalidAction()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'change.json'];
		$this->app->argc = count($this->app->argv);
		$_REQUEST['id'] = 1;
		api_favorites_create_destroy('json');
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action.
	 * @return void
	 */
	public function testWithCreateAction()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'create.json'];
		$this->app->argc = count($this->app->argv);
		$_REQUEST['id'] = 3;
		$result = api_favorites_create_destroy('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_favorites_create_destroy() function with the create action and an RSS result.
	 * @return void
	 */
	public function testWithCreateActionAndRss()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'create.rss'];
		$this->app->argc = count($this->app->argv);
		$_REQUEST['id'] = 3;
		$result = api_favorites_create_destroy('rss');
		$this->assertXml($result, 'status');
	}

	/**
	 * Test the api_favorites_create_destroy() function with the destroy action.
	 * @return void
	 */
	public function testWithDestroyAction()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'destroy.json'];
		$this->app->argc = count($this->app->argv);
		$_REQUEST['id'] = 3;
		$result = api_favorites_create_destroy('json');
		$this->assertStatus($result['status']);
	}

	/**
	 * Test the api_favorites_create_destroy() function without an authenticated user.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\ForbiddenException
	 */
	public function testWithoutAuthenticatedUser()
	{
		$this->app->argv = ['api', '1.1', 'favorites', 'create.json'];
		$this->app->argc = count($this->app->argv);
		$_SESSION['authenticated'] = false;
		api_favorites_create_destroy('json');
	}
}

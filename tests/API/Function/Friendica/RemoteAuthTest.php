<?php

namespace Friendica\Test\API\Friendica;

use Friendica\Test\API\ApiTest;

class RemoteAuthTest extends ApiTest
{
	/**
	 * Test the api_friendica_remoteauth() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_friendica_remoteauth();
	}

	/**
	 * Test the api_friendica_remoteauth() function with an URL.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithUrl()
	{
		$_GET['url'] = 'url';
		$_GET['c_url'] = 'url';
		api_friendica_remoteauth();
	}

	/**
	 * Test the api_friendica_remoteauth() function with a correct URL.
	 * @return void
	 */
	public function testWithCorrectUrl()
	{
		$this->markTestIncomplete("We can't use an assertion here because of App->redirect().");
		$_GET['url'] = 'url';
		$_GET['c_url'] = $this->selfUser['nurl'];
		api_friendica_remoteauth();
	}
}

<?php

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Api\Twitter\DirectMessages\Destroy;
use Friendica\Test\src\Module\Api\ApiTest;

class DestroyTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_destroy() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroy()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		(new Destroy(DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run();
	}

	/**
	 * Test the api_direct_messages_destroy() function with the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithVerbose()
	{
		$response = (new Destroy(DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id or parenturi not specified', $json->message);
	}

	/**
	 * Test the api_direct_messages_destroy() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithoutAuthenticatedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		$_SESSION['authenticated'] = false;
		api_direct_messages_destroy('json');
		*/
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithId()
	{
		$this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		(new Destroy(DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'id' => 1
			]);
	}

	/**
	 * Test the api_direct_messages_destroy() with a non-zero ID and the friendica_verbose GET param.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithIdAndVerbose()
	{
		$response = (new Destroy(DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'id'                  => 1,
				'friendica_parenturi' => 'parent_uri',
				'friendica_verbose'   => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('error', $json->result);
		self::assertEquals('message id not in database', $json->message);
	}

	/**
	 * Test the api_direct_messages_destroy() function with a non-zero ID.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesDestroyWithCorrectId()
	{
		$this->loadFixture(__DIR__ . '/../../../../../datasets/mail/mail.fixture.php', DI::dba());
		$ids = DBA::selectToArray('mail', ['id']);
		$id  = $ids[0]['id'];

		$response = (new Destroy(DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run([
				'id'                => $id,
				'friendica_verbose' => true,
			]);

		$json = $this->toJson($response);

		self::assertEquals('ok', $json->result);
		self::assertEquals('message deleted', $json->message);
	}
}

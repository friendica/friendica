<?php

namespace Friendica\Test\src\Module\Api\Twitter\DirectMessages;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Twitter\DirectMessages\Inbox;
use Friendica\Test\src\Module\Api\ApiTest;

class InboxTest extends ApiTest
{
	/**
	 * Test the api_direct_messages_box() function.
	 *
	 * @return void
	 */
	public function testApiDirectMessagesBoxWithInbox()
	{
		$this->loadFixture(__DIR__ . '/../../../../../datasets/mail/mail.fixture.php', DI::dba());

		$directMessage = new DirectMessage(DI::logger(), DI::dba(), DI::twitterUser());

		$response = (new Inbox($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET], ['extension' => 'json']))
			->run();

		$json = $this->toJson($response);

		self::assertGreaterThan(0, count($json));

		foreach ($json as $item) {
			self::assertIsInt($item->id);
			self::assertIsString($item->text);
		}
	}
}

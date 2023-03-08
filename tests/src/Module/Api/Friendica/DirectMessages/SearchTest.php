<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Module\Api\Friendica\DirectMessages;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Module\Api\Friendica\DirectMessages\Search;
use Friendica\Test\src\Module\Api\ApiTest;
use Psr\Log\NullLogger;

class SearchTest extends ApiTest
{
	public function testEmpty()
	{
		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$response = (new Search($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);

		$json = $this->toJson($response);

		$assert          = new \stdClass();
		$assert->result  = 'error';
		$assert->message = 'searchstring not specified';

		self::assertEquals($assert, $json);
	}

	public function testMail()
	{
		$this->loadFixture(__DIR__ . '/../../../../../datasets/mail/mail.fixture.php', DI::dba());

		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$response = (new Search($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'searchstring' => 'item_body'
			]);

		$json = $this->toJson($response);

		self::assertTrue($json->success);

		foreach ($json->search_results as $searchResult) {
			self::assertIsObject($searchResult->sender);
			self::assertIsInt($searchResult->id);
			self::assertIsInt($searchResult->sender_id);
			self::assertIsObject($searchResult->recipient);
		}
	}

	public function testNothingFound()
	{
		$directMessage = new DirectMessage(new NullLogger(), DI::dba(), DI::twitterUser());

		$response = (new Search($directMessage, DI::dba(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'searchstring' => 'test'
			]);

		$json = $this->toJson($response);

		$assert                 = new \stdClass();
		$assert->success        = false;
		$assert->search_results = 'nothing found';

		self::assertEquals($assert, $json);
	}
}

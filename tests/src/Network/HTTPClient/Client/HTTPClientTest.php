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

namespace Friendica\Test\src\Network\HTTPClient\Client;

use Friendica\DI;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTest;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class HTTPClientTest extends MockedTest
{
	use DiceHttpMockHandlerTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		$this->tearDownHandler();

		parent::tearDown();
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/10473#issuecomment-907749093
	 */
	public function testInvalidURI()
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(301, ['Location' => 'https:///']),
		]));

		self::assertFalse(DI::httpClient()->get('https://friendica.local')->isSuccess());
	}

	/**
	 * Test for issue https://github.com/friendica/friendica/issues/11726
	 */
	public function testRedirect()
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(302, ['Location' => 'https://mastodon.social/about']),
			new Response(200, ['Location' => 'https://mastodon.social']),
		]));

		$result = DI::httpClient()->get('https://mastodon.social');
		self::assertEquals('https://mastodon.social', $result->getUrl());
		self::assertEquals('https://mastodon.social/about', $result->getRedirectUrl());
	}
}

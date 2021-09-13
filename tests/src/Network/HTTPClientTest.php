<?php

namespace Friendica\Test\src\Network;

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
}

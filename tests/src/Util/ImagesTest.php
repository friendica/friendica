<?php

namespace Friendica\Test\src\Util;

use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTest;
use Friendica\Util\Images;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class ImagesTest extends MockedTest
{
	use DiceHttpMockHandlerTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	public function dataImages()
	{
		return [
			'image' => [
				'url'     => 'https://pbs.twimg.com/profile_images/2365515285/9re7kx4xmc0eu9ppmado.png',
				'headers' => [
					'Server'                        => 'tsa_b',
					'Content-Type'                  => 'image/png',
					'Cache-Control'                 => 'max-age=604800,must-revalidate',
					'Last-Modified'                 => 'Thu,04Nov201001:42:54GMT',
					'Content-Length'                => '24875',
					'Access-Control-Allow-Origin'   => '*',
					'Access-Control-Expose-Headers' => 'Content-Length',
					'Date'                          => 'Mon,23Aug202112:39:00GMT',
					'Connection'                    => 'keep-alive',
				],
				'data'      => file_get_contents(__DIR__ . '/../../datasets/curl/image.content'),
				'assertion' => [
					'0'    => '400',
					'1'    => '400',
					'2'    => '3',
					'3'    => 'width="400" height="400"',
					'bits' => '8',
					'mime' => 'image/png',
					'size' => '24875',
				]
			],
			'emptyUrl' => [
				'url'       => '',
				'headers'   => [],
				'data'      => '',
				'assertion' => [],
			],
		];
	}

	/**
	 * Test the Images::getInfoFromURL() method (only remote images, not local/relative!)
	 *
	 * @dataProvider dataImages
	 */
	public function testGetInfoFromRemotURL(string $url, array $headers, string $data, array $assertion)
	{
		$this->httpRequestHandler->setHandler(new MockHandler([
			new Response(200, $headers, $data),
		]));

		self::assertArraySubset($assertion, Images::getInfoFromURL($url));
	}
}

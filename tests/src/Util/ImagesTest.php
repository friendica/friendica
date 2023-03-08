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

namespace Friendica\Test\src\Util;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Friendica\Test\DiceHttpMockHandlerTrait;
use Friendica\Test\MockedTest;
use Friendica\Util\Images;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class ImagesTest extends MockedTest
{
	use DiceHttpMockHandlerTrait;
	use ArraySubsetAsserts;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setupHttpMockHandler();
	}

	protected function tearDown(): void
	{
		$this->tearDownFixtures();

		parent::tearDown();
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

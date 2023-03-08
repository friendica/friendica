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

namespace Friendica\Test\src\App;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Test\MockedTest;
use Psr\Log\NullLogger;

class BaseURLTest extends MockedTest
{
	public function dataSystemUrl(): array
	{
		return [
			'default' => [
				'input'     => ['system' => ['url' => 'https://friendica.local',],],
				'server'    => [],
				'assertion' => 'https://friendica.local',
			],
			'subPath' => [
				'input'     => ['system' => ['url' => 'https://friendica.local/subpath',],],
				'server'    => [],
				'assertion' => 'https://friendica.local/subpath',
			],
			'empty' => [
				'input'     => [],
				'server'    => [],
				'assertion' => 'http://localhost',
			],
			'serverArrayStandard' => [
				'input'  => [],
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'REQUEST_URI'  => '/test/it?with=query',
					'QUERY_STRING' => 'pagename=test/it',
				],
				'assertion' => 'https://friendica.server',
			],
			'serverArraySubPath' => [
				'input'  => [],
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=it/now',
				],
				'assertion' => 'https://friendica.server/test',
			],
			'serverArraySubPath2' => [
				'input'  => [],
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=now',
				],
				'assertion' => 'https://friendica.server/test/it',
			],
			'serverArraySubPath3' => [
				'input'  => [],
				'server' => [
					'HTTPS'        => 'on',
					'HTTP_HOST'    => 'friendica.server',
					'REQUEST_URI'  => '/test/it/now?with=query',
					'QUERY_STRING' => 'pagename=test/it/now',
				],
				'assertion' => 'https://friendica.server',
			],
			'serverArrayWithoutQueryString1' => [
				'input'  => [],
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'REQUEST_URI' => '/test/it/now?with=query',
				],
				'assertion' => 'https://friendica.server/test/it/now',
			],
			'serverArrayWithoutQueryString2' => [
				'input'  => [],
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'REQUEST_URI' => '',
				],
				'assertion' => 'https://friendica.server',
			],
			'serverArrayWithoutQueryString3' => [
				'input'  => [],
				'server' => [
					'HTTPS'       => 'on',
					'HTTP_HOST'   => 'friendica.server',
					'REQUEST_URI' => '/',
				],
				'assertion' => 'https://friendica.server',
			],
		];
	}

	/**
	 * @dataProvider dataSystemUrl
	 */
	public function testDetermine(array $input, array $server, string $assertion)
	{
		$origServerGlobal = $_SERVER;

		$_SERVER = array_merge_recursive($_SERVER, $server);
		$config  = new ReadOnlyFileConfig(new Cache($input));

		$baseUrl = new BaseURL($config, new NullLogger(), $server);

		self::assertEquals($assertion, (string)$baseUrl);

		$_SERVER = $origServerGlobal;
	}

	public function dataRemove(): array
	{
		return [
			'same' => [
				'base'      => ['system' => ['url' => 'https://friendica.local',],],
				'origUrl'   => 'https://friendica.local/test/picture.png',
				'assertion' => 'test/picture.png',
			],
			'other' => [
				'base'      => ['system' => ['url' => 'https://friendica.local',],],
				'origUrl'   => 'https://friendica.other/test/picture.png',
				'assertion' => 'https://friendica.other/test/picture.png',
			],
			'samSubPath' => [
				'base'      => ['system' => ['url' => 'https://friendica.local/test',],],
				'origUrl'   => 'https://friendica.local/test/picture.png',
				'assertion' => 'picture.png',
			],
			'otherSubPath' => [
				'base'      => ['system' => ['url' => 'https://friendica.local/test',],],
				'origUrl'   => 'https://friendica.other/test/picture.png',
				'assertion' => 'https://friendica.other/test/picture.png',
			],
		];
	}

	/**
	 * @dataProvider dataRemove
	 */
	public function testRemove(array $base, string $origUrl, string $assertion)
	{
		$config  = new ReadOnlyFileConfig(new Cache($base));
		$baseUrl = new BaseURL($config, new NullLogger());

		self::assertEquals($assertion, $baseUrl->remove($origUrl));
	}

	/**
	 * Test that redirect to external domains fails
	 */
	public function testRedirectException()
	{
		self::expectException(InternalServerErrorException::class);
		self::expectErrorMessage('https://friendica.other is not a relative path, please use System::externalRedirect');

		$config = new ReadOnlyFileConfig(new Cache([
			'system' => [
				'url' => 'https://friendica.local',
			]
		]));
		$baseUrl = new BaseURL($config, new NullLogger());
		$baseUrl->redirect('https://friendica.other');
	}
}

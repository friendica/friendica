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

use Friendica\App\Request;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Test\MockedTest;

class RequestTest extends MockedTest
{
	public function dataServerArray(): array
	{
		return [
			'default' => [
				'server' => ['REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '',
					'forwarded_for_headers' => '',
				],
				'assertion' => '1.2.3.4',
			],
			'proxy_1' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_2' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_CIDR_multiple_proxies' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '10.0.1.1'],
				'config' => [
					'trusted_proxies'       => '10.0.0.0/16, 1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_wrong_CIDR' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '4.5.6.7, 1.2.3.4', 'REMOTE_ADDR' => '10.1.0.1'],
				'config' => [
					'trusted_proxies'       => '10.0.0.0/24, 1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '10.1.0.1',
			],
			'proxy_3' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_1' => [
				'server' => ['HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_2' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4', 'HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED',
				],
				'assertion' => '4.5.6.7',
			],
			'proxy_multiple_header_wrong' => [
				'server' => ['HTTP_X_FORWARDED_FOR' => '1.2.3.4', 'HTTP_X_FORWARDED' => '1.2.3.4, 4.5.6.7', 'REMOTE_ADDR' => '1.2.3.4'],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => '',
				],
				'assertion' => '1.2.3.4',
			],
			'no_remote_addr' => [
				'server' => [],
				'config' => [
					'trusted_proxies'       => '1.2.3.4',
					'forwarded_for_headers' => '',
				],
				'assertion' => '0.0.0.0',
			],
		];
	}

	/**
	 * @dataProvider dataServerArray
	 */
	public function testRemoteAddress(array $server, array $config, string $assertion)
	{
		$configClass = \Mockery::mock(IManageConfigValues::class);
		$configClass->shouldReceive('get')->with('proxy', 'trusted_proxies', '')->andReturn($config['trusted_proxies']);
		$configClass->shouldReceive('get')->with('proxy', 'forwarded_for_headers', Request::DEFAULT_FORWARD_FOR_HEADER)->andReturn($config['forwarded_for_headers']);

		$request = new Request($configClass, $server);

		self::assertEquals($assertion, $request->getRemoteAddress());
	}
}

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

use Friendica\App;
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
	private function assertArguments(array $assert, App\Arguments $arguments)
	{
		self::assertEquals($assert['queryString'], $arguments->getQueryString());
		self::assertEquals($assert['command'], $arguments->getCommand());
		self::assertEquals($assert['argv'], $arguments->getArgv());
		self::assertEquals($assert['argc'], $arguments->getArgc());
		self::assertEquals($assert['method'], $arguments->getMethod());
		self::assertCount($assert['argc'], $arguments->getArgv());
	}

	/**
	 * Test the default argument without any determinations
	 */
	public function testDefault()
	{
		$arguments = new App\Arguments();

		self::assertArguments([
			'queryString' => '',
			'command'     => '',
			'argv'        => [],
			'argc'        => 0,
			'method'      => App\Router::GET
		],
			$arguments);
	}

	public function dataArguments()
	{
		return [
			'withPagename'         => [
				'assert' => [
					'queryString' => 'profile/test/it?arg1=value1&arg2=value2',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=profile/test/it&arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withUnixHomeDir'      => [
				'assert' => [
					'queryString' => '~test/it?arg1=value1&arg2=value2',
					'command'     => '~test/it',
					'argv'        => ['~test', 'it'],
					'argc'        => 2,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=~test/it&arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => '~test/it',
				],
			],
			'withDiasporaHomeDir'  => [
				'assert' => [
					'queryString' => 'u/test/it?arg1=value1&arg2=value2',
					'command'     => 'u/test/it',
					'argv'        => ['u', 'test', 'it'],
					'argc'        => 3,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=u/test/it&arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => 'u/test/it',
				],
			],
			'withTrailingSlash'    => [
				'assert' => [
					'queryString' => 'profile/test/it?arg1=value1&arg2=value2%2F',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=profile/test/it&arg1=value1&arg2=value2/',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withWrongQueryString' => [
				'assert' => [
					'queryString' => 'profile/test/it?wrong=profile%2Ftest%2Fit&arg1=value1&arg2=value2%2F',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'wrong=profile/test/it&arg1=value1&arg2=value2/',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withMissingPageName'  => [
				'assert' => [
					'queryString' => 'notvalid/it?arg1=value1&arg2=value2%2F',
					'command'     => 'notvalid/it',
					'argv'        => ['notvalid', 'it'],
					'argc'        => 2,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=notvalid/it&arg1=value1&arg2=value2/',
				],
				'get'    => [
				],
			],
			'withNothing'  => [
				'assert' => [
					'queryString' => '?arg1=value1&arg2=value2%2F',
					'command'     => '',
					'argv'        => [],
					'argc'        => 0,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'arg1=value1&arg2=value2/',
				],
				'get'    => [
				],
			],
			'withFileExtension'  => [
				'assert' => [
					'queryString' => 'api/call.json',
					'command'     => 'api/call.json',
					'argv'        => ['api', 'call.json'],
					'argc'        => 2,
					'method'      => App\Router::GET,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=api/call.json',
				],
				'get'    => [
					'pagename' => 'api/call.json'
				],
			],
			'withHTTPMethod'  => [
				'assert' => [
					'queryString' => 'api/call.json',
					'command'     => 'api/call.json',
					'argv'        => ['api', 'call.json'],
					'argc'        => 2,
					'method'      => App\Router::POST,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=api/call.json',
					'REQUEST_METHOD' => App\Router::POST,
				],
				'get'    => [
					'pagename' => 'api/call.json'
				],
			],
		];
	}

	/**
	 * Test all variants of argument determination
	 *
	 * @dataProvider dataArguments
	 */
	public function testDetermine(array $assert, array $server, array $get)
	{
		$arguments = (new App\Arguments())
			->determine($server, $get);

		self::assertArguments($assert, $arguments);
	}

	/**
	 * Test if the get/has methods are working for the determined arguments
	 *
	 * @dataProvider dataArguments
	 */
	public function testGetHas(array $assert, array $server, array $get)
	{
		$arguments = (new App\Arguments())
			->determine($server, $get);

		for ($i = 0; $i < $arguments->getArgc(); $i++) {
			self::assertTrue($arguments->has($i));
			self::assertEquals($assert['argv'][$i], $arguments->get($i));
		}

		self::assertFalse($arguments->has($arguments->getArgc()));
		self::assertEmpty($arguments->get($arguments->getArgc()));
		self::assertEquals('default', $arguments->get($arguments->getArgc(), 'default'));
	}

	public function dataStripped()
	{
		return [
			'strippedZRLFirst'  => [
				'assert' => '?arg1=value1',
				'input'  => '&zrl=nope&arg1=value1',
			],
			'strippedZRLLast'   => [
				'assert' => '?arg1=value1',
				'input'  => '&arg1=value1&zrl=nope',
			],
			'strippedZTLMiddle' => [
				'assert' => '?arg1=value1&arg2=value2',
				'input'  => '&arg1=value1&zrl=nope&arg2=value2',
			],
			'strippedOWTFirst'  => [
				'assert' => '?arg1=value1',
				'input'  => '&owt=test&arg1=value1',
			],
			'strippedOWTLast'   => [
				'assert' => '?arg1=value1',
				'input'  => '&arg1=value1&owt=test',
			],
			'strippedOWTMiddle' => [
				'assert' => '?arg1=value1&arg2=value2',
				'input'  => '&arg1=value1&owt=test&arg2=value2',
			],
		];
	}

	/**
	 * Test the ZRL and OWT stripping
	 *
	 * @dataProvider dataStripped
	 */
	public function testStrippedQueries(string $assert, string $input)
	{
		$command = 'test/it';

		$arguments = (new App\Arguments())
			->determine(['QUERY_STRING' => 'pagename=' . $command . $input,], ['pagename' => $command]);

		self::assertEquals($command . $assert, $arguments->getQueryString());
	}

	/**
	 * Test that arguments are immutable
	 */
	public function testImmutable()
	{
		$argument = new App\Arguments();

		$argNew = $argument->determine([], []);

		self::assertNotSame($argument, $argNew);
	}
}

<?php

namespace Friendica\Test\src\App;

use Friendica\App;
use PHPUnit\Framework\TestCase;

class ArgumentsTest extends TestCase
{
	private function assertArguments(array $assert, App\Arguments $arguments)
	{
		$this->assertEquals($assert['queryString'], $arguments->getQueryString());
		$this->assertEquals($assert['command'], $arguments->getCommand());
		$this->assertEquals($assert['argv'], $arguments->getArgv());
		$this->assertEquals($assert['argc'], $arguments->getArgc());
		$this->assertCount($assert['argc'], $arguments->getArgv());
	}

	/**
	 * Test the default argument without any determinations
	 */
	public function testDefault()
	{
		$arguments = new App\Arguments();

		$this->assertArguments([
			'queryString' => '',
			'command'     => '',
			'argv'        => ['home'],
			'argc'        => 1,
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
				],
				'server' => [
					'QUERY_STRING' => 'pagename=profile/test/it?arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withQ'                => [
				'assert' => [
					'queryString' => 'profile/test/it?arg1=value1&arg2=value2',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
				],
				'server' => [
					'QUERY_STRING' => 'q=profile/test/it?arg1=value1&arg2=value2',
				],
				'get'    => [
					'q' => 'profile/test/it',
				],
			],
			'withWrongDelimiter'   => [
				'assert' => [
					'queryString' => 'profile/test/it?arg1=value1&arg2=value2',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
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
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=~test/it?arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => '~test/it',
				],
			],
			'withDiasporaHomeDir'  => [
				'assert' => [
					'queryString' => 'u/test/it?arg1=value1&arg2=value2',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=u/test/it?arg1=value1&arg2=value2',
				],
				'get'    => [
					'pagename' => 'u/test/it',
				],
			],
			'withTrailingSlash'    => [
				'assert' => [
					'queryString' => 'profile/test/it?arg1=value1&arg2=value2/',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=profile/test/it?arg1=value1&arg2=value2/',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withWrongQueryString' => [
				'assert' => [
					// empty query string?!
					'queryString' => '',
					'command'     => 'profile/test/it',
					'argv'        => ['profile', 'test', 'it'],
					'argc'        => 3,
				],
				'server' => [
					'QUERY_STRING' => 'wrong=profile/test/it?arg1=value1&arg2=value2/',
				],
				'get'    => [
					'pagename' => 'profile/test/it',
				],
			],
			'withMissingPageName'  => [
				'assert' => [
					'queryString' => 'test/it?arg1=value1&arg2=value2/',
					'command'     => '',
					'argv'        => [''],
					'argc'        => 1,
				],
				'server' => [
					'QUERY_STRING' => 'pagename=test/it?arg1=value1&arg2=value2/',
				],
				'get'    => [
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

		$this->assertArguments($assert, $arguments);
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
			$this->assertTrue($arguments->has($i));
			$this->assertEquals($assert['argv'][$i], $arguments->get($i));
		}

		$this->assertFalse($arguments->has($arguments->getArgc()));
		$this->assertEmpty($arguments->get($arguments->getArgc()));
		$this->assertEquals('default', $arguments->get($arguments->getArgc(), 'default'));
	}

	public function dataStripped()
	{
		return [
			'strippedZRLFirst'  => [
				'assert' => '?arg1=value1',
				'input'  => '?zrl=nope&arg1=value1',
			],
			'strippedZRLLast'   => [
				'assert' => '?arg1=value1',
				'input'  => '?arg1=value1&zrl=nope',
			],
			'strippedZTLMiddle' => [
				'assert' => '?arg1=value1&arg2=value2',
				'input'  => '?arg1=value1&zrl=nope&arg2=value2',
			],
			'strippedOWTFirst'  => [
				'assert' => '?arg1=value1',
				'input'  => '?owt=test&arg1=value1',
			],
			'strippedOWTLast'   => [
				'assert' => '?arg1=value1',
				'input'  => '?arg1=value1&owt=test',
			],
			'strippedOWTMiddle' => [
				'assert' => '?arg1=value1&arg2=value2',
				'input'  => '?arg1=value1&owt=test&arg2=value2',
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
			->determine(['QUERY_STRING' => 'q=' . $command . $input,], ['pagename' => $command]);

		$this->assertEquals($command . $assert, $arguments->getQueryString());
	}

	/**
	 * Test that arguments are immutable
	 */
	public function testImmutable()
	{
		$argument = new App\Arguments();

		$argNew = $argument->determine([], []);

		$this->assertNotSame($argument, $argNew);
	}
}

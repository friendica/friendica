<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Installer;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\VFSTrait;
use Mockery;
use Mockery\MockInterface;
use phpmock\phpunit\PHPMock;

class InstallerTest extends MockedTestCase
{
	use VFSTrait;
	use PHPMock;

	/**
	 * @var L10n|MockInterface
	 */
	private $l10nMock;
	/**
	 * @var Dice&MockInterface
	 */
	private $dice;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->l10nMock = Mockery::mock(L10n::class);

		/** @var Dice&MockInterface $dice */
		$this->dice = Mockery::mock(Dice::class)->makePartial();
		$this->dice = $this->dice->addRules(include __DIR__ . '/../../../static/dependencies.config.php');

		$this->dice->shouldReceive('create')
				   ->with(L10n::class)
				   ->andReturn($this->l10nMock);

		DI::init($this->dice, true);
	}

	public static function tearDownAfterClass(): void
	{
		// Reset mocking
		global $phpMock;
		$phpMock = [];

		parent::tearDownAfterClass();
	}

	private function mockL10nT(string $text, $times = null)
	{
		$this->l10nMock->shouldReceive('t')->with($text)->andReturn($text)->times($times);
	}

	/**
	 * Mocking the DI::l10n()->t() calls for the function checks
	 *
	 * @param bool $disableTimes if true, the L10, which are just created in case of an error, will be set to false (because the check will succeed)
	 */
	private function mockFunctionL10TCalls(bool $disableTimes = false)
	{
		$this->mockL10nT('Apache mod_rewrite module', 1);
		$this->mockL10nT('PDO or MySQLi PHP module', 1);
		$this->mockL10nT('IntlChar PHP module', 1);
		$this->mockL10nT('Error: The IntlChar module is not installed.', $disableTimes ? 0 : 1);
		$this->mockL10nT('libCurl PHP module', 1);
		$this->mockL10nT('Error: libCURL PHP module required but not installed.', 1);
		$this->mockL10nT('XML PHP module', 1);
		$this->mockL10nT('GD graphics PHP module', 1);
		$this->mockL10nT('Error: GD graphics PHP module with JPEG support required but not installed.', 1);
		$this->mockL10nT('OpenSSL PHP module', 1);
		$this->mockL10nT('Error: openssl PHP module required but not installed.', 1);
		$this->mockL10nT('mb_string PHP module', 1);
		$this->mockL10nT('Error: mb_string PHP module required but not installed.', 1);
		$this->mockL10nT('iconv PHP module', 1);
		$this->mockL10nT('Error: iconv PHP module required but not installed.', 1);
		$this->mockL10nT('POSIX PHP module', 1);
		$this->mockL10nT('Error: POSIX PHP module required but not installed.', 1);
		$this->mockL10nT('JSON PHP module', 1);
		$this->mockL10nT('Error: JSON PHP module required but not installed.', 1);
		$this->mockL10nT('File Information PHP module', 1);
		$this->mockL10nT('Error: File Information PHP module required but not installed.', 1);
		$this->mockL10nT('GNU Multiple Precision PHP module', 1);
		$this->mockL10nT('Error: GNU Multiple Precision PHP module required but not installed.', 1);
		$this->mockL10nT('IDN Functions PHP module', 1);
		$this->mockL10nT('Error: IDN Functions PHP module required but not installed.', 1);
		$this->mockL10nT('Program execution functions', 1);
		$this->mockL10nT('Error: Program execution functions (proc_open) required but not enabled.', 1);
	}

	private function assertCheckExist($position, $title, $help, $status, $required, $assertionArray)
	{
		$exptected = [
			'title'     => $title,
			'status'    => $status,
			'required'  => $required,
			'error_msg' => null,
			'help'      => $help,
		];

		self::assertArrayHasKey($position, $assertionArray);
		self::assertEquals($exptected, $assertionArray[$position]);
	}

	public static function getCheckKeysData(): array
	{
		return [
			'openssl_pkey_new does not exist' => ['openssl_pkey_new', false],
			'openssl_pkey_new does exists'    => ['openssl_pkey_new', true],
		];
	}

	/**
	 * @small
	 *
	 * @dataProvider getCheckKeysData
	 */
	public function testCheckKeys($function, $expected)
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) use ($function, $expected) {
			if ($function_name === $function) {
				return $expected;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$install = new Installer();
		self::assertSame($expected, $install->checkKeys());
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutIntlChar()
	{
		$class_exists = $this->getFunctionMock('Friendica\Core', 'class_exists');
		$class_exists->expects($this->any())->willReturnCallback(function ($class_name) {
			if ($class_name === 'IntlChar') {
				return false;
			}
			return call_user_func_array('\class_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls();

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			2,
			'IntlChar PHP module',
			'Error: The IntlChar module is not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutCurlInit()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'curl_init') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			4,
			'libCurl PHP module',
			'Error: libCURL PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutImagecreateformjpeg()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'imagecreatefromjpeg') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			5,
			'GD graphics PHP module',
			'Error: GD graphics PHP module with JPEG support required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutOpensslpublicencrypt()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'openssl_public_encrypt') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			6,
			'OpenSSL PHP module',
			'Error: openssl PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutMbStrlen()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'mb_strlen') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			7,
			'mb_string PHP module',
			'Error: mb_string PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutIconvStrlen()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'iconv_strlen') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			8,
			'iconv PHP module',
			'Error: iconv PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutPosixkill()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'posix_kill') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			9,
			'POSIX PHP module',
			'Error: POSIX PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutProcOpen()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'proc_open') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			10,
			'Program execution functions',
			'Error: Program execution functions (proc_open) required but not enabled.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutJsonEncode()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'json_encode') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			11,
			'JSON PHP module',
			'Error: JSON PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutFinfoOpen()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'finfo_open') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			12,
			'File Information PHP module',
			'Error: File Information PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctionsWithoutGmpStrval()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'gmp_strval') {
				return false;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(
			13,
			'GNU Multiple Precision PHP module',
			'Error: GNU Multiple Precision PHP module required but not installed.',
			false,
			true,
			$install->getChecks(),
		);
	}

	/**
	 * @small
	 */
	public function testCheckFunctions()
	{
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if (in_array(
				$function_name,
				[
					'curl_init',
					'imagecreatefromjpeg',
					'openssl_public_encrypt',
					'mb_strlen',
					'iconv_strlen',
					'posix_kill',
					'json_encode',
					'finfo_open',
					'gmp_strval',
				],
			)) {
				return true;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->mockFunctionL10TCalls(true);

		$install = new Installer();
		self::assertTrue($install->checkFunctions());
	}

	/**
	 * @small
	 */
	public function testCheckLocalIni()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		self::assertTrue($this->root->hasChild('config/local.config.php'));

		$install = new Installer();
		self::assertTrue($install->checkLocalIni());

		$this->delConfigFile('local.config.php');

		self::assertFalse($this->root->hasChild('config/local.config.php'));

		$install = new Installer();
		self::assertTrue($install->checkLocalIni());
	}

	/**
	 * @small
	 */
	public function testCheckHtAccessFail()
	{
		// Mocking that we can use CURL
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'curl_init') {
				return true;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		// Mocking the CURL Response
		$IHTTPResult = Mockery::mock(ICanHandleHttpResponses::class);
		$IHTTPResult
			->shouldReceive('getReturnCode')
			->andReturn('404');
		$IHTTPResult
			->shouldReceive('getRedirectUrl')
			->andReturn('');
		$IHTTPResult
			->shouldReceive('getError')
			->andReturn('test Error');

		// Mocking the CURL Request
		$networkMock = Mockery::mock(ICanSendHttpRequests::class);
		$networkMock
			->shouldReceive('get')
			->with('https://test/install/testrewrite')
			->andReturn($IHTTPResult);
		$networkMock
			->shouldReceive('get')
			->with('http://test/install/testrewrite')
			->andReturn($IHTTPResult);

		$this->dice->shouldReceive('create')
			 ->with(ICanSendHttpRequests::class)
			 ->andReturn($networkMock);

		DI::init($this->dice, true);

		$install = new Installer();

		self::assertFalse($install->checkHtAccess('https://test'));
		self::assertSame('test Error', $install->getChecks()[0]['error_msg']['msg']);
	}

	/**
	 * @small
	 */
	public function testCheckHtAccessWork()
	{
		// Mocking that we can use CURL
		$function_exists = $this->getFunctionMock('Friendica\Core', 'function_exists');
		$function_exists->expects($this->any())->willReturnCallback(function ($function_name) {
			if ($function_name === 'curl_init') {
				return true;
			}
			return call_user_func_array('\function_exists', func_get_args());
		});

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		// Mocking the failed CURL Response
		$IHTTPResultF = Mockery::mock(ICanHandleHttpResponses::class);
		$IHTTPResultF
			->shouldReceive('getReturnCode')
			->andReturn('404');

		// Mocking the working CURL Response
		$IHTTPResultW = Mockery::mock(ICanHandleHttpResponses::class);
		$IHTTPResultW
			->shouldReceive('getReturnCode')
			->andReturn('204');

		// Mocking the CURL Request
		$networkMock = Mockery::mock(ICanSendHttpRequests::class);
		$networkMock
			->shouldReceive('get')
			->with('https://test/install/testrewrite')
			->andReturn($IHTTPResultF);
		$networkMock
			->shouldReceive('get')
			->with('http://test/install/testrewrite')
			->andReturn($IHTTPResultW);

		$this->dice->shouldReceive('create')
				   ->with(ICanSendHttpRequests::class)
				   ->andReturn($networkMock);

		DI::init($this->dice, true);

		$install = new Installer();

		self::assertTrue($install->checkHtAccess('https://test'));
	}

	public function testImagickNotInstalled()
	{
		$class_exists = $this->getFunctionMock('Friendica\Core', 'class_exists');
		$class_exists->expects($this->any())->willReturnCallback(function ($class_name) {
			if ($class_name === 'Imagick') {
				return false;
			}
			return call_user_func_array('\class_exists', func_get_args());
		});
		$this->mockL10nT('ImageMagick PHP extension is not installed');

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		self::assertTrue($install->checkImagick());
		self::assertCheckExist(
			0,
			'ImageMagick PHP extension is not installed',
			'',
			false,
			false,
			$install->getChecks(),
		);
	}

	/**
	 * Test the setup of the config cache for installation
	 * @doesNotPerformAssertions
	 */
	public function testSetUpCache()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$install     = new Installer();
		$configCache = Mockery::mock(Cache::class);
		$configCache->shouldReceive('set')->with('config', 'php_path', Mockery::any())->once();
		$configCache->shouldReceive('set')->with('system', 'basepath', '/test/')->once();

		$install->setUpCache($configCache, '/test/');
	}
}

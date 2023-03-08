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

/// @todo this is in the same namespace as Install for mocking 'function_exists'
namespace Friendica\Core;

use Dice\Dice;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\DI;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Mockery;
use Mockery\MockInterface;

class InstallerTest extends MockedTest
{
	use VFSTrait;
	use ArraySubsetAsserts;

	/**
	 * @var L10n|MockInterface
	 */
	private $l10nMock;
	/**
	 * @var Dice|MockInterface
	 */
	private $dice;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->l10nMock = Mockery::mock(L10n::class);

		/** @var Dice|MockInterface $dice */
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
	 */
	private function mockFunctionL10TCalls()
	{
		$this->mockL10nT('Apache mod_rewrite module', 1);
		$this->mockL10nT('PDO or MySQLi PHP module', 1);
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
		$this->mockL10nT('Program execution functions', 1);
		$this->mockL10nT('Error: Program execution functions (proc_open) required but not enabled.', 1);
	}

	private function assertCheckExist($position, $title, $help, $status, $required, $assertionArray)
	{
		$subSet = [$position => [
			'title' => $title,
			'status' => $status,
			'required' => $required,
			'error_msg' => null,
			'help' => $help]
		];

		self::assertArraySubset($subSet, $assertionArray, false, "expected subset: " . PHP_EOL . print_r($subSet, true) . PHP_EOL . "current subset: " . print_r($assertionArray, true));
	}

	/**
	 * Replaces function_exists results with given mocks
	 *
	 * @param array $functions a list from function names and their result
	 */
	private function setFunctions(array $functions)
	{
		global $phpMock;
		$phpMock['function_exists'] = function($function) use ($functions) {
			foreach ($functions as $name => $value) {
				if ($function == $name) {
					return $value;
				}
			}
			return '__phpunit_continue__';
		};
	}

	/**
	 * Replaces class_exist results with given mocks
	 *
	 * @param array $classes a list from class names and their results
	 */
	private function setClasses(array $classes)
	{
		global $phpMock;
		$phpMock['class_exists'] = function($class) use ($classes) {
			foreach ($classes as $name => $value) {
				if ($class == $name) {
					return $value;
				}
			}
			return '__phpunit_continue__';
		};
	}

	/**
	 * @small
	 */
	public function testCheckKeys()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->setFunctions(['openssl_pkey_new' => false]);
		$install = new Installer();
		self::assertFalse($install->checkKeys());

		$this->setFunctions(['openssl_pkey_new' => true]);
		$install = new Installer();
		self::assertTrue($install->checkKeys());
	}

	/**
	 * @small
	 */
	public function testCheckFunctions()
	{
		$this->mockFunctionL10TCalls();
		$this->setFunctions(['curl_init' => false, 'imagecreatefromjpeg' => true]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(3,
			'libCurl PHP module',
			'Error: libCURL PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['imagecreatefromjpeg' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(4,
			'GD graphics PHP module',
			'Error: GD graphics PHP module with JPEG support required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['openssl_public_encrypt' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(5,
			'OpenSSL PHP module',
			'Error: openssl PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['mb_strlen' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(6,
			'mb_string PHP module',
			'Error: mb_string PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['iconv_strlen' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(7,
			'iconv PHP module',
			'Error: iconv PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['posix_kill' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(8,
			'POSIX PHP module',
			'Error: POSIX PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['proc_open' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(9,
			'Program execution functions',
			'Error: Program execution functions (proc_open) required but not enabled.',
			false,
			true,
			$install->getChecks());
		$this->mockFunctionL10TCalls();
		$this->setFunctions(['json_encode' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(10,
			'JSON PHP module',
			'Error: JSON PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['finfo_open' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(11,
			'File Information PHP module',
			'Error: File Information PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['gmp_strval' => false]);
		$install = new Installer();
		self::assertFalse($install->checkFunctions());
		self::assertCheckExist(12,
			'GNU Multiple Precision PHP module',
			'Error: GNU Multiple Precision PHP module required but not installed.',
		false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions([
			'curl_init' => true,
			'imagecreatefromjpeg' => true,
			'openssl_public_encrypt' => true,
			'mb_strlen' => true,
			'iconv_strlen' => true,
			'posix_kill' => true,
			'json_encode' => true,
			'finfo_open' => true,
			'gmp_strval' => true,
		]);
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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testCheckHtAccessFail()
	{
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
			->shouldReceive('fetchFull')
			->with('https://test/install/testrewrite')
			->andReturn($IHTTPResult);
		$networkMock
			->shouldReceive('fetchFull')
			->with('http://test/install/testrewrite')
			->andReturn($IHTTPResult);

		$this->dice->shouldReceive('create')
		     ->with(ICanSendHttpRequests::class)
		     ->andReturn($networkMock);

		DI::init($this->dice, true);

		// Mocking that we can use CURL
		$this->setFunctions(['curl_init' => true]);

		$install = new Installer();

		self::assertFalse($install->checkHtAccess('https://test'));
		self::assertSame('test Error', $install->getChecks()[0]['error_msg']['msg']);
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testCheckHtAccessWork()
	{
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
			->shouldReceive('fetchFull')
			->with('https://test/install/testrewrite')
			->andReturn($IHTTPResultF);
		$networkMock
			->shouldReceive('fetchFull')
			->with('http://test/install/testrewrite')
			->andReturn($IHTTPResultW);

		$this->dice->shouldReceive('create')
		           ->with(ICanSendHttpRequests::class)
		           ->andReturn($networkMock);

		DI::init($this->dice, true);

		// Mocking that we can use CURL
		$this->setFunctions(['curl_init' => true]);

		$install = new Installer();

		self::assertTrue($install->checkHtAccess('https://test'));
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testImagick()
	{
		static::markTestIncomplete('needs adapted class_exists() mock');

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->setClasses(['Imagick' => true]);

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		self::assertTrue($install->checkImagick());

		self::assertCheckExist(1,
			$this->l10nMock->t('ImageMagick supports GIF'),
			'',
			true,
			false,
			$install->getChecks());
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testImagickNotFound()
	{
		static::markTestIncomplete('Disabled due not working/difficult mocking global functions - needs more care!');

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->setClasses(['Imagick' => true]);

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		self::assertTrue($install->checkImagick());
		self::assertCheckExist(1,
			$this->l10nMock->t('ImageMagick supports GIF'),
			'',
			false,
			false,
			$install->getChecks());
	}

	public function testImagickNotInstalled()
	{
		$this->setClasses(['Imagick' => false]);
		$this->mockL10nT('ImageMagick PHP extension is not installed');

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		self::assertTrue($install->checkImagick());
		self::assertCheckExist(0,
			'ImageMagick PHP extension is not installed',
			'',
			false,
			false,
			$install->getChecks());
	}

	/**
	 * Test the setup of the config cache for installation
	 * @doesNotPerformAssertions
	 */
	public function testSetUpCache()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$install = new Installer();
		$configCache = Mockery::mock(Cache::class);
		$configCache->shouldReceive('set')->with('config', 'php_path', Mockery::any())->once();
		$configCache->shouldReceive('set')->with('system', 'basepath', '/test/')->once();

		$install->setUpCache($configCache, '/test/');
	}
}

/**
 * A workaround to replace the PHP native function_exists with a mocked function
 *
 * @param string $function_name the Name of the function
 *
 * @return bool true or false
 */
function function_exists(string $function_name)
{
	global $phpMock;
	if (isset($phpMock['function_exists'])) {
		$result = call_user_func_array($phpMock['function_exists'], func_get_args());
		if ($result !== '__phpunit_continue__') {
			return $result;
		}
	}
	return call_user_func_array('\function_exists', func_get_args());
}

function class_exists($class_name)
{
	global $phpMock;
	if (isset($phpMock['class_exists'])) {
		$result = call_user_func_array($phpMock['class_exists'], func_get_args());
		if ($result !== '__phpunit_continue__') {
			return $result;
		}
	}
	return call_user_func_array('\class_exists', func_get_args());
}

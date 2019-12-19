<?php

// this is in the same namespace as Install for mocking 'function_exists'
namespace Friendica\Core;

use Dice\Dice;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\DI;
use Friendica\Network\CurlResult;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Network;
use Mockery\MockInterface;

class InstallerTest extends MockedTest
{
	use VFSTrait;

	/**
	 * @var \Friendica\Core\L10n\L10n|MockInterface
	 */
	private $l10nMock;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->l10nMock = \Mockery::mock(\Friendica\Core\L10n\L10n::class);

		/** @var Dice|MockInterface $dice */
		$dice = \Mockery::mock(Dice::class)->makePartial();
		$dice = $dice->addRules(include __DIR__ . '/../../../static/dependencies.config.php');

		$dice->shouldReceive('create')
		           ->with(\Friendica\Core\L10n\L10n::class, [])
		           ->andReturn($this->l10nMock);

		DI::init($dice);
	}

	private function mockL10nT(string $text, $times = null)
	{
		$this->l10nMock->shouldReceive('t')->with($text)->andReturn($text)->times($times);
	}

	/**
	 * Mocking the L10n::t() calls for the function checks
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
	}

	private function assertCheckExist($position, $title, $help, $status, $required, $assertionArray)
	{
		$this->assertArraySubset([$position => [
			'title' => $title,
			'status' => $status,
			'required' => $required,
			'error_msg' => null,
			'help' => $help]
		], $assertionArray);
	}

	/**
	 * Replaces function_exists results with given mocks
	 *
	 * @param array $functions a list from function names and their result
	 */
	private function setFunctions($functions)
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
	private function setClasses($classes)
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
		$this->assertFalse($install->checkKeys());

		$this->setFunctions(['openssl_pkey_new' => true]);
		$install = new Installer();
		$this->assertTrue($install->checkKeys());
	}

	/**
	 * @small
	 */
	public function testCheckFunctions()
	{
		$this->mockFunctionL10TCalls();
		$this->setFunctions(['curl_init' => false, 'imagecreatefromjpeg' => true]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(3,
			'libCurl PHP module',
			'Error: libCURL PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['imagecreatefromjpeg' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(4,
			'GD graphics PHP module',
			'Error: GD graphics PHP module with JPEG support required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['openssl_public_encrypt' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(5,
			'OpenSSL PHP module',
			'Error: openssl PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['mb_strlen' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(6,
			'mb_string PHP module',
			'Error: mb_string PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['iconv_strlen' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(7,
			'iconv PHP module',
			'Error: iconv PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['posix_kill' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(8,
			'POSIX PHP module',
			'Error: POSIX PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['json_encode' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(9,
			'JSON PHP module',
			'Error: JSON PHP module required but not installed.',
			false,
			true,
			$install->getChecks());

		$this->mockFunctionL10TCalls();
		$this->setFunctions(['finfo_open' => false]);
		$install = new Installer();
		$this->assertFalse($install->checkFunctions());
		$this->assertCheckExist(10,
			'File Information PHP module',
			'Error: File Information PHP module required but not installed.',
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
		]);
		$install = new Installer();
		$this->assertTrue($install->checkFunctions());
	}

	/**
	 * @small
	 */
	public function testCheckLocalIni()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->assertTrue($this->root->hasChild('config/local.config.php'));

		$install = new Installer();
		$this->assertTrue($install->checkLocalIni());

		$this->delConfigFile('local.config.php');

		$this->assertFalse($this->root->hasChild('config/local.config.php'));

		$install = new Installer();
		$this->assertTrue($install->checkLocalIni());
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
		$curlResult = \Mockery::mock(CurlResult::class);
		$curlResult
			->shouldReceive('getReturnCode')
			->andReturn('404');
		$curlResult
			->shouldReceive('getRedirectUrl')
			->andReturn('');
		$curlResult
			->shouldReceive('getError')
			->andReturn('test Error');

		// Mocking the CURL Request
		$networkMock = \Mockery::mock('alias:' . Network::class);
		$networkMock
			->shouldReceive('fetchUrlFull')
			->with('https://test/install/testrewrite')
			->andReturn($curlResult);
		$networkMock
			->shouldReceive('fetchUrlFull')
			->with('http://test/install/testrewrite')
			->andReturn($curlResult);

		// Mocking that we can use CURL
		$this->setFunctions(['curl_init' => true]);

		$install = new Installer();

		$this->assertFalse($install->checkHtAccess('https://test'));
		$this->assertSame('test Error', $install->getChecks()[0]['error_msg']['msg']);
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
		$curlResultF = \Mockery::mock(CurlResult::class);
		$curlResultF
			->shouldReceive('getReturnCode')
			->andReturn('404');

		// Mocking the working CURL Response
		$curlResultW = \Mockery::mock(CurlResult::class);
		$curlResultW
			->shouldReceive('getReturnCode')
			->andReturn('204');

		// Mocking the CURL Request
		$networkMock = \Mockery::mock('alias:' . Network::class);
		$networkMock
			->shouldReceive('fetchUrlFull')
			->with('https://test/install/testrewrite')
			->andReturn($curlResultF);
		$networkMock
			->shouldReceive('fetchUrlFull')
			->with('http://test/install/testrewrite')
			->andReturn($curlResultW);

		// Mocking that we can use CURL
		$this->setFunctions(['curl_init' => true]);

		$install = new Installer();

		$this->assertTrue($install->checkHtAccess('https://test'));
	}

	/**
	 * @small
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testImagick()
	{
		$this->markTestIncomplete('needs adapted class_exists() mock');

		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->setClasses(['Imagick' => true]);

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		$this->assertTrue($install->checkImagick());

		$this->assertCheckExist(1,
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
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$this->setClasses(['Imagick' => true]);

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		$this->assertTrue($install->checkImagick());
		$this->assertCheckExist(1,
			$this->l10nMock->t('ImageMagick supports GIF'),
			'',
			true,
			false,
			$install->getChecks());
	}

	public function testImagickNotInstalled()
	{
		$this->setClasses(['Imagick' => false]);
		$this->mockL10nT('ImageMagick PHP extension is not installed');

		$install = new Installer();

		// even there is no supported type, Imagick should return true (because it is not required)
		$this->assertTrue($install->checkImagick());
		$this->assertCheckExist(0,
			'ImageMagick PHP extension is not installed',
			'',
			false,
			false,
			$install->getChecks());
	}

	/**
	 * Test the setup of the config cache for installation
	 */
	public function testSetUpCache()
	{
		$this->l10nMock->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$install = new Installer();
		$configCache = \Mockery::mock(ConfigCache::class);
		$configCache->shouldReceive('set')->with('config', 'php_path', \Mockery::any())->once();
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
function function_exists($function_name)
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

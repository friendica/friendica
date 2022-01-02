<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Detection\MobileDetect;
use Friendica\App\Arguments;
use Friendica\App\Mode;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Mockery;
use Mockery\MockInterface;

class ModeTest extends MockedTest
{
	use VFSTrait;

	/**
	 * @var BasePath|MockInterface
	 */
	private $basePathMock;

	/**
	 * @var Database|MockInterface
	 */
	private $databaseMock;

	/**
	 * @var Cache|MockInterface
	 */
	private $configCacheMock;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->basePathMock    = Mockery::mock(BasePath::class);
		$this->databaseMock    = Mockery::mock(Database::class);
		$this->configCacheMock = Mockery::mock(Cache::class);
	}

	public function testItEmpty()
	{
		$mode = new Mode();
		self::assertTrue($mode->isInstall());
		self::assertFalse($mode->isNormal());
	}

	public function testWithoutConfig()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		self::assertTrue($this->root->hasChild('config/local.config.php'));

		$this->delConfigFile('local.config.php');

		self::assertFalse($this->root->hasChild('config/local.config.php'));

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertTrue($mode->isInstall());
		self::assertFalse($mode->isNormal());

		self::assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithoutDatabase()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock->shouldReceive('connected')->andReturn(false)->once();

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertFalse($mode->isNormal());
		self::assertTrue($mode->isInstall());

		self::assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertFalse($mode->has(Mode::DBAVAILABLE));
	}

	public function testWithoutDatabaseSetup()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
						   ->with('SHOW TABLES LIKE \'config\'')->andReturn(false)->once();

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertFalse($mode->isNormal());
		self::assertTrue($mode->isInstall());

		self::assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithMaintenanceMode()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
						   ->with('SHOW TABLES LIKE \'config\'')->andReturn(true)->once();
		$this->configCacheMock->shouldReceive('get')->with('system', 'maintenance')
							  ->andReturn(true)->once();

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertFalse($mode->isNormal());
		self::assertFalse($mode->isInstall());

		self::assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testNormalMode()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
						   ->with('SHOW TABLES LIKE \'config\'')->andReturn(true)->once();
		$this->configCacheMock->shouldReceive('get')->with('system', 'maintenance')
							  ->andReturn(false)->once();
		$this->databaseMock->shouldReceive('selectFirst')
						   ->with('config', ['v'], ['cat' => 'system', 'k' => 'maintenance'])
						   ->andReturn(['v' => null])->once();

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertTrue($mode->isNormal());
		self::assertFalse($mode->isInstall());

		self::assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}

	/**
	 * Test explicit disabled maintenance (in case you manually disable it)
	 */
	public function testDisabledMaintenance()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
						   ->with('SHOW TABLES LIKE \'config\'')->andReturn(true)->once();
		$this->configCacheMock->shouldReceive('get')->with('system', 'maintenance')
							  ->andReturn(false)->once();
		$this->databaseMock->shouldReceive('selectFirst')
						   ->with('config', ['v'], ['cat' => 'system', 'k' => 'maintenance'])
						   ->andReturn(['v' => '0'])->once();

		$mode = (new Mode())->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertTrue($mode->isNormal());
		self::assertFalse($mode->isInstall());

		self::assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}

	/**
	 * Test that modes are immutable
	 */
	public function testImmutable()
	{
		$this->basePathMock->shouldReceive('getPath')->andReturn(null)->once();

		$mode = new Mode();

		$modeNew = $mode->determine($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		self::assertNotSame($modeNew, $mode);
	}

	/**
	 * Test if not called by index is backend
	 */
	public function testIsBackendNotIsBackend()
	{
		$server       = [];
		$args         = new Arguments();
		$mobileDetect = new MobileDetect();

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertTrue($mode->isBackend());
	}

	/**
	 * Test is called by index but module is backend
	 */
	public function testIsBackendButIndex()
	{
		$server       = [];
		$args         = new Arguments('', '', Mode::BACKEND_MODULES[0]);
		$mobileDetect = new MobileDetect();

		$mode = (new Mode())->determineRunMode(false, $server, $args, $mobileDetect);

		self::assertTrue($mode->isBackend());
	}

	/**
	 * Test is called by index and module is not backend
	 */
	public function testIsNotBackend()
	{
		$server       = [];
		$args         = new Arguments('', '', Arguments::DEFAULT_MODULE);
		$mobileDetect = new MobileDetect();

		$mode = (new Mode())->determineRunMode(false, $server, $args, $mobileDetect);

		self::assertFalse($mode->isBackend());
	}

	/**
	 * Test if the call is an ajax call
	 */
	public function testIsAjax()
	{
		// This is the server environment variable to determine ajax calls
		$server = [
			'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
		];

		$args         = new Arguments('', '', Arguments::DEFAULT_MODULE);
		$mobileDetect = new MobileDetect();

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertTrue($mode->isAjax());
	}

	/**
	 * Test if the call is not nan ajax call
	 */
	public function testIsNotAjax()
	{
		$server       = [];
		$args         = new Arguments('', '', Arguments::DEFAULT_MODULE);
		$mobileDetect = new MobileDetect();

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertFalse($mode->isAjax());
	}

	/**
	 * Test if the call is a mobile and is a tablet call
	 */
	public function testIsMobileIsTablet()
	{
		$server       = [];
		$args         = new Arguments('', '', Arguments::DEFAULT_MODULE);
		$mobileDetect = Mockery::mock(MobileDetect::class);
		$mobileDetect->shouldReceive('isMobile')->andReturn(true);
		$mobileDetect->shouldReceive('isTablet')->andReturn(true);

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertTrue($mode->isMobile());
		self::assertTrue($mode->isTablet());
	}


	/**
	 * Test if the call is not a mobile and is not a tablet call
	 */
	public function testIsNotMobileIsNotTablet()
	{
		$server       = [];
		$args         = new Arguments('', '', Arguments::DEFAULT_MODULE);
		$mobileDetect = Mockery::mock(MobileDetect::class);
		$mobileDetect->shouldReceive('isMobile')->andReturn(false);
		$mobileDetect->shouldReceive('isTablet')->andReturn(false);

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertFalse($mode->isMobile());
		self::assertFalse($mode->isTablet());
	}
}

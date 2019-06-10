<?php

namespace Friendica\Test\src\App;

use Friendica\App\Mode;
use Friendica\Core\Config;
use Friendica\Database\Database;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\DBAMockTrait;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Mockery\MockInterface;

class ModeTest extends MockedTest
{
	use VFSTrait;
	use DBAMockTrait;

	/**
	 * @var BasePath|MockInterface
	 */
	private $basePathMock;

	/**
	 * @var Database|MockInterface
	 */
	private $databaseMock;

	/**
	 * @var Config\Cache\ConfigCache|MockInterface
	 */
	private $configCacheMock;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->basePathMock = \Mockery::mock(BasePath::class);
		$this->basePathMock->shouldReceive('getPath')->andReturn($this->root->url())->once();

		$this->databaseMock = \Mockery::mock(Database::class);
		$this->configCacheMock = \Mockery::mock(Config\Cache\ConfigCache::class);
	}

	public function testItEmpty()
	{
		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);
		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());
	}

	public function testWithoutConfig()
	{
		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);

		$this->assertTrue($this->root->hasChild('config/local.config.php'));

		$this->delConfigFile('local.config.php');

		$this->assertFalse($this->root->hasChild('config/local.config.php'));

		$mode->determine();

		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());

		$this->assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithoutDatabase()
	{
		$this->databaseMock->shouldReceive('connected')->andReturn(false)->once();

		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		$this->assertFalse($mode->has(Mode::DBAVAILABLE));
	}

	public function testWithoutDatabaseSetup()
	{
		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
		                   ->with('SHOW TABLES LIKE \'config\'')->andReturn(false)->once();

		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	public function testWithMaintenanceMode()
	{
		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
		                   ->with('SHOW TABLES LIKE \'config\'')->andReturn(true)->once();
		$this->configCacheMock->shouldReceive('get')->with('system', 'maintenance')
			->andReturn(true)->once();

		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testNormalMode()
	{
		$this->databaseMock->shouldReceive('connected')->andReturn(true)->once();
		$this->databaseMock->shouldReceive('fetchFirst')
		                   ->with('SHOW TABLES LIKE \'config\'')->andReturn(true)->once();
		$this->configCacheMock->shouldReceive('get')->with('system', 'maintenance')
		                      ->andReturn(false)->once();
		$this->databaseMock->shouldReceive('selectFirst')
		                   ->with('config', ['cat' => 'system', 'key' => 'maintenance'])
		                   ->andReturn(false)->once();

		$mode = new Mode($this->basePathMock, $this->databaseMock, $this->configCacheMock);
		$mode->determine();

		$this->assertTrue($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}
}

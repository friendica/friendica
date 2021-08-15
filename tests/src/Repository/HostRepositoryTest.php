<?php

namespace Friendica\Test\src\Repository;

use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Repository;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class HostRepositoryTest extends MockedTest
{
	/** @var Database|MockInterface */
	private $dba;

	protected function setUp(): void
	{
		parent::setUp();

		$this->dba = \Mockery::mock(Database::class);
	}

	public function testInstance()
	{
		$hostRepo = new Repository\Host($this->dba, new NullLogger());

		$this->assertInstanceOf(Repository\Host::class, $hostRepo);
	}

	public function testExistingHost()
	{
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' => 1, 'name' => gethostname()])->once();

		$hostRepo = new Repository\Host($this->dba, new NullLogger());
		$host     = $hostRepo->selectCurrentHost();

		$this->assertEquals(gethostname(), $host->name);
		$this->assertEquals(1, $host->id);
	}

	public function testHostOverride()
	{
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' => 1, 'name' => 'testserver.test'])->once();

		$hostRepo = new Repository\Host($this->dba, new NullLogger());
		$host     = $hostRepo->selectCurrentHost([Repository\Host::ENV_VARIABLE => 'testserver.test']);

		$this->assertEquals('testserver.test', $host->name);
		$this->assertEquals(1, $host->id);
	}

	public function testExceptionForEmptyHostname()
	{
		$this->expectException(InternalServerErrorException::class);
		$this->expectExceptionMessage('Empty hostname is invalid.');

		$hostRepo = new Repository\Host($this->dba, new NullLogger());
		$hostRepo->selectCurrentHost([Repository\Host::ENV_VARIABLE => ' ']);
	}

	public function testNewHostname()
	{
		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();
		$this->dba->shouldReceive('replace')->once();
		$this->dba->shouldReceive('selectFirst')->andReturn(['id' => 1, 'name' => gethostname()])->once();

		$hostRepo = new Repository\Host($this->dba, new NullLogger());
		$host     = $hostRepo->selectCurrentHost();

		$this->assertEquals(gethostname(), $host->name);
		$this->assertEquals(1, $host->id);
	}

	public function testBadInsert()
	{
		$this->expectException(NotFoundException::class);

		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();
		$this->dba->shouldReceive('replace')->once();
		$this->dba->shouldReceive('selectFirst')->andReturn([])->once();

		$hostRepo = new Repository\Host($this->dba, new NullLogger());
		$hostRepo->selectCurrentHost();
	}
}

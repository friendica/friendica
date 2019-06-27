<?php

namespace Friendica\Test\src\Core\Config\Adapter;

use Friendica\Core\Config\Adapter\IConfigAdapter;
use Friendica\Database\Database;
use Friendica\Test\MockedTest;
use Friendica\Util\Profiler;
use Mockery;

abstract class ConfigAdapterTest extends MockedTest
{
	/** @var Database|Mockery\MockInterface */
	protected $dba;

	protected function setUp()
	{
		// We need the profiler for some saveTimestamp action
		$profiler = Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		$this->dba = Mockery::mock(Database::class)->makePartial();
		$this->dba->setProfiler($profiler);

		// the connect-check of a config class should be just once!
		$this->dba->shouldReceive('connected')->andReturn(true)->once();

		$this->dba->shouldReceive('close')->withAnyArgs()->andReturn(true);
	}

	/**
	 * The data array
	 *
	 * @var array
	 */
	protected $config = [
		['cat' => 'system', 'k' => 'key1', 'v' => 'value1'],
		['cat' => 'system', 'k' => 'key2', 'v' => 'value2'],
		['cat' => 'system', 'k' => 'key3', 'v' => 'value3'],
		['cat' => 'config', 'k' => 'key1', 'v' => 'value1a'],
		['cat' => 'config', 'k' => 'key4', 'v' => 'value4'],
		['cat' => 'other', 'k' => 'key5', 'v' => 'value5'],
		['cat' => 'other', 'k' => 'key6', 'v' => 'value6'],
	];

	/** @return IConfigAdapter */
	abstract protected function getInstance();

	/**
	 * Mock the DBA-calls for a GET operation
	 *
	 * @param string $cat   The category
	 * @param string $key   The key
	 * @param mixed  $value The guessed value
	 * @param int    $times How often the DBA-call should be needed
	 */
	protected function mockGet(string $cat, string $key, $value, int $times = 1)
	{
		$this->dba->shouldReceive('selectFirst')
		          ->with('config', ['v'], ['cat' => $cat, 'k' => $key])
		          ->andReturn(['v' => $value])
		          ->times($times);
	}

	/**
	 * Mock the DBA-calls for a SET operation
	 *
	 * @param string $cat    The category
	 * @param string $key    The key
	 * @param string $value  The guessed value
	 * @param bool   $return True, if the update was successful
	 * @param int    $times  How often the DBA-call should be needed
	 */
	protected function mockSet(string $cat, string $key, $value, bool $return = true, int $times = 1)
	{
		$this->dba->shouldReceive('update')
		          ->with('config', ['v' => $value], ['cat' => $cat, 'k' => $key], true)
		          ->andReturn($return)
		          ->times($times);
	}

	/**
	 * Mock the DBA-calls for a ALL categories
	 *
	 * @param int $times How often the DBA-call should be needed
	 */
	protected function mockLoadAll(int $times = 1)
	{
		$config = $this->config;

		$this->dba->shouldReceive('fetch')
		          ->with('mocked')
		          ->andReturnUsing(function () use (&$config) {
			          $row = current($config);
			          next($config);
			          return $row;
		          });

		$this->dba->shouldReceive('select')
		          ->with('config', ['cat', 'v', 'k'])
		          ->andReturn('mocked')
		          ->times($times);
	}

	/**
	 * Mock the DBA-calls for a category specific loading
	 *
	 * @param string $cat   The category
	 * @param int    $times How often the DBA-call should be needed
	 */
	protected function mockLoad(string $cat, int $times = 1)
	{
		$config = array_filter($this->config, function ($values) use ($cat) {
			return $cat === $values['cat'];
		});

		$this->dba->shouldReceive('fetch')
		          ->with('mocked')
		          ->andReturnUsing(function () use (&$config) {
			          $row = current($config);
			          next($config);
			          return $row;
		          });

		$this->dba->shouldReceive('select')
		          ->with('config', ['v', 'k'], ['cat' => $cat])
		          ->andReturn('mocked')
		          ->times($times);
	}

	/**
	 * Assert a config tree
	 *
	 * @param string         $cat           The category to assert
	 * @param array          $result        The given result set of a test
	 * @param IConfigAdapter $configAdapter the used config Adapter
	 */
	protected function assertConfig(string $cat, array $result, IConfigAdapter $configAdapter)
	{
		$this->assertArrayHasKey($cat, $result);

		foreach ($this->config as $values) {
			if ($values['cat'] === $cat || empty($cat)) {
				$this->assertTrue($configAdapter->isLoaded($values['cat'], $values['k']));
				$this->assertArraySubset([$values['k'] => $values['v']], $result[$cat]);
			}
		}
	}

	/**
	 * Test loading without argument (default is normally config tree)
	 */
	public function testLoadConfig()
	{
		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));

		return ['adapter' => $configAdapter, 'result' => $configAdapter->load()];
	}

	/**
	 * Test loading system config tree
	 */
	public function testLoadSystem()
	{
		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));

		return ['adapter' => $configAdapter, 'result' => $configAdapter->load('system')];

	}

	/**
	 * Test loading a custom, other config tree
	 */
	public function testLoadOther()
	{
		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));
		$this->assertFalse($configAdapter->isLoaded('other', 'key5'));
		$this->assertFalse($configAdapter->isLoaded('other', 'key6'));

		return ['adapter' => $configAdapter, 'result' => $configAdapter->load('other')];
	}

	/**
	 * Test the LOAD behaviour of a disconnected DB
	 */
	public function testLoadDisconnectedDB()
	{
		// fire the first connected check to reset it (because of the setup)
		$this->assertTrue($this->dba->connected());

		$this->dba->shouldReceive('connected')->andReturn(false);

		$configAdapter = $this->getInstance();

		$result = $configAdapter->load();

		// No result!
		$this->assertEmpty($result);
	}

	/**
	 * Test the get method of a JIT Config adapter with different arguments
	 */
	public function testGet()
	{
		// mock system check (one DB call)
		$this->mockGet('system', 'key1', 'value1');
		$this->mockGet('system', 'key2', 'value2');
		$this->mockGet('system', 'key3', 'value3');

		// mock config check (one DB call)
		$this->mockGet('config', 'key1', 'value1a');
		$this->mockGet('config', 'key4', 'value4');

		// mock invalid value
		$this->mockGet('invalid', 'invalid', null);

		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));

		// Test system
		$this->assertEquals('value1', $configAdapter->get('system', 'key1'));
		$this->assertEquals('value2', $configAdapter->get('system', 'key2'));
		$this->assertEquals('value3', $configAdapter->get('system', 'key3'));

		// test config
		$this->assertEquals('value1a', $configAdapter->get('config', 'key1'));
		$this->assertEquals('value4', $configAdapter->get('config', 'key4'));

		// test invalid
		$this->assertEmpty($configAdapter->get('invalid', 'invalid'));

		return $configAdapter;
	}

	/**
	 * Test a simple SET
	 */
	public function testSet()
	{
		$this->mockGet('config', 'key1', null);
		$this->mockSet('config', 'key1', 'value1');

		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertTrue($configAdapter->set('config', 'key1', 'value1'));

		return $configAdapter;
	}

	/**
	 * Test a simple SET
	 */
	public function testNotSet()
	{
		$this->mockGet('config', 'key1', 'value1');

		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertTrue($configAdapter->set('config', 'key1', 'value1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));

		return $configAdapter;
	}
}

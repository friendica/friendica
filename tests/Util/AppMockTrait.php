<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\Util;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Render\FriendicaSmartyEngine;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Trait to Mock the global App instance
 */
trait AppMockTrait
{
	/**
	 * @var MockInterface|App The mocked Friendica\App
	 */
	protected $app;

	/**
	 * @var MockInterface|Config\IConfig The mocked Config Cache
	 */
	protected $configMock;

	/**
	 * @var MockInterface|Profiler The mocked profiler
	 */
	protected $profilerMock;

	/**
	 * @var MockInterface|App\Mode The mocked App mode
	 */
	protected $mode;

	/**
	 * @var MockInterface|Dice The dependency injection library
	 */
	protected $dice;

	/**
	 * Mock the App
	 *
	 * @param vfsStreamDirectory $root The root directory
	 * @param bool               $raw  If true, no config mocking will be done
	 *
	 * @throws InternalServerErrorException
	 */
	public function mockApp(vfsStreamDirectory $root, $raw = false)
	{
		$this->dice = \Mockery::mock(Dice::class)->makePartial();
		$this->dice = $this->dice->addRules(include __DIR__ . '/../../static/dependencies.config.php');

		$this->configMock = \Mockery::mock(Config\Cache\Cache::class);
		$this->dice->shouldReceive('create')
		           ->with(Config\Cache\Cache::class)
		           ->andReturn($this->configMock);
		$this->mode = \Mockery::mock(App\Mode::class);
		$this->dice->shouldReceive('create')
		           ->with(App\Mode::class)
		           ->andReturn($this->mode);
		$configModel= \Mockery::mock(Config\Model\Config::class);
		// Disable the adapter
		$configModel->shouldReceive('isConnected')->andReturn(false);

		$config = new Config\Type\JitConfig($this->configMock, $configModel);
		$this->dice->shouldReceive('create')
		           ->with(Config\IConfig::class)
		           ->andReturn($config);

		// Mocking App and most used functions
		$this->app = \Mockery::mock(App::class);
		$this->dice->shouldReceive('create')
		           ->with(App::class)
		           ->andReturn($this->app);
		$this->app
			->shouldReceive('getBasePath')
			->andReturn($root->url());

		$this->profilerMock = \Mockery::mock(Profiler::class);
		$this->profilerMock->shouldReceive('startRecording');
		$this->profilerMock->shouldReceive('stopRecording');
		$this->profilerMock->shouldReceive('saveTimestamp');
		$this->dice->shouldReceive('create')
		           ->with(Profiler::class)
		           ->andReturn($this->profilerMock);

		$this->app
			->shouldReceive('getConfigCache')
			->andReturn($this->configMock);
		$this->app
			->shouldReceive('getTemplateEngine')
			->andReturn(new FriendicaSmartyEngine('frio', []));
		$this->app
			->shouldReceive('getCurrentTheme')
			->andReturn('Smarty3');
		$this->app->shouldReceive('getThemeInfoValue')
			->with('videowidth')
			->andReturn(425);
		$this->app->shouldReceive('getThemeInfoValue')
			->with('videoheight')
			->andReturn(350);
	
		DI::init($this->dice);

		if ($raw) {
			return;
		}

		$this->configMock
			->shouldReceive('has')
			->andReturn(true);
		$this->configMock
			->shouldReceive('get')
			->with('database', 'hostname')
			->andReturn(getenv('MYSQL_HOST'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'username')
			->andReturn(getenv('MYSQL_USERNAME'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'password')
			->andReturn(getenv('MYSQL_PASSWORD'));
		$this->configMock
			->shouldReceive('get')
			->with('database', 'database')
			->andReturn(getenv('MYSQL_DATABASE'));
		$this->configMock
			->shouldReceive('get')
			->with('config', 'hostname')
			->andReturn('localhost');
		$this->configMock
			->shouldReceive('get')
			->with('system', 'theme')
			->andReturn('system_theme');
	}
}

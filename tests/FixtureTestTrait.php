<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\Router;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Type\Memory;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;

trait FixtureTestTrait
{
	use VFSTrait;
	use DatabaseTestTrait;

	/** @var Dice */
	protected $dice;

	/**
	 * Create variables used by tests.
	 */
	protected function setUpFixtures(): void
	{
		$this->setUpVfsDir();
		$this->setUpDb();

		$server                   = $_SERVER;
		$server['REQUEST_METHOD'] = Router::GET;

		$this->dice = (new Dice())
			->addRules(include __DIR__ . '/../static/dependencies.config.php')
			->addRule(ConfigFileManager::class, [
				'instanceOf' => Config::class,
				'call'       => [['createConfigFileManager', [$this->root->url(), $this->root->url() . '/addon', $server,], Dice::CHAIN_CALL]]])
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true])
			->addRule(IHandleSessions::class, ['instanceOf' => Memory::class, 'shared' => true, 'call' => null])
			->addRule(Arguments::class, [
				'instanceOf' => Arguments::class,
				'call'       => [
					['determine', [$server, $_GET], Dice::CHAIN_CALL],
				],
			]);
		DI::init($this->dice, true);

		$config = $this->dice->create(IManageConfigValues::class);
		$config->set('database', 'disable_pdo', true);

		/** @var Database $dba */
		$dba = $this->dice->create(Database::class);
		$dba->setTestmode(true);

		DBStructure::checkInitialValues();

		// Load the API dataset for the whole API
		$this->loadFixture(__DIR__ . '/datasets/api.fixture.php', $dba);
	}

	protected function tearDownFixtures(): void
	{
		$this->tearDownDb();
	}

	protected function useHttpMethod(string $method = Router::GET)
	{
		$server                   = $_SERVER;
		$server['REQUEST_METHOD'] = $method;

		$this->dice = $this->dice
			->addRule(Arguments::class, [
				'instanceOf' => Arguments::class,
				'call'       => [
					['determine', [$server, $_GET], Dice::CHAIN_CALL],
				],
			]);

		DI::init($this->dice);
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Database\Database;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;

trait CreateDatabaseTrait
{
	use DatabaseTestTrait;
	use VFSTrait;

	/** @var Database|null */
	protected $dba = null;

	public function getDbInstance(): Database
	{
		if (isset($this->dba)) {
			return $this->dba;
		}

		$configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static'
		);
		$config = new ReadOnlyFileConfig(new Cache([
			'database' => [
				'disable_pdo' => true
			],
		]));

		$database = new StaticDatabase($config, (new DbaDefinition($this->root->url()))->load(), (new ViewDefinition($this->root->url()))->load());
		$database->setTestmode(true);

		return $database;
	}
}

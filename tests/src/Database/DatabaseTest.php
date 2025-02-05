<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Database;

use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\FixtureTestCase;
use Friendica\Test\Util\CreateDatabaseTrait;

class DatabaseTest extends FixtureTestCase
{
	use CreateDatabaseTrait;

	/**
	 * @var Cache
	 */
	protected $configCache;
	/**
	 * @var ConfigFileManager
	 */
	protected $configFileManager;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		parent::setUp();

		$this->configCache       = new Cache();
		$this->configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static'
		);
	}

	/**
	 * Test, if directly updating a field is possible
	 */
	public function testUpdateIncrease()
	{
		$db = $this->getDbInstance();

		self::assertTrue($db->insert('config', ['cat' => 'test', 'k' => 'inc', 'v' => 0]));
		self::assertTrue($db->update('config', ["`v` = `v` + 1"], ['cat' => 'test', 'k' => 'inc']));
		self::assertEquals(1, $db->selectFirst('config', ['v'], ['cat' => 'test', 'k' => 'inc'])['v']);
	}

	/**
	 * Test if combining directly field updates with normal updates is working
	 */
	public function testUpdateWithField()
	{
		$db = $this->getDbInstance();

		self::assertEquals('https://friendica.local', $db->selectFirst('gserver', ['url'], ['nurl' => 'http://friendica.local'])['url']);
		self::assertTrue($db->update('gserver', ['active-week-users' => 0], ['nurl' => 'http://friendica.local']));
		self::assertTrue($db->update('gserver', [
			'site_name' => 'test', "`registered-users` = `registered-users` + 1",
			'info'      => 'another test',
			"`active-week-users` = `active-week-users` + 2"
		], [
			'nurl' => 'http://friendica.local'
		]));
		self::assertEquals(1, $db->selectFirst('gserver', ['registered-users'], ['nurl' => 'http://friendica.local'])['registered-users']);
		self::assertEquals(2, $db->selectFirst('gserver', ['active-week-users'], ['nurl' => 'http://friendica.local'])['active-week-users']);
		self::assertTrue($db->update('gserver', [
			'site_name' => 'test', "`registered-users` = `registered-users` + 1",
			'info'      => 'another test'
		], [
			'nurl' => 'http://friendica.local'
		]));
		self::assertEquals(2, $db->selectFirst('gserver', ['registered-users'], ['nurl' => 'http://friendica.local'])['registered-users']);
		self::assertTrue($db->update('gserver', [
			'site_name' => 'test', "`registered-users` = `registered-users` - 1",
			'info'      => 'another test'
		], [
			'nurl' => 'http://friendica.local'
		]));
		self::assertEquals(1, $db->selectFirst('gserver', ['registered-users'], ['nurl' => 'http://friendica.local'])['registered-users']);
	}

	public function testUpdateWithArray()
	{
		$db = $this->getDbInstance();

		self::assertTrue($db->update('gserver', ['active-week-users' => 0, 'registered-users' => 0], ['nurl' => 'http://friendica.local']));

		$fields   = ["`registered-users` = `registered-users` + 1"];
		$fields[] = "`active-week-users` = `active-week-users` + 2";

		self::assertTrue($db->update('gserver', $fields, ['nurl' => 'http://friendica.local']));

		self::assertEquals(2, $db->selectFirst('gserver', ['active-week-users'], ['nurl' => 'http://friendica.local'])['active-week-users']);
		self::assertEquals(1, $db->selectFirst('gserver', ['registered-users'], ['nurl' => 'http://friendica.local'])['registered-users']);
	}
}

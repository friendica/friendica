<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\App;

use Detection\MobileDetect;
use Friendica\App\Arguments;
use Friendica\App\Mode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ModeTest extends TestCase
{
	public function testMethodsReturnCorrectValuesByDefault(): void
	{
		$mode = new Mode();

		self::assertTrue($mode->isInstall());
		self::assertFalse($mode->isNormal());

		self::assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertFalse($mode->has(Mode::DBAVAILABLE));
		self::assertFalse($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testMethodsReturnCorrectValuesAfterDetermineWithoutConfigFile(): void
	{
		$root = vfsStream::setup(__FUNCTION__ . '_friendica', 0777, []);

		$mode = (new Mode())->determine(
			$root->url(),
			self::createStub(Database::class),
			self::createStub(IManageConfigValues::class),
		);

		self::assertTrue($mode->isInstall());
		self::assertFalse($mode->isNormal());

		self::assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertFalse($mode->has(Mode::DBAVAILABLE));
		self::assertFalse($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testMethodsReturnCorrectValuesAfterDetermineWithoutDatabaseConnection(): void
	{
		$root = vfsStream::setup(__FUNCTION__ . '_friendica', 0777, ['config' => [
			'local.config.php' => file_get_contents(realpath(dirname(__FILE__, 4) . '/config/local-sample.config.php')),
		]]);

		$mode = (new Mode())->determine($root->url(), self::createStub(Database::class), self::createStub(IManageConfigValues::class));

		self::assertFalse($mode->isNormal());
		self::assertTrue($mode->isInstall());

		self::assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertFalse($mode->has(Mode::DBAVAILABLE));
		self::assertFalse($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testMethodsReturnCorrectValuesWithMaintenanceMode(): void
	{
		$root = vfsStream::setup(__FUNCTION__ . '_friendica', 0777, ['config' => [
			'local.config.php' => file_get_contents(realpath(dirname(__FILE__, 4) . '/config/local-sample.config.php')),
		]]);

		$database = self::createMock(Database::class);
		$database->expects(self::once())->method('connected')->willReturn(true);

		$config = self::createMock(IManageConfigValues::class);
		$config->expects(self::once())->method('get')->with('system', 'maintenance')->willReturn(true);

		$mode = (new Mode())->determine($root->url(), $database, $config);

		self::assertFalse($mode->isNormal());
		self::assertFalse($mode->isInstall());

		self::assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertTrue($mode->has(Mode::DBAVAILABLE));
		self::assertFalse($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	public function testMethodsReturnCorrectValuesWithNormalMode(): void
	{
		$root = vfsStream::setup(__FUNCTION__ . '_friendica', 0777, ['config' => [
			'local.config.php' => file_get_contents(realpath(dirname(__FILE__, 4) . '/config/local-sample.config.php')),
		]]);

		$database = self::createMock(Database::class);
		$database->expects(self::once())->method('connected')->willReturn(true);

		$config = self::createMock(IManageConfigValues::class);
		$config->expects(self::once())->method('get')->with('system', 'maintenance')->willReturn(false);

		$mode = (new Mode())->determine($root->url(), $database, $config);

		self::assertTrue($mode->isNormal());
		self::assertFalse($mode->isInstall());

		self::assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		self::assertTrue($mode->has(Mode::DBAVAILABLE));
		self::assertFalse($mode->has(Mode::DBCONFIGAVAILABLE));
		self::assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}

	/**
	 * Test that modes are immutable
	 */
	public function testDetermineReturnsNewModeInstance(): void
	{
		$mode = new Mode();

		$modeNew = $mode->determine('', self::createStub(Database::class), self::createStub(IManageConfigValues::class));

		self::assertNotSame($mode, $modeNew);
	}

	/**
	 * Test if not called by index is backend
	 */
	public function testIsBackendReturnsTrue(): void
	{
		$args         = self::createStub(Arguments::class);
		$mobileDetect = self::createStub(MobileDetect::class);

		$mode = (new Mode())->determineRunMode(true, [], $args, $mobileDetect);

		self::assertTrue($mode->isBackend());
	}

	/**
	 * Test is called by index but module is backend
	 */
	public function testIsBackendWithBackendModuleReturnsTrue(): void
	{
		$args = self::createMock(Arguments::class);
		$args->expects(self::once())->method('getModuleName')->willReturn(Mode::BACKEND_MODULES[0]);

		$mobileDetect = self::createStub(MobileDetect::class);

		$mode = (new Mode())->determineRunMode(false, [], $args, $mobileDetect);

		self::assertTrue($mode->isBackend());
	}

	/**
	 * Test is called by index and module is not backend
	 */
	public function testIsBackendWithDefaultModuleReturnsFalse(): void
	{
		$args = self::createMock(Arguments::class);
		$args->expects(self::once())->method('getModuleName')->willReturn(Arguments::DEFAULT_MODULE);

		$mobileDetect = self::createStub(MobileDetect::class);

		$mode = (new Mode())->determineRunMode(false, [], $args, $mobileDetect);

		self::assertFalse($mode->isBackend());
	}

	/**
	 * Test if the call is an ajax call
	 */
	public function testIsAjaxReturnsTrue(): void
	{
		// This is the server environment variable to determine ajax calls
		$server = [
			'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest',
		];

		$args         = self::createStub(Arguments::class);
		$mobileDetect = self::createStub(MobileDetect::class);

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertTrue($mode->isAjax());
	}

	/**
	 * Test if the call is not nan ajax call
	 */
	public function testIsAjaxReturnsFalse(): void
	{
		// header for ajax call is missing
		$server = [];

		$args         = self::createStub(Arguments::class);
		$mobileDetect = self::createStub(MobileDetect::class);

		$mode = (new Mode())->determineRunMode(true, $server, $args, $mobileDetect);

		self::assertFalse($mode->isAjax());
	}

	/**
	 * Test if the call is a mobile and is a tablet call
	 */
	public function testIsMobileAndIsTabletReturnsTrue(): void
	{
		$args         = self::createStub(Arguments::class);
		$mobileDetect = self::createMock(MobileDetect::class);
		$mobileDetect->expects(self::once())->method('isMobile')->willReturn(true);
		$mobileDetect->expects(self::once())->method('isTablet')->willReturn(true);

		$mode = (new Mode())->determineRunMode(true, [], $args, $mobileDetect);

		self::assertTrue($mode->isMobile());
		self::assertTrue($mode->isTablet());
	}


	/**
	 * Test if the call is not a mobile and is not a tablet call
	 */
	public function testIsMobileAndIsTabletReturnsFalse(): void
	{
		$args         = self::createStub(Arguments::class);
		$mobileDetect = self::createMock(MobileDetect::class);
		$mobileDetect->expects(self::once())->method('isMobile')->willReturn(false);
		$mobileDetect->expects(self::once())->method('isTablet')->willReturn(false);

		$mode = (new Mode())->determineRunMode(true, [], $args, $mobileDetect);

		self::assertFalse($mode->isMobile());
		self::assertFalse($mode->isTablet());
	}
}

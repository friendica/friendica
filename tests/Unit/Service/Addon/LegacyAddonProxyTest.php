<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Service\Addon;

use Friendica\Service\Addon\Addon;
use Friendica\Service\Addon\LegacyAddonProxy;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LegacyAddonProxyTest extends TestCase
{
	public function testCreateWithIdAndPath(): void
	{
		$root = vfsStream::setup('addons', 0777, ['helloaddon' => []]);

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$this->assertInstanceOf(Addon::class, $addon);
	}

	public function testGetIdReturnsId(): void
	{
		$root = vfsStream::setup('addons', 0777, ['helloaddon' => []]);

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$this->assertSame('helloaddon', $addon->getId());
	}

	public function testGetRequiredDependenciesReturnsEmptyArray(): void
	{
		$root = vfsStream::setup('addons', 0777, ['helloaddon' => []]);

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$this->assertSame(
			[],
			$addon->getRequiredDependencies(),
		);
	}

	public function testGetProvidedDependencyIncludesConfigFile(): void
	{
		$root = vfsStream::setup('addons_4', 0777, ['helloaddon' => ['static' => []]]);

		vfsStream::newFile('dependencies.config.php')
				->at($root->getChild('helloaddon/static'))
				->setContent('<?php return [\'name\' => []];');

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$this->assertSame(
			['name' => []],
			$addon->getProvidedDependencyRules(),
		);
	}

	public function testGetSubscribedEventsReturnsEmptyArray(): void
	{
		$root = vfsStream::setup('addons', 0777, ['helloaddon' => []]);

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$this->assertSame(
			[],
			$addon->getSubscribedEvents(),
		);
	}

	public function testInitAddonIncludesAddonFile(): void
	{
		$root = vfsStream::setup('addons_1', 0777, ['helloaddon' => []]);

		vfsStream::newFile('helloaddon.php')
				->at($root->getChild('helloaddon'))
				->setContent('<?php throw new \Exception("Addon loaded");');

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		try {
			$addon->initAddon($this->createStub(ContainerInterface::class));
		} catch (\Throwable $th) {
			$this->assertSame(
				'Addon loaded',
				$th->getMessage(),
			);
		}
	}

	public function testInitAddonMultipleTimesWillIncludeFileOnlyOnce(): void
	{
		$root = vfsStream::setup('addons_2', 0777, ['helloaddon' => []]);

		vfsStream::newFile('helloaddon.php')
				->at($root->getChild('helloaddon'))
				->setContent('<?php throw new \Exception("Addon loaded");');

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		try {
			$addon->initAddon($this->createStub(ContainerInterface::class));
		} catch (\Exception $th) {
			$this->assertSame(
				'Addon loaded',
				$th->getMessage(),
			);
		}

		$addon->initAddon($this->createStub(ContainerInterface::class));
		$addon->initAddon($this->createStub(ContainerInterface::class));
	}

	public function testInstallAddonWillCallInstallFunction(): void
	{
		$root = vfsStream::setup('addons_3', 0777, ['helloaddon' => []]);

		vfsStream::newFile('helloaddon.php')
				->at($root->getChild('helloaddon'))
				->setContent('<?php function helloaddon_install() { throw new \Exception("Addon installed"); }');

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$addon->initAddon($this->createStub(ContainerInterface::class));
		try {
			$addon->installAddon();
		} catch (\Exception $th) {
			$this->assertSame(
				'Addon installed',
				$th->getMessage(),
			);
		}
	}

	public function testUninstallAddonWillCallUninstallFunction(): void
	{
		$root = vfsStream::setup('addons_4', 0777, ['helloaddon' => []]);

		vfsStream::newFile('helloaddon.php')
				->at($root->getChild('helloaddon'))
				->setContent('<?php function helloaddon_uninstall() { throw new \Exception("Addon uninstalled"); }');

		$addon = new LegacyAddonProxy('helloaddon', $root->url());

		$addon->initAddon($this->createStub(ContainerInterface::class));
		try {
			$addon->uninstallAddon();
		} catch (\Exception $th) {
			$this->assertSame(
				'Addon uninstalled',
				$th->getMessage(),
			);
		}
	}
}

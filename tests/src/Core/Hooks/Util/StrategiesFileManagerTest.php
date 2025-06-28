<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Hooks\Util;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookConfigException;
use Friendica\Core\Hooks\Util\StrategiesFileManager;
use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StrategiesFileManagerTest extends MockedTestCase
{
	use VFSTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	public function dataHooks(): array
	{
		return [
			'normal' => [
				'content' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					];
					EOF,
				'addonContent' => <<<EOF
					<?php

					return [];
					EOF,
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
			'normalWithString' => [
				'content' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => '',
						],
					];
					EOF,
				'addonContent' => <<<EOF
					<?php

					return [];
					EOF,
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
			'withAddons' => [
				'content' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					];
					EOF,
				'addonContent' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => ['null'],
						],
					];
					EOF,
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
			],
			'withAddonsWithString' => [
				'content' => <<<EOF
					<?php

					return [
							\Psr\Log\LoggerInterface::class => [
								\Psr\Log\NullLogger::class => [''],
							],
					];
					EOF,
				'addonContent' => <<<EOF
					<?php

					return [
							\Psr\Log\LoggerInterface::class => [
								\Psr\Log\NullLogger::class => ['null'],
							],
					];
					EOF,
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
			],
			// This should work because unique name convention is part of the instance manager logic, not of the file-infrastructure layer
			'withAddonsDoubleNamed' => [
				'content' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					];
					EOF,
				'addonContent' => <<<EOF
					<?php

					return [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					];
					EOF,
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
		];
	}

	/**
	 * @dataProvider dataHooks
	 */
	public function testSetupHooks(string $content, string $addonContent, array $assertStrategies)
	{
		vfsStream::newFile(StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
			->withContent($content)
			->at($this->root);

		vfsStream::newFile('addon/testaddon/' . StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
			->withContent($addonContent)
			->at($this->root);

		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')->andReturn(['testaddon' => ['admin' => false]])->once();

		$hookFileManager = new StrategiesFileManager($this->root->url(), $config);

		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		foreach ($assertStrategies as $assertStrategy) {
			$instanceManager->shouldReceive('registerStrategy')->withArgs($assertStrategy)->once();
		}

		$hookFileManager->loadConfig();
		$hookFileManager->setupStrategies($instanceManager);

		self::expectNotToPerformAssertions();
	}

	/**
	 * Test the exception in case the strategies.config.php file is missing
	 */
	public function testMissingStrategiesFile()
	{
		$config          = \Mockery::mock(IManageConfigValues::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $config);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf(
			'config file %s does not exist.',
			$this->root->url() . '/' . StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php'
		));

		$hookFileManager->loadConfig();
	}

	/**
	 * Test the exception in case the strategies.config.php file is wrong
	 */
	public function testWrongStrategiesFile()
	{
		$config          = \Mockery::mock(IManageConfigValues::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $config);

		vfsStream::newFile(StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
				 ->withContent("<?php return 'WRONG_CONTENT';")
				 ->at($this->root);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf(
			'Error loading config file %s.',
			$this->root->url() . '/' . StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php'
		));

		$hookFileManager->loadConfig();
	}
}

<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Hooks\Util;

use Friendica\Core\Addon\Capability\ICanLoadAddons;
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
				'addonsArray'      => [],
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
				'addonsArray'      => [],
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
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => ['null'],
					],
				],
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
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => 'null',
					],
				],
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
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => [''],
					],
				],
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
	public function testSetupHooks(string $content, array $addonsArray, array $assertStrategies)
	{
		vfsStream::newFile(StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
			->withContent($content)
			->at($this->root);

		$addonLoader = \Mockery::mock(ICanLoadAddons::class);
		$addonLoader->shouldReceive('getActiveAddonConfig')->andReturn($addonsArray)->once();

		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

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
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

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
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

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

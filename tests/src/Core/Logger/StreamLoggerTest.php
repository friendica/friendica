<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Test\LoggerTestCase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Core\Logger\Type\StreamLogger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\LogLevel;

class StreamLoggerTest extends LoggerTestCase
{
	use VFSTrait;

	/**
	 * @var vfsStreamFile
	 */
	private $logfile;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	/**
	 * {@@inheritdoc}
	 */
	protected function getInstance($level = LogLevel::DEBUG, $logfile = 'friendica.log')
	{
		$this->logfile = vfsStream::newFile($logfile)
			->at($this->root);

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn($this->logfile->url())->once();
		$this->config->shouldReceive('get')->with('system', 'loglevel')->andReturn($level)->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		return $loggerFactory->create($this->config);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logfile->getContent();
	}

	/**
	 * Test when a file isn't set
	 */
	public function testNoUrl(): void
	{
		$this->expectException(LoggerArgumentException::class);
		$this->expectExceptionMessage(' is not a valid logfile');

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn('')->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		$logger        = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when a file cannot be opened
	 */
	public function testWrongUrl(): void
	{
		$this->expectException(LoggerArgumentException::class);

		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root)->chmod(0);

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn($logfile->url())->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		$logger        = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when the directory cannot get created
	 */
	public function testWrongDir(): void
	{
		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessageMatches("/Directory .* cannot get created: .* /");

		static::markTestIncomplete('We need a platform independent way to set directory to readonly');

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test'); // @phpstan-ignore deadCode.unreachable (skipped test)
		$logger        = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongMinimumLevel(): void
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance('NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongLogLevel(): void
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance('NOPE');

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test a relative path
	 */
	#[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
	public function testRealPath(): void
	{
		static::markTestSkipped('vfsStream isn\'t compatible with chdir, so not testable.');

		$logfile = vfsStream::newFile('friendica.log') // @phpstan-ignore deadCode.unreachable (skipped test)
							->at($this->root);

		chdir($this->root->getChild('logs')->url());

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn('../friendica.log')->once();

		$logger = new StreamLogger('test', $this->config, $this->introspection, $this->fileSystem);

		$logger->info('Test');
	}
}

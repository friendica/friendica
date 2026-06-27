<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit;

use Friendica\App;
use Friendica\App\Request;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Container;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
	public function testFromContainerReturnsApp(): void
	{
		$container = $this->createMock(Container::class);
		$container->expects($this->never())->method('create');

		$app = App::fromContainer($container);

		$this->assertInstanceOf(App::class, $app); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testMergeBodyIntoPsr7Request(): void
	{
		$configMock = $this->createMock(IManageConfigValues::class);
		$configMock->method('get')->willReturn('');

		$psr7Request = new ServerRequest(
			'POST',
			'http://example.com',
			['Content-Type' => 'application/x-www-form-urlencoded;charset=utf8'],
			'title=Test2',
			'1.1',
			['CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=utf8'],
		);

		$app = App::fromContainer($this->createStub(Container::class));

		$method          = new \ReflectionMethod(App::class, 'mergeRequestInput');
		$modifiedRequest = $method->invoke($app, $psr7Request);

		$appRequest = new Request($modifiedRequest, $configMock);

		self::assertSame('Test2', $appRequest->getBodyParam('title'));
	}

	public function testMergeRequestInputPreservesUploadedFiles(): void
	{
		$configMock = $this->createMock(IManageConfigValues::class);
		$configMock->method('get')->willReturn('');

		$uploadedFile = $this->createStub(\Psr\Http\Message\UploadedFileInterface::class);

		$psr7Request = new ServerRequest(
			'POST',
			'http://example.com',
			['Content-Type' => 'application/x-www-form-urlencoded;charset=utf8'],
			'title=Test',
			'1.1',
			['CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=utf8'],
		);
		$psr7Request = $psr7Request->withUploadedFiles(['avatar' => $uploadedFile]);

		$app = App::fromContainer($this->createStub(Container::class));

		$method          = new \ReflectionMethod(App::class, 'mergeRequestInput');
		$modifiedRequest = $method->invoke($app, $psr7Request);

		self::assertArrayHasKey('avatar', $modifiedRequest->getUploadedFiles());
		self::assertSame($uploadedFile, $modifiedRequest->getUploadedFiles()['avatar']);
	}
}

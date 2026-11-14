<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Protocol;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Protocol\ATProtocol;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ATProtocolTest extends TestCase
{
	private function getATProtocol(ICanSendHttpRequests $httpClient, ?IManageConfigValues $config = null): ATProtocol
	{
		return new ATProtocol(
			$this->createStub(LoggerInterface::class),
			$this->createStub(Database::class),
			$config ?? $this->createStub(IManageConfigValues::class),
			$this->createStub(IManagePersonalConfigValues::class),
			$httpClient,
		);
	}

	public static function dataDidWeb(): array
	{
		return [
			'domain only' => [
				'did' => 'did:web:example.com',
				'url' => 'https://example.com/.well-known/did.json',
			],
			'with path' => [
				'did' => 'did:web:example.com:user:alice',
				'url' => 'https://example.com/user/alice/did.json',
			],
			'percent-encoded port' => [
				'did' => 'did:web:example.com%3A3000',
				'url' => 'https://example.com:3000/.well-known/did.json',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDidWeb')]
	public function testGetDidDocumentForDidWeb(string $did, string $url): void
	{
		$response = $this->createStub(ICanHandleHttpResponses::class);
		$response->method('getBodyString')->willReturn('');

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->once())
			->method('get')
			->with($url, HttpClientAccept::JSON, [])
			->willReturn($response);

		$this->assertNull($this->getATProtocol($httpClient)->getDidDocument($did));
	}

	public function testGetDidDocumentForDidWebWithoutDomain(): void
	{
		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->never())->method('get');

		$this->assertNull($this->getATProtocol($httpClient)->getDidDocument('did:web:'));
	}

	public function testGetDidDocumentForDidPlc(): void
	{
		$config = $this->createMock(IManageConfigValues::class);
		$config->expects($this->once())->method('get')->with('atprotocol', 'plc_directory')->willReturn('https://plc.directory');

		$response = $this->createStub(ICanHandleHttpResponses::class);
		$response->method('getBodyString')->willReturn('');

		$httpClient = $this->createMock(ICanSendHttpRequests::class);
		$httpClient->expects($this->once())
			->method('get')
			->with('https://plc.directory/did:plc:z72i7hdynmk6r22z27h6tvur', HttpClientAccept::JSON, [])
			->willReturn($response);

		$this->assertNull($this->getATProtocol($httpClient, $config)->getDidDocument('did:plc:z72i7hdynmk6r22z27h6tvur'));
	}
}

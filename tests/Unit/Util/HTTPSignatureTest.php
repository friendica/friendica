<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Dice\Dice;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Network\HTTPClient\Capability\ICanHandleHttpResponses;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Util\BasePath;
use Friendica\Util\HTTPSignature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HTTPSignatureTest extends TestCase
{
	private const ACTOR = 'https://remote.example/users/alice';

	private ICanSendHttpRequests&MockObject $httpClient;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpClient = $this->createMock(ICanSendHttpRequests::class);

		// The JSON-LD document loader needs the base path and a cache
		$cache = $this->createMock(ICanCache::class);
		$cache->method('get')->willReturn(null);

		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class      => new NullLogger(),
			ICanSendHttpRequests::class => $this->httpClient,
			ICanCache::class            => $cache,
			BasePath::class             => new BasePath(dirname(__DIR__, 3)),
			default                     => throw new \InvalidArgumentException('Unexpected DI::create() call for class: ' . $name),
		});

		DI::init($dice, true);
	}

	private static function publicKey(): string
	{
		return openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]))['key'];
	}

	private function response(bool $success, string $body = '', string $contentType = 'application/activity+json', bool $gone = false): ICanHandleHttpResponses
	{
		$response = $this->createMock(ICanHandleHttpResponses::class);
		$response->method('isGone')->willReturn($gone);
		$response->method('isSuccess')->willReturn($success);
		$response->method('getReturnCode')->willReturn($gone ? '410' : ($success ? '200' : '500'));
		$response->method('getContentType')->willReturn($contentType);
		$response->method('getBodyString')->willReturn($body);

		return $response;
	}

	private function actorDocument(string $id, string $pem): string
	{
		return json_encode([
			'@context'  => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
			'id'        => $id,
			'type'      => 'Person',
			'inbox'     => $id . '/inbox',
			'publicKey' => ['id' => $id . '#main-key', 'owner' => $id, 'publicKeyPem' => $pem],
		]);
	}

	private function fetchUnsignedKey(string $url, bool $followOwner = true): array
	{
		return (new \ReflectionMethod(HTTPSignature::class, 'fetchUnsignedKey'))->invoke(null, $url, $followOwner);
	}

	public function testActorDocumentReturnsItsKey(): void
	{
		$pem = self::publicKey();
		$this->httpClient->method('get')->willReturn($this->response(true, $this->actorDocument(self::ACTOR, $pem)));

		$key = $this->fetchUnsignedKey(self::ACTOR . '#main-key');

		self::assertSame(self::ACTOR, $key['url']);
		self::assertSame(trim($pem), $key['pubkey']);
		self::assertSame('Person', $key['type']);
	}

	/**
	 * GoToSocial serves the actor document under the key id path.
	 */
	public function testKeyIdPathServingTheActorDocument(): void
	{
		$pem = self::publicKey();
		$this->httpClient->method('get')->willReturn($this->response(true, $this->actorDocument(self::ACTOR, $pem)));

		$key = $this->fetchUnsignedKey(self::ACTOR . '/main-key');

		self::assertSame(self::ACTOR, $key['url']);
		self::assertSame(trim($pem), $key['pubkey']);
	}

	public function testGoneReturnsTombstone(): void
	{
		$this->httpClient->method('get')->willReturn($this->response(false, '', 'text/html', true));

		self::assertSame(
			['url' => self::ACTOR, 'pubkey' => '', 'type' => 'Tombstone'],
			$this->fetchUnsignedKey(self::ACTOR),
		);
	}

	public function testFailedRequestReturnsNothing(): void
	{
		$this->httpClient->method('get')->willReturn($this->response(false));

		self::assertSame([], $this->fetchUnsignedKey(self::ACTOR));
	}

	public function testInvalidContentTypeReturnsNothing(): void
	{
		$this->httpClient->method('get')->willReturn($this->response(true, $this->actorDocument(self::ACTOR, self::publicKey()), 'text/html'));

		self::assertSame([], $this->fetchUnsignedKey(self::ACTOR));
	}

	public function testInvalidJsonReturnsNothing(): void
	{
		$this->httpClient->method('get')->willReturn($this->response(true, 'not json'));

		self::assertSame([], $this->fetchUnsignedKey(self::ACTOR));
	}

	/**
	 * A key id must not hand out the key of an actor on another host.
	 */
	public function testActorOnDifferentHostIsRejected(): void
	{
		$this->httpClient->method('get')->willReturn($this->response(true, $this->actorDocument('https://victim.example/users/bob', self::publicKey())));

		self::assertSame([], $this->fetchUnsignedKey(self::ACTOR . '#main-key'));
	}

	public function testActorWithoutKeyReturnsNothing(): void
	{
		$document = json_encode([
			'@context' => ['https://www.w3.org/ns/activitystreams'],
			'id'       => self::ACTOR,
			'type'     => 'Person',
		]);
		$this->httpClient->method('get')->willReturn($this->response(true, $document));

		self::assertSame([], $this->fetchUnsignedKey(self::ACTOR));
	}

	/**
	 * A key document is only followed to its owner once, so a chain of key documents cannot loop.
	 */
	public function testKeyDocumentIsNotFollowedWhenOwnerLookupIsDisabled(): void
	{
		$document = json_encode([
			'@context'     => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
			'id'           => 'https://remote.example/keys/1',
			'type'         => 'CryptographicKey',
			'owner'        => self::ACTOR,
			'publicKeyPem' => self::publicKey(),
		]);
		$this->httpClient->expects(self::once())->method('get')->willReturn($this->response(true, $document));

		self::assertSame([], $this->fetchUnsignedKey('https://remote.example/keys/1', false));
	}

	public function testGetPublicKeyReturnsRsaKey(): void
	{
		$pem = self::publicKey();

		self::assertSame(trim($pem), APContact::getPublicKey(['w3id:publicKey' => ['w3id:publicKeyPem' => ['@value' => $pem]]]));
	}

	public function testGetPublicKeyFallsBackToEd25519Multikey(): void
	{
		$compacted = ['w3id:assertionMethod' => ['@type' => 'w3id:Multikey', 'w3id:publicKeyMultibase' => ['@value' => 'z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK']]];

		self::assertSame('z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK', APContact::getPublicKey($compacted));
	}

	public function testGetPublicKeyIgnoresUnknownMultibaseKey(): void
	{
		$compacted = ['w3id:assertionMethod' => ['w3id:publicKeyMultibase' => ['@value' => 'zQ3shNotEd25519']]];

		self::assertNull(APContact::getPublicKey($compacted));
	}

	public function testGetPublicKeyWithoutKeyIsNull(): void
	{
		self::assertNull(APContact::getPublicKey([]));
	}
}

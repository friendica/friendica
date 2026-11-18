<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module;

use Dice\Dice;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Xrd;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\FixtureTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;

final class XrdTest extends FixtureTestCase
{
	protected function tearDown(): void
	{
		unset($_GET['resource']);

		parent::tearDown();
	}

	public static function acceptedResourceProvider(): array
	{
		return [
			'host with port of the base url' => ['https://friendica.local:8443', 'acct:selfcontact@friendica.local:8443'],
			'host without port'              => ['https://friendica.local:8443', 'acct:selfcontact@friendica.local'],
			'profile url with port'          => ['https://friendica.local:8443', 'https://friendica.local:8443/profile/selfcontact'],
			'default port'                   => ['https://friendica.local', 'acct:selfcontact@friendica.local'],
		];
	}

	#[DataProvider('acceptedResourceProvider')]
	public function testWebfingerAcceptsOwnHost(string $baseUrl, string $resource): void
	{
		$response = $this->webfinger($baseUrl, $resource);

		self::assertSame(200, $response->getStatusCode());
		self::assertStringStartsWith('acct:selfcontact@friendica.local', json_decode((string) $response->getBody(), true)['subject']);
	}

	public static function rejectedResourceProvider(): array
	{
		return [
			'other port'          => ['https://friendica.local:8443', 'acct:selfcontact@friendica.local:9443'],
			'port on default url' => ['https://friendica.local', 'acct:selfcontact@friendica.local:8443'],
			'other host'          => ['https://friendica.local:8443', 'acct:selfcontact@example.com:8443'],
		];
	}

	#[DataProvider('rejectedResourceProvider')]
	public function testWebfingerRejectsForeignHost(string $baseUrl, string $resource): void
	{
		$this->expectException(NotFoundException::class);

		$this->webfinger($baseUrl, $resource);
	}

	private function webfinger(string $baseUrl, string $resource): \Psr\Http\Message\ResponseInterface
	{
		DI::config()->set('system', 'url', $baseUrl);
		$this->dice = $this->dice
			->addRule(BaseURL::class, ['constructParams' => [[]], 'shared' => true])
			->addRule(Arguments::class, [
				'instanceOf' => Arguments::class,
				'call'       => [['determine', [['QUERY_STRING' => 'pagename=.well-known/webfinger'], []], Dice::CHAIN_CALL]],
			]);
		DI::init($this->dice, true);

		$_GET['resource'] = $resource;

		$module = $this->dice->create(Xrd::class, [[], []]);

		try {
			$module->handleRequest(new ServerRequest('GET', $baseUrl . '/.well-known/webfinger'));
		} catch (EarlyExitException $e) {
			return $e->getResponse();
		}

		self::fail('Expected EarlyExitException');
	}
}

<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Protocol\ActivityPub;

use Dice\Dice;
use Friendica\DI;
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Util\Profiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReceiverTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// HTML::toPlaintext() records its runtime in the profiler
		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			Profiler::class => $this->createStub(Profiler::class),
			default         => throw new \InvalidArgumentException('Unexpected DI::create() call for class: ' . $name),
		});

		DI::init($dice, true);
	}

	public static function dataQuoteRequestInstrument(): array
	{
		return [
			'embedded note' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/note/1', '@type' => 'as:Note']],
				'expected' => 'https://example.test/note/1',
			],
			'embedded article' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/article/1', '@type' => 'as:Article']],
				'expected' => 'https://example.test/article/1',
			],
			'link only' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/note/1']],
				'expected' => 'https://example.test/note/1',
			],
			'application and note' => [
				'activity' => ['as:instrument' => [
					['@id' => 'https://example.test/app', '@type' => 'as:Application'],
					['@id' => 'https://example.test/note/1', '@type' => 'as:Note'],
				]],
				'expected' => 'https://example.test/note/1',
			],
			'application only' => [
				'activity' => ['as:instrument' => ['@id' => 'https://example.test/app', '@type' => 'as:Application']],
				'expected' => null,
			],
			'no instrument' => [
				'activity' => [],
				'expected' => null,
			],
		];
	}

	#[DataProvider('dataQuoteRequestInstrument')]
	public function testFetchQuoteRequestInstrument(array $activity, ?string $expected): void
	{
		$method = new \ReflectionMethod(Receiver::class, 'fetchQuoteRequestInstrument');

		self::assertSame($expected, $method->invoke(null, $activity));
	}

	public static function dataLocationParts(): array
	{
		return [
			'name only' => [
				'place'    => ['@type' => 'as:Place', 'as:name' => ['@value' => 'Café X']],
				'expected' => ['Café X'],
			],
			'mobilizon address in name' => [
				'place' => [
					'@type'      => 'as:Place',
					'as:name'    => ['@value' => 'Apenrader Straße 49, 24939 Flensburg, Schleswig-Holstein'],
					'sc:address' => [
						'@type'              => 'sc:PostalAddress',
						'sc:streetAddress'   => ['@value' => '49 Apenrader Straße'],
						'sc:addressLocality' => ['@value' => 'Flensburg'],
						'sc:addressRegion'   => ['@value' => 'Schleswig-Holstein'],
						'sc:addressCountry'  => ['@value' => 'Germany'],
					],
				],
				'expected' => ['Apenrader Straße 49, 24939 Flensburg, Schleswig-Holstein', 'Germany'],
			],
			'venue name and address' => [
				'place' => [
					'@type'      => 'as:Place',
					'as:name'    => ['@value' => 'Café X'],
					'sc:address' => [
						'@type'              => 'sc:PostalAddress',
						'sc:streetAddress'   => ['@value' => 'Hauptstr. 1'],
						'sc:addressLocality' => ['@value' => 'Berlin'],
						'sc:addressRegion'   => ['@value' => 'Berlin'],
						'sc:addressCountry'  => ['@value' => 'Germany'],
					],
				],
				'expected' => ['Café X', 'Hauptstr. 1', 'Berlin', 'Germany'],
			],
			'partial address without name' => [
				'place' => [
					'@type'      => 'as:Place',
					'sc:address' => ['sc:addressLocality' => ['@value' => 'Wien'], 'sc:addressCountry' => ['@value' => 'Austria']],
				],
				'expected' => ['Wien', 'Austria'],
			],
			'address as string' => [
				'place' => [
					'@type'      => 'as:Place',
					'as:name'    => ['@value' => 'Rathaus'],
					'sc:address' => ['@value' => 'Rathausplatz 1, Wien'],
				],
				'expected' => ['Rathaus', 'Rathausplatz 1, Wien'],
			],
			'name repeated in address' => [
				'place' => [
					'@type'      => 'as:Place',
					'as:name'    => ['@value' => 'Berlin'],
					'sc:address' => ['sc:addressLocality' => ['@value' => 'Berlin']],
				],
				'expected' => ['Berlin'],
			],
			'pixelfed country' => [
				'place'    => ['@type' => 'as:Place', 'as:name' => ['@value' => 'Vancouver'], 'sc:addressCountry' => ['@value' => 'Canada']],
				'expected' => ['Vancouver', 'Canada'],
			],
			'name as plain string' => [
				'place'    => ['@type' => 'as:Place', 'as:name' => 'Café X'],
				'expected' => ['Café X'],
			],
			'html in name' => [
				'place'    => ['@type' => 'as:Place', 'as:name' => '<p>Café X</p>', 'sc:address' => ['sc:addressLocality' => ['@value' => 'P']]],
				'expected' => ['Café X', 'P'],
			],
			'no place' => [
				'place'    => null,
				'expected' => [],
			],
		];
	}

	#[DataProvider('dataLocationParts')]
	public function testFetchLocationParts(?array $place, array $expected): void
	{
		$method = new \ReflectionMethod(Receiver::class, 'fetchLocationParts');

		self::assertSame($expected, $method->invoke(null, $place));
	}

	public static function dataHostedBySigner(): array
	{
		return [
			'same host' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => ['https://example.test/users/alice'],
				'expected' => true,
			],
			'different case' => [
				'objectId' => 'https://Example.TEST/note/1',
				'signer'   => ['https://example.test/users/alice'],
				'expected' => true,
			],
			'second signer' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => ['https://other.test/users/bob', 'https://example.test/users/alice'],
				'expected' => true,
			],
			'other host' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => ['https://other.test/users/bob'],
				'expected' => false,
			],
			'subdomain' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => ['https://sub.example.test/users/alice'],
				'expected' => false,
			],
			'id without host' => [
				'objectId' => '/note/1',
				'signer'   => ['https://example.test/users/alice'],
				'expected' => false,
			],
			'signer without host' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => ['/users/alice'],
				'expected' => false,
			],
			'no signer' => [
				'objectId' => 'https://example.test/note/1',
				'signer'   => [],
				'expected' => false,
			],
		];
	}

	#[DataProvider('dataHostedBySigner')]
	public function testIsHostedBySigner(string $objectId, array $signer, bool $expected): void
	{
		$method = new \ReflectionMethod(Receiver::class, 'isHostedBySigner');

		self::assertSame($expected, $method->invoke(null, $objectId, $signer));
	}
}

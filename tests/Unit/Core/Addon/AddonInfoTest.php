<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Addon;

use Friendica\Core\Addon\AddonInfo;
use PHPUnit\Framework\TestCase;

class AddonInfoTest extends TestCase
{
	public function testFromStringCreatesObject(): void
	{
		$this->assertInstanceOf(AddonInfo::class, AddonInfo::fromString('addonId', ''));
	}

	public static function getStringData(): array
	{
		return [
			'minimal' => [
				'test',
				'',
				['id' => 'test'],
			],
			'without-author' => [
				'test',
				<<<TEXT
				/**
				 * Name: Test Addon
				 * Description: adds awesome features to friendica
				 * Version: 100.4.50-beta.5
				 * Maintainer: Robin
				 * Status: beta
				 * Ignore: The "ignore" key is unsupported and will be ignored
				 */
				TEXT,
				[
					'id'          => 'test',
					'name'        => 'Test Addon',
					'description' => 'adds awesome features to friendica',

					'maintainers' => [
						['name' => 'Robin'],
					],
					'version' => '100.4.50-beta.5',
					'status'  => 'beta',
				],
			],
			'without-maintainer' => [
				'test',
				<<<TEXT
				/*
				 * Name: Test Addon
				 * Description: adds awesome features to friendica
				 * Version: 100.4.50-beta.5
				 * Author: Sam
				 * Status: beta
				 * Ignore: The "ignore" key is unsupported and will be ignored
				 */
				TEXT,
				[
					'id'          => 'test',
					'name'        => 'Test Addon',
					'description' => 'adds awesome features to friendica',
					'authors'     => [
						['name' => 'Sam'],
					],
					'version' => '100.4.50-beta.5',
					'status'  => 'beta',
				],
			],
			'complete' => [
				'test',
				<<<TEXT
				/*
				 * Name: Test Addon
				 * Description: adds awesome features to friendica
				 * Version: 100.4.50-beta.5
				 * Author: Sam
				 * Author: Sam With Mail <mail@example.org>
				 * Maintainer: Robin
				 * Maintainer: Robin With Profile <https://example.org/profile/robin>
				 * Status: beta
				 * Ignore: The "ignore" key is unsupported and will be ignored
				 */
				TEXT,
				[
					'id'          => 'test',
					'name'        => 'Test Addon',
					'description' => 'adds awesome features to friendica',
					'authors'     => [
						['name' => 'Sam'],
						['name' => 'Sam With Mail', 'link' => 'mail@example.org'],
					],
					'maintainers' => [
						['name' => 'Robin'],
						['name' => 'Robin With Profile', 'link' => 'https://example.org/profile/robin'],
					],
					'version' => '100.4.50-beta.5',
					'status'  => 'beta',
				],
			],
		];
	}

	/**
	 * @dataProvider getStringData
	 */
	public function testFromStringReturnsCorrectValues(string $addonId, string $raw, array $expected): void
	{
		$this->assertAddonInfoData($expected, AddonInfo::fromString($addonId, $raw));
	}

	public function testFromArrayCreatesObject(): void
	{
		$this->assertInstanceOf(AddonInfo::class, AddonInfo::fromArray([]));
	}

	public function testGetterReturningCorrectValues(): void
	{
		$data = [
			'id'          => 'test',
			'name'        => 'Test-Addon',
			'description' => 'This is an addon for tests',
			'authors'     => [['name' => 'Sam']],
			'maintainers' => [['name' => 'Sam', 'link' => 'https://example.com']],
			'version'     => '0.1',
			'status'      => 'In Development',
		];

		$this->assertAddonInfoData($data, AddonInfo::fromArray($data));
	}

	private function assertAddonInfoData(array $expected, AddonInfo $info): void
	{
		$expected = array_merge(
			[
				'id'          => '',
				'name'        => '',
				'description' => '',
				'authors'     => [],
				'maintainers' => [],
				'version'     => '',
				'status'      => '',
			],
			$expected
		);

		$data = [
			'id'          => $info->getId(),
			'name'        => $info->getName(),
			'description' => $info->getDescription(),
			'authors'     => $info->getAuthors(),
			'maintainers' => $info->getMaintainers(),
			'version'     => $info->getVersion(),
			'status'      => $info->getStatus(),
		];

		$this->assertSame($expected, $data);
	}
}

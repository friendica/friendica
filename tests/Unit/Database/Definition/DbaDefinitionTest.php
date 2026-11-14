<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Database\Definition;

use Dice\Dice;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

class DbaDefinitionTest extends TestCase
{
	private function definition(string $charset = ''): DbaDefinition
	{
		$config = $this->createMock(IManageConfigValues::class);
		$config->method('get')->willReturnCallback(
			fn (string $cat, string $key): ?string => ($cat === 'database' && $key === 'charset') ? $charset : null,
		);

		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			IManageConfigValues::class => $config,
			default                    => throw new \InvalidArgumentException('Unexpected DI::create() call for class: ' . $name),
		});

		DI::init($dice, true);

		return (new DbaDefinition(dirname(__DIR__, 4)))->load();
	}

	public function testUnknownTableReturnsNoFields(): void
	{
		self::assertSame([], $this->definition()->truncateFieldsForTable('this-table-does-not-exist', ['id' => 1]));
	}

	public function testFieldsThatAreNotPartOfTheTableAreDropped(): void
	{
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['media-id' => 1, 'no-such-column' => 'x']);

		self::assertSame(['media-id' => 1], $fields);
	}

	public function testVarcharFieldsAreCutToTheirCharacterLength(): void
	{
		// "Artist" is a varchar(255)
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['Artist' => str_repeat('ä', 300)]);

		self::assertSame(255, mb_strlen((string) $fields['Artist']));
	}

	/**
	 * Regression test for the "Data too long for column 'raw-data'" errors reported in
	 * https://github.com/friendica/friendica/issues/15817
	 */
	public function testTextFieldsAreCutToTheirByteLength(): void
	{
		// "raw-data" is a text column, which holds 65535 bytes
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['raw-data' => str_repeat('a', 70000)]);

		self::assertSame(65535, strlen((string) $fields['raw-data']));
	}

	public function testTextFieldsAreNotCutInTheMiddleOfAMultiByteCharacter(): void
	{
		// The 65535th byte falls into the middle of a 2 byte character
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['raw-data' => str_repeat('ä', 40000)]);

		self::assertSame(65534, strlen((string) $fields['raw-data']));
		self::assertSame($fields['raw-data'], mb_convert_encoding($fields['raw-data'], 'UTF-8', 'UTF-8'));
	}

	public function testShortTextFieldsAreLeftUntouched(): void
	{
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['raw-data' => '{"Make":"Canon"}']);

		self::assertSame('{"Make":"Canon"}', $fields['raw-data']);
	}

	/**
	 * Regression test for the "Out of range value for column 'Orientation'" errors reported in
	 * https://github.com/friendica/friendica/issues/15817
	 */
	public function testIntegerFieldsAreCappedAtTheirColumnRange(): void
	{
		$definition = $this->definition();

		// "Orientation" is a tinyint unsigned, "ISOSpeedRatings" a smallint unsigned
		$fields = $definition->truncateFieldsForTable('post-media-exif', ['Orientation' => 65537, 'ISOSpeedRatings' => 100000]);

		self::assertSame(255, $fields['Orientation']);
		self::assertSame(65535, $fields['ISOSpeedRatings']);

		// Unsigned columns never receive a negative value
		$fields = $definition->truncateFieldsForTable('post-media-exif', ['Orientation' => -1]);

		self::assertSame(0, $fields['Orientation']);
	}

	public function testIntegerFieldsWithinTheColumnRangeAreLeftUntouched(): void
	{
		$fields = $this->definition()->truncateFieldsForTable('post-media-exif', ['Orientation' => 6, 'ISOSpeedRatings' => 400]);

		self::assertSame(6, $fields['Orientation']);
		self::assertSame(400, $fields['ISOSpeedRatings']);
	}
}

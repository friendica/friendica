<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Network;

use Friendica\Network\Entity;
use Friendica\Network\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MimeTypeTest extends TestCase
{
	public static function dataCreateFromContentType(): array
	{
		return [
			'image/jpg' => [
				'expected'    => new Entity\MimeType('image', 'jpg'),
				'contentType' => 'image/jpg',
			],
			'image/jpg;charset=utf8' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset=utf8' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset = utf8' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset=utf8',
			],
			'image/jpg; charset="utf8"' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset="utf8"',
			],
			'image/jpg; charset="\"utf8\""' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
				'contentType' => 'image/jpg; charset="\"utf8\""',
			],
			'image/jpg; charset="\"utf8\" (comment)"' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
				'contentType' => 'image/jpg; charset="\"utf8\" (comment)"',
			],
			'image/jpg; charset=utf8 (comment)' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
				'contentType' => 'image/jpg; charset="utf8 (comment)"',
			],
			'image/jpg; charset=utf8; attribute=value' => [
				'expected'    => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8', 'attribute' => 'value']),
				'contentType' => 'image/jpg; charset=utf8; attribute=value',
			],
			'empty' => [
				'expected'    => new Entity\MimeType('unkn', 'unkn'),
				'contentType' => '',
			],
			'unknown' => [
				'expected'    => new Entity\MimeType('unkn', 'unkn'),
				'contentType' => 'unknown',
			],
		];
	}

	/**
	 * @param Entity\MimeType $expected
	 * @param string          $contentType
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataCreateFromContentType')]
	public function testCreateFromContentType(Entity\MimeType $expected, string $contentType): void
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($contentType));
	}

	public static function dataToString(): array
	{
		return [
			'image/jpg' => [
				'expected' => 'image/jpg',
				'mimeType' => new Entity\MimeType('image', 'jpg'),
			],
			'image/jpg;charset=utf8' => [
				'expected' => 'image/jpg; charset=utf8',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8']),
			],
			'image/jpg; charset="\"utf8\""' => [
				'expected' => 'image/jpg; charset="\"utf8\""',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => '"utf8"']),
			],
			'image/jpg; charset=utf8; attribute=value' => [
				'expected' => 'image/jpg; charset=utf8; attribute=value',
				'mimeType' => new Entity\MimeType('image', 'jpg', ['charset' => 'utf8', 'attribute' => 'value']),
			],
			'empty' => [
				'expected' => 'unkn/unkn',
				'mimeType' => new Entity\MimeType('unkn', 'unkn'),
			],
		];
	}

	/**
	 * @param string          $expected
	 * @param Entity\MimeType $mimeType
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataToString')]
	public function testToString(string $expected, Entity\MimeType $mimeType): void
	{
		$this->assertEquals($expected, $mimeType->__toString());
	}

	public static function dataRoundtrip(): array
	{
		return [
			['image/jpg'],
			['image/jpg; charset=utf8'],
			['image/jpg; charset="\"utf8\""'],
			['image/jpg; charset=utf8; attribute=value'],
		];
	}

	/**
	 * @param string $expected
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataRoundtrip')]
	public function testRoundtrip(string $expected): void
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($expected)->__toString());
	}
}

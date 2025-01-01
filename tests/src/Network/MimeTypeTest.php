<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Network;

use Friendica\Network\Entity;
use Friendica\Network\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MimeTypeTest extends TestCase
{
	public function dataCreateFromContentType(): array
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
	 * @dataProvider dataCreateFromContentType
	 * @param Entity\MimeType $expected
	 * @param string          $contentType
	 * @return void
	 */
	public function testCreateFromContentType(Entity\MimeType $expected, string $contentType)
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($contentType));
	}

	public function dataToString(): array
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
	 * @dataProvider dataToString
	 * @param string          $expected
	 * @param Entity\MimeType $mimeType
	 * @return void
	 */
	public function testToString(string $expected, Entity\MimeType $mimeType)
	{
		$this->assertEquals($expected, $mimeType->__toString());
	}

	public function dataRoundtrip(): array
	{
		return [
			['image/jpg'],
			['image/jpg; charset=utf8'],
			['image/jpg; charset="\"utf8\""'],
			['image/jpg; charset=utf8; attribute=value'],
		];
	}

	/**
	 * @dataProvider dataRoundtrip
	 * @param string $expected
	 * @return void
	 */
	public function testRoundtrip(string $expected)
	{
		$factory = new Factory\MimeType(new NullLogger());

		$this->assertEquals($expected, $factory->createFromContentType($expected)->__toString());
	}
}

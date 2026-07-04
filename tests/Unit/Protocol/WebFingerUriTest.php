<?php

/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Main database structure configuration file.
 *
 * Here are described all the tables, fields and indexes Friendica needs to work.
 * The entry order is mostly alphabetic - with the exception of tables that are used in foreign keys.
 *
 * Syntax (braces indicate optionale values):
 * "<table name>" => [
 *    "comment" => "Description of the table",
 *    "fields" => [
 *        "<field name>" => [
 *            "type" => "<field type>{(<field size>)} <unsigned>",
 *            "not null" => 0|1,
 *            {"extra" => "auto_increment",}
 *            {"default" => "<default value>",}
 *            {"default" => NULL_DATE,} (for datetime fields)
 *            {"primary" => "1",}
 *            {"foreign|relation" => ["<foreign key table name>" => "<foreign key field name>"],}
 *            "comment" => "Description of the fields"
 *        ],
 *        ...
 *    ],
 *    "indexes" => [
 *        "PRIMARY" => ["<primary key field name>", ...],
 *        "<index name>" => [{"UNIQUE",} "<field name>{(<key size>)}", ...]
 *        ...
 *    ],
 * ],
 *
 * Whenever possible prefer "foreign" before "relation" with the foreign keys.
 * "foreign" adds true foreign keys on the database level, while "relation" is just an indicator of a table relation without any consequences
 *
 * If you need to make any change, make sure to increment the DB_UPDATE_VERSION constant value below.
 *
 */

namespace Friendica\Test\Unit\Protocol;

use Friendica\Protocol\WebFingerUri;
use PHPUnit\Framework\TestCase;

class WebFingerUriTest extends TestCase
{
	public static function dataFromString(): array
	{
		return [
			'long' => [
				'expectedLong'  => 'acct:selma@www.example.com:8080/friend',
				'expectedShort' => 'selma@www.example.com:8080/friend',
				'input'         => 'acct:selma@www.example.com:8080/friend',
			],
			'short' => [
				'expectedLong'  => 'acct:selma@www.example.com:8080/friend',
				'expectedShort' => 'selma@www.example.com:8080/friend',
				'input'         => 'selma@www.example.com:8080/friend',
			],
			'minimal' => [
				'expectedLong'  => 'acct:bob@example.com',
				'expectedShort' => 'bob@example.com',
				'input'         => 'bob@example.com',
			],
			'acct:' => [
				'expectedLong'  => 'acct:alice@example.acct:90',
				'expectedShort' => 'alice@example.acct:90',
				'input'         => 'alice@example.acct:90',
			],
		];
	}

	/**
	 * @param string $expectedLong
	 * @param string $expectedShort
	 * @param string $input
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFromString')]
	public function testFromString(string $expectedLong, string $expectedShort, string $input): void
	{
		$uri = WebFingerUri::fromString($input);

		$this->assertEquals($expectedLong, $uri->getLongForm());
		$this->assertEquals($expectedShort, $uri->getShortForm());
	}

	public static function dataFromStringFailure()
	{
		return [
			'missing user' => [
				'input' => 'example.com',
			],
			'missing user @' => [
				'input' => '@example.com',
			],
			'missing host' => [
				'input' => 'alice',
			],
			'missing host @' => [
				'input' => 'alice@',
			],
			'missing everything' => [
				'input' => '',
			],
		];
	}

	/**
	 * @param string $input
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFromStringFailure')]
	public function testFromStringFailure(string $input): void
	{
		$this->expectException(\InvalidArgumentException::class);

		WebFingerUri::fromString($input);
	}
}

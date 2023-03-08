<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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

namespace Friendica\Test\src\Protocol;

use Friendica\Protocol\WebFingerUri;
use PHPUnit\Framework\TestCase;

class WebFingerUriTest extends TestCase
{
	public function dataFromString(): array
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
	 * @dataProvider dataFromString
	 * @param string $expectedLong
	 * @param string $expectedShort
	 * @param string $input
	 * @return void
	 */
	public function testFromString(string $expectedLong, string $expectedShort, string $input)
	{
		$uri = WebFingerUri::fromString($input);

		$this->assertEquals($expectedLong, $uri->getLongForm());
		$this->assertEquals($expectedShort, $uri->getShortForm());
	}

	public function dataFromStringFailure()
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
	 * @dataProvider dataFromStringFailure
	 * @param string $input
	 * @return void
	 */
	public function testFromStringFailure(string $input)
	{
		$this->expectException(\InvalidArgumentException::class);

		WebFingerUri::fromString($input);
	}
}

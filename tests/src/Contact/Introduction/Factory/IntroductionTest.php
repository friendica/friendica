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
 */

namespace Friendica\Test\src\Contact\Introduction\Factory;

use Friendica\Contact\Introduction\Factory\Introduction;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class IntroductionTest extends TestCase
{
	public function dataRow()
	{
		return [
			'default' => [
				'input' => [
					'uid'         => 42,
					'suggest-cid' => 13,
					'contact-id'  => 24,
					'knowyou'     => 1,
					'note'        => 'a note',
					'hash'        => '12345',
					'datetime'    => '1970-01-01 00:00:00',
					'ignore'      => 0,
					'id'          => 56,
				],
				'assertion' => [
					'uid'         => 42,
					'suggest-cid' => 13,
					'contact-id'  => 24,
					'knowyou'     => true,
					'note'        => 'a note',
					'hash'        => '12345',
					'datetime'    => new \DateTime('1970-01-01 00:00:00', new \DateTimeZone('UTC')),
					'ignore'      => false,
					'id'          => 56,
				]
			],
			'empty' => [
				'input' => [
				],
				'assertion' => [
					'uid'         => 0,
					'contact-id'  => 0,
					'suggest-cid' => null,
					'knowyou'     => false,
					'note'        => '',
					'ignore'      => false,
					'id'          => null,
				]
			],
		];
	}

	public function assertIntro(\Friendica\Contact\Introduction\Entity\Introduction $intro, array $assertion)
	{
		self::assertEquals($intro->id, $assertion['id'] ?? null);
		self::assertEquals($intro->uid, $assertion['uid'] ?? 0);
		self::assertEquals($intro->cid, $assertion['contact-id'] ?? 0);
		self::assertEquals($intro->sid, $assertion['suggest-cid'] ?? null);
		self::assertEquals($intro->knowyou, $assertion['knowyou'] ?? false);
		self::assertEquals($intro->note, $assertion['note'] ?? '');
		if (isset($assertion['hash'])) {
			self::assertEquals($intro->hash, $assertion['hash']);
		} else {
			self::assertIsString($intro->hash);
		}
		if (isset($assertion['datetime'])) {
			self::assertEquals($intro->datetime, $assertion['datetime']);
		} else {
			self::assertInstanceOf(\DateTime::class, $intro->datetime);
		}
		self::assertEquals($intro->ignore, $assertion['ignore'] ?? false);
	}

	/**
	 * @dataProvider dataRow
	 */
	public function testCreateFromTableRow(array $input, array $assertion)
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createFromTableRow($input);
		$this->assertIntro($intro, $assertion);
	}

	/**
	 * @dataProvider dataRow
	 */
	public function testCreateNew(array $input, array $assertion)
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createNew($input['uid'] ?? 0, $input['cid'] ?? 0, $input['note'] ?? '');

		$this->assertIntro($intro, [
			'uid'        => $input['uid'] ?? 0,
			'contact-id' => $input['cid'] ?? 0,
			'note'       => $input['note'] ?? '',
		]);
	}

	/**
	 * @dataProvider dataRow
	 */
	public function testCreateDummy(array $input, array $assertion)
	{
		$factory = new Introduction(new NullLogger());

		$intro = $factory->createDummy($input['id'] ?? null);

		$this->assertIntro($intro, ['id' => $input['id'] ?? null]);
	}
}

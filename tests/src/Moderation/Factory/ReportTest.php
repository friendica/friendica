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

namespace Friendica\Test\src\Moderation\Factory;

use Friendica\Moderation\Factory;
use Friendica\Moderation\Entity;
use Friendica\Test\MockedTest;
use Psr\Log\NullLogger;

class ReportTest extends MockedTest
{
	public function dataCreateFromTableRow(): array
	{
		return [
			'default' => [
				'row' => [
					'id'          => 11,
					'uid'         => 12,
					'reporter-id' => 14,
					'cid'         => 13,
					'comment'     => '',
					'category'    => null,
					'rules'       => '',
					'forward'     => false,
					'created'     => null
				],
				'postUriIds' => [],
				'assertion'  => new Entity\Report(
					14,
					13,
					new \DateTime('now', new \DateTimeZone('UTC')),
					'',
					null,
					'',
					false,
					[],
					12,
					11,
				),
			],
			'full' => [
				'row' => [
					'id'          => 11,
					'uid'         => 12,
					'reporter-id' => 14,
					'cid'         => 13,
					'comment'     => 'Report',
					'category'    => 'violation',
					'rules'       => 'Rules',
					'forward'     => true,
					'created'     => '2021-10-12 12:23:00'
				],
				'postUriIds' => [89, 90],
				'assertion'  => new Entity\Report(
					14,
					13,
					new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					'Report',
					'violation',
					'Rules',
					true,
					[89, 90],
					12,
					11
				),
			],
		];
	}

	public function assertReport(Entity\Report $assertion, Entity\Report $report)
	{
		self::assertEquals(
			$assertion->id,
			$report->id
		);
		self::assertEquals($assertion->uid, $report->uid);
		self::assertEquals($assertion->reporterId, $report->reporterId);
		self::assertEquals($assertion->cid, $report->cid);
		self::assertEquals($assertion->comment, $report->comment);
		self::assertEquals($assertion->category, $report->category);
		self::assertEquals($assertion->rules, $report->rules);
		self::assertEquals($assertion->forward, $report->forward);
		// No way to test "now" at the moment
		//self::assertEquals($assertion->created, $report->created);
		self::assertEquals($assertion->postUriIds, $report->postUriIds);
	}

	/**
	 * @dataProvider dataCreateFromTableRow
	 */
	public function testCreateFromTableRow(array $row, array $postUriIds, Entity\Report $assertion)
	{
		$factory = new Factory\Report(new NullLogger());

		$this->assertReport($factory->createFromTableRow($row, $postUriIds), $assertion);
	}

	public function dataCreateFromReportsRequest(): array
	{
		return [
			'default' => [
				'reporter-id' => 14,
				'cid'         => 13,
				'comment'     => '',
				'category'    => null,
				'rules'       => '',
				'forward'     => false,
				'postUriIds'  => [],
				'uid'         => 12,
				'assertion'   => new Entity\Report(
					14,
					13,
					new \DateTime('now', new \DateTimeZone('UTC')),
					'',
					null,
					'',
					false,
					[],
					12,
					null
				),
			],
			'full' => [
				'reporter-id' => 14,
				'cid'         => 13,
				'comment'     => 'Report',
				'category'    => 'violation',
				'rules'       => 'Rules',
				'forward'     => true,
				'postUriIds'  => [89, 90],
				'uid'         => 12,
				'assertion'   => new Entity\Report(
					14,
					13,
					new \DateTime('now', new \DateTimeZone('UTC')),
					'Report',
					'violation',
					'Rules',
					true,
					[89, 90],
					12,
					null
				),
			],
		];
	}

	/**
	 * @dataProvider dataCreateFromReportsRequest
	 */
	public function testCreateFromReportsRequest(int $reporter, int $cid, string $comment, string $category = null, string $rules = '', bool $forward, array $postUriIds, int $uid, Entity\Report $assertion)
	{
		$factory = new Factory\Report(new NullLogger());

		$this->assertReport($factory->createFromReportsRequest($reporter, $cid, $comment, $category, $rules, $forward, $postUriIds, $uid), $assertion);
	}
}

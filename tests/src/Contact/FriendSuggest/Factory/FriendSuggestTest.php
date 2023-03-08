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

namespace Friendica\Test\src\Contact\FriendSuggest\Factory;

use Friendica\Contact\FriendSuggest\Factory\FriendSuggest;
use Friendica\Contact\FriendSuggest\Entity;
use Friendica\Test\MockedTest;
use Psr\Log\NullLogger;

class FriendSuggestTest extends MockedTest
{
	public function dataCreate()
	{
		return [
			'default' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00'
				],
				'assertion' => new Entity\FriendSuggest(
					12,
					13,
					'test',
					'https://friendica.local/profile/test',
					'https://friendica.local/dfrn_request/test',
					'https://friendica.local/photo/profile/test',
					'a common note',
					new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC'))
				),
			],
			'minimum' => [
				'input' => [
					'id' => 20,
				],
				'assertion' => new Entity\FriendSuggest(
					0,
					0,
					'',
					'',
					'',
					'',
					'',
					new \DateTime('now', new \DateTimeZone('UTC')),
					20
				),
			],
			'full' => [
				'input' => [
					'uid'     => 12,
					'cid'     => 13,
					'name'    => 'test',
					'url'     => 'https://friendica.local/profile/test',
					'request' => 'https://friendica.local/dfrn_request/test',
					'photo'   => 'https://friendica.local/photo/profile/test',
					'note'    => 'a common note',
					'created' => '2021-10-12 12:23:00',
					'id'      => 666,
				],
				'assertion' => new Entity\FriendSuggest(
					12,
					13,
					'test',
					'https://friendica.local/profile/test',
					'https://friendica.local/dfrn_request/test',
					'https://friendica.local/photo/profile/test',
					'a common note',
					new \DateTime('2021-10-12 12:23:00', new \DateTimeZone('UTC')),
					666
				),
			],
		];
	}

	public function assertFriendSuggest(Entity\FriendSuggest $assertion, Entity\FriendSuggest $friendSuggest)
	{
		self::assertEquals($assertion->id, $friendSuggest->id);
		self::assertEquals($assertion->uid, $friendSuggest->uid);
		self::assertEquals($assertion->cid, $friendSuggest->cid);
		self::assertEquals($assertion->name, $friendSuggest->name);
		self::assertEquals($assertion->url, $friendSuggest->url);
		self::assertEquals($assertion->request, $friendSuggest->request);
		self::assertEquals($assertion->photo, $friendSuggest->photo);
		self::assertEquals($assertion->note, $friendSuggest->note);
	}

	public function testCreateNew()
	{
		$factory = new FriendSuggest(new NullLogger());

		$this->assertFriendSuggest(
			$factory->createNew(12, 13),
			new Entity\FriendSuggest(12, 13, '', '', '', '', '',
				new \DateTime('now', new \DateTimeZone('UTC')), null
			)
		);
	}

	/**
	 * @dataProvider dataCreate
	 */
	public function testCreateFromTableRow(array $input, Entity\FriendSuggest $assertion)
	{
		$factory = new FriendSuggest(new NullLogger());

		$this->assertFriendSuggest($factory->createFromTableRow($input), $assertion);
	}

	public function testCreateEmpty()
	{
		$factory = new FriendSuggest(new NullLogger());

		$this->assertFriendSuggest($factory->createEmpty(66), new Entity\FriendSuggest(0, 0, '', '', '', '', '',
			new \DateTime('now', new \DateTimeZone('UTC')), 66
		));
	}
}

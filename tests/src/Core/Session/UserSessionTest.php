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

namespace Friendica\Test\src\Core\Session;

use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Session\Type\ArraySession;
use Friendica\Test\MockedTest;

class UserSessionTest extends MockedTest
{
	public function dataLocalUserId()
	{
		return [
			'standard' => [
				'data' => [
					'authenticated' => true,
					'uid'           => 21,
				],
				'expected' => 21,
			],
			'not_auth' => [
				'data' => [
					'authenticated' => false,
					'uid'           => 21,
				],
				'expected' => false,
			],
			'no_uid' => [
				'data' => [
					'authenticated' => true,
				],
				'expected' => false,
			],
			'no_auth' => [
				'data' => [
					'uid' => 21,
				],
				'expected' => false,
			],
			'invalid' => [
				'data' => [
					'authenticated' => false,
					'uid'           => 'test',
				],
				'expected' => false,
			],
		];
	}

	/**
	 * @dataProvider dataLocalUserId
	 */
	public function testGetLocalUserId(array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->getLocalUserId());
	}

	public function testPublicContactId()
	{
		$this->markTestSkipped('Needs Contact::getIdForURL testable first');
	}

	public function dataGetRemoteUserId()
	{
		return [
			'standard' => [
				'data' => [
					'authenticated' => true,
					'visitor_id'    => 21,
				],
				'expected' => 21,
			],
			'not_auth' => [
				'data' => [
					'authenticated' => false,
					'visitor_id'    => 21,
				],
				'expected' => false,
			],
			'no_visitor_id' => [
				'data' => [
					'authenticated' => true,
				],
				'expected' => false,
			],
			'no_auth' => [
				'data' => [
					'visitor_id' => 21,
				],
				'expected' => false,
			],
			'invalid' => [
				'data' => [
					'authenticated' => false,
					'visitor_id'    => 'test',
				],
				'expected' => false,
			],
		];
	}

	/**
	 * @dataProvider dataGetRemoteUserId
	 */
	public function testGetRemoteUserId(array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->getRemoteUserId());
	}

	/// @fixme Add more data when Contact::getIdForUrl is a dynamic class
	public function dataGetRemoteContactId()
	{
		return [
			'remote_exists' => [
				'uid'  => 1,
				'data' => [
					'remote' => ['1' => '21'],
				],
				'expected' => 21,
			],
		];
	}

	/**
	 * @dataProvider dataGetRemoteContactId
	 */
	public function testGetRemoteContactId(int $uid, array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->getRemoteContactID($uid));
	}

	public function dataGetUserIdForVisitorContactID()
	{
		return [
			'standard' => [
				'cid'  => 21,
				'data' => [
					'remote' => ['3' => '21'],
				],
				'expected' => 3,
			],
			'missing' => [
				'cid'  => 2,
				'data' => [
					'remote' => ['3' => '21'],
				],
				'expected' => false,
			],
			'empty' => [
				'cid'  => 21,
				'data' => [
				],
				'expected' => false,
			],
		];
	}

	/** @dataProvider dataGetUserIdForVisitorContactID */
	public function testGetUserIdForVisitorContactID(int $cid, array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->getUserIDForVisitorContactID($cid));
	}

	public function dataAuthenticated()
	{
		return [
			'authenticated' => [
				'data' => [
					'authenticated' => true,
				],
				'expected' => true,
			],
			'not_authenticated' => [
				'data' => [
					'authenticated' => false,
				],
				'expected' => false,
			],
			'missing' => [
				'data' => [
				],
				'expected' => false,
			],
		];
	}

	/**
	 * @dataProvider dataAuthenticated
	 */
	public function testIsAuthenticated(array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->isAuthenticated());
	}
}

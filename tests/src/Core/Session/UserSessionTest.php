<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Session;

use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Session\Type\ArraySession;
use Friendica\Test\MockedTestCase;

class UserSessionTest extends MockedTestCase
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
				'expected' => 0,
			],
			'empty' => [
				'cid'  => 21,
				'data' => [
				],
				'expected' => 0,
			],
		];
	}

	/** @dataProvider dataGetUserIdForVisitorContactID */
	public function testGetUserIdForVisitorContactID(int $cid, array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertSame($expected, $userSession->getUserIDForVisitorContactID($cid));
	}

	public function dataAuthenticated()
	{
		return [
			'authenticated' => [
				'data' => [
					'authenticated' => true,
					'uid'           => 21,
				],
				'expected' => true,
			],
			'not_authenticated' => [
				'data' => [
					'authenticated' => false,
				],
				'expected' => false,
			],
			'remote_visitor' => [
				'data' => [
					'authenticated' => true,
					'visitor_id'    => 21,
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

	public function dataIsVisitor()
	{
		return [
			'local_user' => [
				'data' => [
					'authenticated' => true,
					'uid'           => 21,
				],
				'expected' => false,
			],
			'not_authenticated' => [
				'data' => [
					'authenticated' => false,
				],
				'expected' => false,
			],
			'remote_visitor' => [
				'data' => [
					'authenticated' => true,
					'visitor_id'    => 21,
				],
				'expected' => true,
			],
			'remote_unauthenticated_visitor' => [
				'data' => [
					'authenticated' => false,
					'visitor_id'    => 21,
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
	 * @dataProvider dataIsVisitor
	 */
	public function testIsVisitor(array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->isVisitor());
	}

	public function dataIsUnauthenticated()
	{
		return [
			'local_user' => [
				'data' => [
					'authenticated' => true,
					'uid'           => 21,
				],
				'expected' => false,
			],
			'not_authenticated' => [
				'data' => [
					'authenticated' => false,
				],
				'expected' => true,
			],
			'authenticated' => [
				'data' => [
					'authenticated' => true,
				],
				'expected' => false,
			],
			'remote_visitor' => [
				'data' => [
					'authenticated' => true,
					'visitor_id'    => 21,
				],
				'expected' => false,
			],
			'remote_unauthenticated_visitor' => [
				'data' => [
					'authenticated' => false,
					'visitor_id'    => 21,
				],
				'expected' => true,
			],
			'missing' => [
				'data' => [
				],
				'expected' => true,
			],
		];
	}

	/**
	 * @dataProvider dataIsUnauthenticated
	 */
	public function testIsUnauthenticated(array $data, $expected)
	{
		$userSession = new UserSession(new ArraySession($data));
		$this->assertEquals($expected, $userSession->isUnauthenticated());
	}
}

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

namespace Friendica\Test\src\Factory\Api\Twitter;

use Friendica\DI;
use Friendica\Factory\Api\Twitter\User;
use Friendica\Test\FixtureTest;
use Friendica\Test\src\Module\Api\ApiTest;

class UserTest extends FixtureTest
{
	/**
	 * Assert that an user array contains expected keys.
	 *
	 * @return void
	 */
	protected function assertSelfUser(array $user)
	{
		self::assertEquals(ApiTest::SELF_USER['id'], $user['uid']);
		self::assertEquals(ApiTest::SELF_USER['id'], $user['cid']);
		self::assertEquals('DFRN', $user['location']);
		self::assertEquals(ApiTest::SELF_USER['name'], $user['name']);
		self::assertEquals(ApiTest::SELF_USER['nick'], $user['screen_name']);
		self::assertTrue($user['verified']);
	}

	/**
	 * Test the api_get_user() function.
	 *
	 * @return void
	 */
	public function testApiGetUser()
	{
		$user = (new User(DI::logger(), DI::twitterStatus()))
			->createFromUserId(ApiTest::SELF_USER['id'])
			->toArray();

		$this->assertSelfUser($user);
	}

	/**
	 * Test the api_get_user() function with a Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithFrioSchema()
	{
		$this->markTestIncomplete('Needs missing fields for profile colors at API User object first.');

		/*
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'schema', 'red');

		$userFactory = new User(DI::logger(), DI::twitterStatus());
		$user        = $userFactory->createFromUserId(42);

		$this->assertSelfUser($user->toArray());
		self::assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		self::assertEquals('6fdbe8', $user['profile_link_color']);
		self::assertEquals('ededed', $user['profile_background_color']);
		*/
	}

	/**
	 * Test the api_get_user() function with an empty Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithEmptyFrioSchema()
	{
		$this->markTestIncomplete('Needs missing fields for profile colors at API User object first.');

		/*
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'schema', '---');

		$userFactory = new User(DI::logger(), DI::twitterStatus());
		$user        = $userFactory->createFromUserId(42);

		$this->assertSelfUser($user->toArray());
		self::assertEquals('708fa0', $user['profile_sidebar_fill_color']);
		self::assertEquals('6fdbe8', $user['profile_link_color']);
		self::assertEquals('ededed', $user['profile_background_color']);
		*/
	}

	/**
	 * Test the api_get_user() function with a custom Frio schema.
	 *
	 * @return void
	 */
	public function testApiGetUserWithCustomFrioSchema()
	{
		$this->markTestIncomplete('Needs missing fields for profile colors at API User object first.');

		/*
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'schema', '---');
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'nav_bg', '#123456');
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'link_color', '#123456');
		DI::pConfig()->set(ApiTest::SELF_USER['id'], 'frio', 'background_color', '#123456');

		$userFactory = new User(DI::logger(), DI::twitterStatus());
		$user        = $userFactory->createFromUserId(42);

		$this->assertSelfUser($user->toArray());
		self::assertEquals('123456', $user['profile_sidebar_fill_color']);
		self::assertEquals('123456', $user['profile_link_color']);
		self::assertEquals('123456', $user['profile_background_color']);
		*/
	}

	/**
	 * Test the api_get_user() function with a wrong user ID in a GET parameter.
	 *
	 * @return void
	 */
	public function testApiGetUserWithWrongGetId()
	{
		$user = (new User(DI::logger(), DI::twitterStatus()))
			->createFromUserId(-1)
			->toArray();

		self::assertEquals(0, $user['id']);
		self::assertEquals(0, $user['uid']);
		self::assertEquals(0, $user['cid']);
		self::assertEquals(0, $user['pid']);
		self::assertEmpty($user['name']);
	}

	/**
	 * Test the api_user() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiUserWithUnallowedUser()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		// self::assertEquals(false, api_user());
	}
}

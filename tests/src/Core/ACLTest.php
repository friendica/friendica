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

namespace Friendica\Test\src\Core;

use Friendica\Core\ACL;
use Friendica\Test\FixtureTest;

class ACLTest extends FixtureTest
{
	/**
	 * Test the ACL::isValidContact() function.
	 *
	 * @return void
	 */
	public function testCheckAclInput()
	{
		$result = ACL::isValidContact('<aclstring>', '42');
		self::assertFalse($result);
	}

	/**
	 * Test the ACL::isValidContact() function with an empty ACL string.
	 *
	 * @return void
	 */
	public function testCheckAclInputWithEmptyAclString()
	{
		$result = ACL::isValidContact('', '42');
		self::assertTrue($result);
	}
}

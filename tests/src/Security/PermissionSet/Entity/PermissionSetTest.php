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

namespace Friendica\Test\src\Security\PermissionSet\Entity;

use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Test\MockedTest;

class PermissionSetTest extends MockedTest
{
	public function dateAllowedContacts()
	{
		return [
			'default' => [
				'id'         => 10,
				'allow_cid'  => ['1', '2'],
				'allow_gid'  => ['3', '4'],
				'deny_cid'   => ['5', '6', '7'],
				'deny_gid'   => ['8'],
				'update_cid' => ['10'],
			],
		];
	}

	/**
	 * Test if the call "withAllowedContacts()" creates a clone
	 *
	 * @dataProvider dateAllowedContacts
	 */
	public function testWithAllowedContacts(int $id, array $allow_cid, array $allow_gid, array $deny_cid, array $deny_gid, array $update_cid)
	{
		$permissionSetOrig = new PermissionSet(
			$id,
			$allow_cid,
			$allow_gid,
			$deny_cid,
			$deny_gid
		);

		$permissionSetNew = $permissionSetOrig->withAllowedContacts($update_cid);

		self::assertNotSame($permissionSetOrig, $permissionSetNew);
		self::assertEquals($update_cid, $permissionSetNew->allow_cid);
		self::assertEquals($allow_cid, $permissionSetOrig->allow_cid);
	}
}

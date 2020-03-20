<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Model;

use Friendica\BaseModel;
use Friendica\DI;

/**
 * functions for interacting with the permission set of an object (item, photo, event, ...)
 *
 * @property int uid
 * @property string allow_cid
 * @property string allow_gid
 * @property string deny_cid
 * @property string deny_gid
 */
class PermissionSet extends BaseModel
{
	/**
	 * Fetch the id of a given permission set. Generate a new one when needed
	 *
	 * @param int         $uid
	 * @param string|null $allow_cid       Allowed contact IDs    - empty = everyone
	 * @param string|null $allow_gid       Allowed group IDs      - empty = everyone
	 * @param string|null $deny_cid        Disallowed contact IDs - empty = no one
	 * @param string|null $deny_gid        Disallowed group IDs   - empty = no 
	 * @param bool        $public_constant Return the "self::PUBLIC" constant for public ACL
	 * @return int id
	 * @throws \Exception
	 * @deprecated since 2020.03, use Repository\PermissionSet instead
	 * @see \Friendica\Repository\PermissionSet->getIdFromACL
	 */
	public static function getIdFromACL(
		int $uid,
		string $allow_cid = null,
		string $allow_gid = null,
		string $deny_cid = null,
		string $deny_gid = null,
		bool $public_constant = true
	) {
		return DI::permissionSet()->getIdFromACL($uid, $allow_cid, $allow_gid, $deny_cid, $deny_gid, $public_constant);
	}

	/**
	 * Returns a permission set for a given contact
	 *
	 * @param integer $uid        User id whom the items belong
	 * @param integer $contact_id Contact id of the visitor
	 *
	 * @return array of permission set ids.
	 * @throws \Exception
	 * @deprecated since 2020.03, use Repository\PermissionSet instead
	 * @see \Friendica\Repository\PermissionSet->selectByContactId
	 */
	public static function get($uid, $contact_id)
	{
		$permissionSets = DI::permissionSet()->selectByContactId($contact_id, $uid);

		return $permissionSets->column('id');
	}
}

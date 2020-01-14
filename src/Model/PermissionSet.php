<?php
/**
 * @file src/Model/PermissionSet.php
 */

namespace Friendica\Model;

use Friendica\BaseModel;
use Friendica\DI;

/**
 * functions for interacting with the permission set of an object (item, photo, event, ...)
 */
class PermissionSet extends BaseModel
{
	/**
	 * Fetch the id of a given permission set. Generate a new one when needed
	 *
	 * @param int         $uid
	 * @param string|null $allow_cid Allowed contact IDs    - empty = everyone
	 * @param string|null $allow_gid Allowed group IDs      - empty = everyone
	 * @param string|null $deny_cid  Disallowed contact IDs - empty = no one
	 * @param string|null $deny_gid  Disallowed group IDs   - empty = no one
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
		string $deny_gid = null
	) {
		return DI::permissionSet()->getIdFromACL($uid, $allow_cid, $allow_gid, $deny_cid, $deny_gid);
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

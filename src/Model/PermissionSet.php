<?php
/**
 * @file src/Model/PermissionSet.php
 */

namespace Friendica\Model;

use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * functions for interacting with the permission set of an object (item, photo, event, ...)
 */
class PermissionSet
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
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function getIdFromACL(
		int $uid,
		string $allow_cid = null,
		string $allow_gid = null,
		string $deny_cid = null,
		string $deny_gid = null
	) {
		$ACLFormatter = DI::aclFormatter();

		$allow_cid = $ACLFormatter->sanitize($allow_cid);
		$allow_gid = $ACLFormatter->sanitize($allow_gid);
		$deny_cid = $ACLFormatter->sanitize($deny_cid);
		$deny_gid = $ACLFormatter->sanitize($deny_gid);

		// Public permission
		if (!$allow_cid && !$allow_gid && !$deny_cid && !$deny_gid) {
			return 0;
		}

		$condition = [
			'uid' => $uid,
			'allow_cid' => $allow_cid,
			'allow_gid' => $allow_gid,
			'deny_cid'  => $deny_cid,
			'deny_gid'  => $deny_gid
		];
		$permissionset = DBA::selectFirst('permissionset', ['id'], $condition);

		if (DBA::isResult($permissionset)) {
			$psid = $permissionset['id'];
		} else {
			if (DBA::insert('permissionset', $condition, true)) {
				$psid = DBA::lastInsertId();
			} else {
				throw new HTTPException\InternalServerErrorException(L10n::t('Unable to create a new permission set.'));
			}
		}

		return $psid;
	}

	/**
	 * Returns a permission set for a given contact
	 *
	 * @param integer $uid        User id whom the items belong
	 * @param integer $contact_id Contact id of the visitor
	 *
	 * @return array of permission set ids.
	 * @throws \Exception
	 */
	static public function get($uid, $contact_id)
	{
		if (DBA::exists('contact', ['id' => $contact_id, 'uid' => $uid, 'blocked' => false])) {
			$groups = Group::getIdsByContactId($contact_id);
		}

		if (empty($groups) || !is_array($groups)) {
			return [];
		}

		$group_str = '<<>>'; // should be impossible to match

		foreach ($groups as $g) {
			$group_str .= '|<' . intval($g) . '>';
		}

		$contact_str = '<' . $contact_id . '>';

		$condition = ["`uid` = ? AND (NOT (`deny_cid` REGEXP ? OR deny_gid REGEXP ?)
			AND (allow_cid REGEXP ? OR allow_gid REGEXP ? OR (allow_cid = '' AND allow_gid = '')))",
			$uid, $contact_str, $group_str, $contact_str, $group_str];

		$ret = DBA::select('permissionset', ['id'], $condition);
		$set = [];
		while ($permission = DBA::fetch($ret)) {
			$set[] = $permission['id'];
		}
		DBA::close($ret);

		return $set;
	}
}

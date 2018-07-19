<?php
/**
 * @file src/Model/PermissionSet.php
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

/**
 * @brief functions for interacting with the permission set of an object (item, photo, event, ...)
 */
class PermissionSet extends BaseObject
{
	/**
	 * Fetch the id of a given permission set. Generate a new one when needed
	 *
	 * @param array $postarray The array from an item, picture or event post
	 * @return id
	 */
	public static function fetchIDForPost($postarray)
	{
		$condition = ['uid' => $postarray['uid'],
			'allow_cid' => self::sortPermissions($postarray['allow_cid']),
			'allow_gid' => self::sortPermissions($postarray['allow_gid']),
			'deny_cid' => self::sortPermissions($postarray['deny_cid']),
			'deny_gid' => self::sortPermissions($postarray['deny_gid'])];

		$set = dba::selectFirst('permissionset', ['id'], $condition);

		if (!DBM::is_result($set)) {
			dba::insert('permissionset', $condition, true);

			$set = dba::selectFirst('permissionset', ['id'], $condition);
		}
		return $set['id'];
	}

	private static function sortPermissions($permissionlist)
	{
		$cleaned_list = trim($permissionlist, '<>');

		if (empty($cleaned_list)) {
			return $permissionlist;
		}

		$elements = explode('><', $cleaned_list);

		if (count($elements) <= 1) {
			return $permissionlist;
		}

		asort($elements);

		return '<' . implode('><', $elements) . '>';
	}
}

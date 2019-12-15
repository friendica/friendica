<?php
/**
 * @file src/Model/PermissionSet.php
 */
namespace Friendica\Model;

use Friendica\Database\DBA;

/**
 * @brief functions for interacting with the permission set of an object (item, photo, event, ...)
 */
class PermissionSet
{
	/**
	 * Fetch the id of a given permission set. Generate a new one when needed
	 *
	 * @param array $postarray The array from an item, picture or event post
	 * @return int id
	 * @throws \Exception
	 */
	public static function fetchIDForPost(&$postarray)
	{
		$condition = ['uid' => $postarray['uid'],
			'allow_cid' => self::sortPermissions($postarray['allow_cid'] ?? ''),
			'allow_gid' => self::sortPermissions($postarray['allow_gid'] ?? ''),
			'deny_cid'  => self::sortPermissions($postarray['deny_cid']  ?? ''),
			'deny_gid'  => self::sortPermissions($postarray['deny_gid']  ?? '')];

		$set = DBA::selectFirst('permissionset', ['id'], $condition);

		if (!DBA::isResult($set)) {
			DBA::insert('permissionset', $condition, true);

			$set = DBA::selectFirst('permissionset', ['id'], $condition);
		}

		$postarray['allow_cid'] = null;
		$postarray['allow_gid'] = null;
		$postarray['deny_cid'] = null;
		$postarray['deny_gid'] = null;

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

	/**
	 * @brief Returns a permission set for a given contact
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

<?php

/**
 * @file src/Model/Group.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Database\DBM;
use dba;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';

/**
 * @brief functions for interacting with the group database table
 */
class Group extends BaseObject
{
	/**
	 * @brief Create a new contact group
	 *
	 * Note: If we found a deleted group with the same name, we restore it
	 *
	 * @param int $uid
	 * @param string $name
	 * @return boolean
	 */
	public static function create($uid, $name)
	{
		$return = false;
		if (x($uid) && x($name)) {
			$gid = self::getIdByName($uid, $name); // check for dupes
			if ($gid !== false) {
				// This could be a problem.
				// Let's assume we've just created a group which we once deleted
				// all the old members are gone, but the group remains so we don't break any security
				// access lists. What we're doing here is reviving the dead group, but old content which
				// was restricted to this group may now be seen by the new group members.
				$group = dba::selectFirst('group', ['deleted'], ['id' => $gid]);
				if (DBM::isResult($group) && $group['deleted']) {
					dba::update('group', ['deleted' => 0], ['gid' => $gid]);
					notice(t('A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.') . EOL);
				}
				return true;
			}

			$return = dba::insert('group', ['uid' => $uid, 'name' => $name]);
			if ($return) {
				$return = dba::lastInsertId();
			}
		}
		return $return;
	}

	/**
	 * @brief Get a list of group ids a contact belongs to
	 *
	 * @param int $cid
	 * @return array
	 */
	public static function getIdsByContactId($cid)
	{
		$condition = ['contact-id' => $cid];
		$stmt = dba::select('group_member', ['gid'], $condition);

		$return = [];

		while ($group = dba::fetch($stmt)) {
			$return[] = $group['gid'];
		}

		return $return;
	}

	/**
	 * @brief count unread group items
	 *
	 * Count unread items of each groups of the local user
	 *
	 * @return array
	 * 	'id' => group id
	 * 	'name' => group name
	 * 	'count' => counted unseen group items
	 */
	public static function countUnseen()
	{
		$stmt = dba::p("SELECT `group`.`id`, `group`.`name`,
				(SELECT COUNT(*) FROM `item` FORCE INDEX (`uid_unseen_contactid`)
					WHERE `uid` = ?
					AND `unseen`
					AND `contact-id` IN
						(SELECT `contact-id`
						FROM `group_member`
						WHERE `group_member`.`gid` = `group`.`id`)
					) AS `count`
				FROM `group`
				WHERE `group`.`uid` = ?;",
			local_user(),
			local_user()
		);

		return dba::inArray($stmt);
	}

	/**
	 * @brief Get the group id for a user/name couple
	 *
	 * Returns false if no group has been found.
	 *
	 * @param int $uid
	 * @param string $name
	 * @return int|boolean
	 */
	public static function getIdByName($uid, $name)
	{
		if (!$uid || !strlen($name)) {
			return false;
		}

		$group = dba::selectFirst('group', ['id'], ['uid' => $uid, 'name' => $name]);
		if (DBM::isResult($group)) {
			return $group['id'];
		}

		return false;
	}

	/**
	 * @brief Mark a group as deleted
	 *
	 * @param int $gid
	 * @return boolean
	 */
	public static function remove($gid) {
		if (! $gid) {
			return false;
		}

		$group = dba::selectFirst('group', ['uid'], ['gid' => $gid]);
		if (!DBM::isResult($group)) {
			return false;
		}

		// remove group from default posting lists
		$user = dba::selectFirst('user', ['def_gid', 'allow_gid', 'deny_gid'], ['uid' => $group['uid']]);
		if (DBM::isResult($user)) {
			$change = false;

			if ($user['def_gid'] == $gid) {
				$user['def_gid'] = 0;
				$change = true;
			}
			if (strpos($user['allow_gid'], '<' . $gid . '>') !== false) {
				$user['allow_gid'] = str_replace('<' . $gid . '>', '', $user['allow_gid']);
				$change = true;
			}
			if (strpos($user['deny_gid'], '<' . $gid . '>') !== false) {
				$user['deny_gid'] = str_replace('<' . $gid . '>', '', $user['deny_gid']);
				$change = true;
			}

			if ($change) {
				dba::update('user', $user, ['uid' => $group['uid']]);
			}
		}

		// remove all members
		dba::delete('group_member', ['gid' => $gid]);

		// remove group
		$return = dba::update('group', ['deleted' => 1], ['id' => $gid]);

		return $return;
	}

	/**
	 * @brief Mark a group as deleted based on its name
	 *
	 * @deprecated Use Group::remove instead
	 *
	 * @param int $uid
	 * @param string $name
	 * @return bool
	 */
	public static function removeByName($uid, $name) {
		$return = false;
		if (x($uid) && x($name)) {
			$gid = self::getIdByName($uid, $name);

			$return = self::remove($gid);
		}

		return $return;
	}

	/**
	 * @brief Adds a contact to a group
	 *
	 * @param int $gid
	 * @param int $cid
	 * @return boolean
	 */
	public static function addMember($gid, $cid)
	{
		if (!$gid || !$cid) {
			return false;
		}

		$row_exists = dba::exists('group_member', ['gid' => $gid, 'contact-id' => $cid]);
		if ($row_exists) {
			// Row already existing, nothing to do
			$return = true;
		} else {
			$return = dba::insert('group_member', ['gid' => $gid, 'contact-id' => $cid]);
		}

		return $return;
	}

	/**
	 * @brief Removes a contact from a group
	 *
	 * @param int $gid
	 * @param int $cid
	 * @return boolean
	 */
	public static function removeMember($gid, $cid)
	{
		if (!$gid || !$cid) {
			return false;
		}

		$return = dba::delete('group_member', ['gid' => $gid, 'contact-id' => $cid]);

		return $return;
	}

	/**
	 * @brief Removes a contact from a group based on its name
	 *
	 * @deprecated Use Group::removeMember instead
	 *
	 * @param int $uid
	 * @param string $name
	 * @param int $cid
	 * @return boolean
	 */
	public static function removeMemberByName($uid, $name, $cid)
	{
		$gid = self::getIdByName($uid, $name);

		$return = self::removeMember($gid, $cid);

		return $return;
	}

	/**
	 * @brief Returns the combined list of contact ids from a group id list
	 *
	 * @param array $group_ids
	 * @param boolean $check_dead
	 * @param boolean $use_gcontact
	 * @return array
	 */
	public static function expand($group_ids, $check_dead = false, $use_gcontact = false)
	{
		if (!is_array($group_ids) || !count($group_ids)) {
			return [];
		}

		$condition = '`gid` IN (' . substr(str_repeat("?, ", count($group_ids)), 0, -2) . ')';
		if ($use_gcontact) {
			$sql = 'SELECT `gcontact`.`id` AS `contact-id` FROM `group_member`
					INNER JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id`
					INNER JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
				WHERE ' . $condition;
			$param_arr = array_merge([$sql], $group_ids);
			$stmt = call_user_func_array('dba::p', $param_arr);
		} else {
			$condition_array = array_merge([$condition], $group_ids);
			$stmt = dba::select('group_member', ['contact-id'], $condition_array);
		}

		$return = [];
		while($group_member = dba::fetch($stmt)) {
			$return[] = $group_member['contact-id'];
		}

		if ($check_dead && !$use_gcontact) {
			require_once 'include/acl_selectors.php';
			$return = prune_deadguys($return);
		}
		return $return;
	}

	/**
	 * @brief Returns a templated group selection list
	 *
	 * @param int $uid
	 * @param int $gid An optional pre-selected group
	 * @param string $label An optional label of the list
	 * @return string
	 */
	public static function displayGroupSelection($uid, $gid = 0, $label = '')
	{
		$o = '';

		$stmt = dba::select('group', [], ['deleted' => 0, 'uid' => $uid], ['order' => ['name']]);

		$display_groups = [
			[
				'name' => '',
				'id' => '0',
				'selected' => ''
			]
		];
		while ($group = dba::fetch($stmt)) {
			$display_groups[] = [
				'name' => $group['name'],
				'id' => $group['id'],
				'selected' => $gid == $group['id'] ? 'true' : ''
			];
		}
		logger('groups: ' . print_r($display_groups, true));

		if ($label == '') {
			$label = t('Default privacy group for new contacts');
		}

		$o = replace_macros(get_markup_template('group_selection.tpl'), [
			'$label' => $label,
			'$groups' => $display_groups
		]);
		return $o;
	}

	/**
	 * @brief Create group sidebar widget
	 *
	 * @param string $every
	 * @param string $each
	 * @param string $editmode
	 * 	'standard' => include link 'Edit groups'
	 * 	'extended' => include link 'Create new group'
	 * 	'full' => include link 'Create new group' and provide for each group a link to edit this group
	 * @param int $group_id
	 * @param int $cid
	 * @return string
	 */
	public static function sidebarWidget($every = 'contacts', $each = 'group', $editmode = 'standard', $group_id = 0, $cid = 0)
	{
		$o = '';

		if (!local_user()) {
			return '';
		}

		$display_groups = [
			[
				'text' => t('Everybody'),
				'id' => 0,
				'selected' => (($group_id == 0) ? 'group-selected' : ''),
				'href' => $every,
			]
		];

		$stmt = dba::select('group', [], ['deleted' => 0, 'uid' => local_user()], ['order' => ['name']]);

		$member_of = [];
		if ($cid) {
			$member_of = self::getIdsByContactId($cid);
		}

		while ($group = dba::fetch($stmt)) {
			$selected = (($group_id == $group['id']) ? ' group-selected' : '');

			if ($editmode == 'full') {
				$groupedit = [
					'href' => 'group/' . $group['id'],
					'title' => t('edit'),
				];
			} else {
				$groupedit = null;
			}

			$display_groups[] = [
				'id'   => $group['id'],
				'cid'  => $cid,
				'text' => $group['name'],
				'href' => $each . '/' . $group['id'],
				'edit' => $groupedit,
				'selected' => $selected,
				'ismember' => in_array($group['id'], $member_of),
			];
		}

		$tpl = get_markup_template('group_side.tpl');
		$o = replace_macros($tpl, [
			'$add' => t('add'),
			'$title' => t('Groups'),
			'$groups' => $display_groups,
			'newgroup' => $editmode == 'extended' || $editmode == 'full' ? 1 : '',
			'grouppage' => 'group/',
			'$edittext' => t('Edit group'),
			'$ungrouped' => $every === 'contacts' ? t('Contacts not in any group') : '',
			'$createtext' => t('Create a new group'),
			'$creategroup' => t('Group Name: '),
			'$editgroupstext' => t('Edit groups'),
			'$form_security_token' => get_form_security_token('group_edit'),
		]);


		return $o;
	}
}

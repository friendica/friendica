<?php
/**
 * @file src/Model/Group.php
 */

namespace Friendica\Model;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;

/**
 * @brief functions for interacting with the group database table
 */
class Group
{
	const FOLLOWERS = '~';
	const MUTUALS = '&';

	public static function getByUserId($uid, $includesDeleted = false)
	{
		$conditions = ['uid' => $uid];

		if (!$includesDeleted) {
			$conditions['deleted'] = false;
		}

		return DBA::selectToArray('group', [], $conditions);
	}

	/**
	 * @param int $group_id
	 * @return bool
	 * @throws \Exception
	 */
	public static function exists($group_id, $uid = null)
	{
		$condition = ['id' => $group_id, 'deleted' => false];

		if (isset($uid)) {
			$condition = [
				'uid' => $uid
			];
		}

		return DBA::exists('group', $condition);
	}

	/**
	 * @brief Create a new contact group
	 *
	 * Note: If we found a deleted group with the same name, we restore it
	 *
	 * @param int    $uid
	 * @param string $name
	 * @return boolean
	 * @throws \Exception
	 */
	public static function create($uid, $name)
	{
		$return = false;
		if (!empty($uid) && !empty($name)) {
			$gid = self::getIdByName($uid, $name); // check for dupes
			if ($gid !== false) {
				// This could be a problem.
				// Let's assume we've just created a group which we once deleted
				// all the old members are gone, but the group remains so we don't break any security
				// access lists. What we're doing here is reviving the dead group, but old content which
				// was restricted to this group may now be seen by the new group members.
				$group = DBA::selectFirst('group', ['deleted'], ['id' => $gid]);
				if (DBA::isResult($group) && $group['deleted']) {
					DBA::update('group', ['deleted' => 0], ['id' => $gid]);
					notice(L10n::t('A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.') . EOL);
				}
				return true;
			}

			$return = DBA::insert('group', ['uid' => $uid, 'name' => $name]);
			if ($return) {
				$return = DBA::lastInsertId();
			}
		}
		return $return;
	}

	/**
	 * Update group information.
	 *
	 * @param int    $id   Group ID
	 * @param string $name Group name
	 *
	 * @return bool Was the update successful?
	 * @throws \Exception
	 */
	public static function update($id, $name)
	{
		return DBA::update('group', ['name' => $name], ['id' => $id]);
	}

	/**
	 * @brief Get a list of group ids a contact belongs to
	 *
	 * @param int $cid
	 * @return array
	 * @throws \Exception
	 */
	public static function getIdsByContactId($cid)
	{
		$return = [];

		$stmt = DBA::select('group_member', ['gid'], ['contact-id' => $cid]);
		while ($group = DBA::fetch($stmt)) {
			$return[] = $group['gid'];
		}
		DBA::close($stmt);

		return $return;
	}

	/**
	 * @brief count unread group items
	 *
	 * Count unread items of each groups of the local user
	 *
	 * @return array
	 *    'id' => group id
	 *    'name' => group name
	 *    'count' => counted unseen group items
	 * @throws \Exception
	 */
	public static function countUnseen()
	{
		$stmt = DBA::p("SELECT `group`.`id`, `group`.`name`,
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

		return DBA::toArray($stmt);
	}

	/**
	 * @brief Get the group id for a user/name couple
	 *
	 * Returns false if no group has been found.
	 *
	 * @param int    $uid
	 * @param string $name
	 * @return int|boolean
	 * @throws \Exception
	 */
	public static function getIdByName($uid, $name)
	{
		if (!$uid || !strlen($name)) {
			return false;
		}

		$group = DBA::selectFirst('group', ['id'], ['uid' => $uid, 'name' => $name]);
		if (DBA::isResult($group)) {
			return $group['id'];
		}

		return false;
	}

	/**
	 * @brief Mark a group as deleted
	 *
	 * @param int $gid
	 * @return boolean
	 * @throws \Exception
	 */
	public static function remove($gid)
	{
		if (!$gid) {
			return false;
		}

		$group = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (!DBA::isResult($group)) {
			return false;
		}

		// remove group from default posting lists
		$user = DBA::selectFirst('user', ['def_gid', 'allow_gid', 'deny_gid'], ['uid' => $group['uid']]);
		if (DBA::isResult($user)) {
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
				DBA::update('user', $user, ['uid' => $group['uid']]);
			}
		}

		// remove all members
		DBA::delete('group_member', ['gid' => $gid]);

		// remove group
		$return = DBA::update('group', ['deleted' => 1], ['id' => $gid]);

		return $return;
	}

	/**
	 * @brief      Mark a group as deleted based on its name
	 *
	 * @param int    $uid
	 * @param string $name
	 * @return bool
	 * @throws \Exception
	 * @deprecated Use Group::remove instead
	 *
	 */
	public static function removeByName($uid, $name)
	{
		$return = false;
		if (!empty($uid) && !empty($name)) {
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
	 * @throws \Exception
	 */
	public static function addMember($gid, $cid)
	{
		if (!$gid || !$cid) {
			return false;
		}

		$row_exists = DBA::exists('group_member', ['gid' => $gid, 'contact-id' => $cid]);
		if ($row_exists) {
			// Row already existing, nothing to do
			$return = true;
		} else {
			$return = DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $cid]);
		}

		return $return;
	}

	/**
	 * @brief Removes a contact from a group
	 *
	 * @param int $gid
	 * @param int $cid
	 * @return boolean
	 * @throws \Exception
	 */
	public static function removeMember($gid, $cid)
	{
		if (!$gid || !$cid) {
			return false;
		}

		$return = DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $cid]);

		return $return;
	}

	/**
	 * @brief      Removes a contact from a group based on its name
	 *
	 * @param int    $uid
	 * @param string $name
	 * @param int    $cid
	 * @return boolean
	 * @throws \Exception
	 * @deprecated Use Group::removeMember instead
	 *
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
	 * @param int     $uid
	 * @param array   $group_ids
	 * @param boolean $check_dead
	 * @return array
	 * @throws \Exception
	 */
	public static function expand($uid, array $group_ids, $check_dead = false)
	{
		if (!is_array($group_ids) || !count($group_ids)) {
			return [];
		}

		$return = [];
		$pubmail = false;
		$networks = Protocol::SUPPORT_PRIVATE;

		$mailacct = DBA::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', $uid]);
		if (DBA::isResult($mailacct)) {
			$pubmail = $mailacct['pubmail'];
		}

		if (!$pubmail) {
			$networks = array_diff($networks, [Protocol::MAIL]);
		}

		$key = array_search(self::FOLLOWERS, $group_ids);
		if ($key !== false) {
			$followers = Contact::selectToArray(['id'], [
				'uid' => $uid,
				'rel' => [Contact::FOLLOWER, Contact::FRIEND],
				'network' => $networks,
				'contact-type' => [Contact::TYPE_UNKNOWN, Contact::TYPE_PERSON],
				'archive' => false,
				'pending' => false,
				'blocked' => false,
			]);

			foreach ($followers as $follower) {
				$return[] = $follower['id'];
			}

			unset($group_ids[$key]);
		}

		$key = array_search(self::MUTUALS, $group_ids);
		if ($key !== false) {
			$mutuals = Contact::selectToArray(['id'], [
				'uid' => $uid,
				'rel' => [Contact::FRIEND],
				'network' => $networks,
				'contact-type' => [Contact::TYPE_UNKNOWN, Contact::TYPE_PERSON],
				'archive' => false,
				'pending' => false,
				'blocked' => false,
			]);

			foreach ($mutuals as $mutual) {
				$return[] = $mutual['id'];
			}

			unset($group_ids[$key]);
		}

		$stmt = DBA::select('group_member', ['contact-id'], ['gid' => $group_ids]);
		while ($group_member = DBA::fetch($stmt)) {
			$return[] = $group_member['contact-id'];
		}
		DBA::close($stmt);

		if ($check_dead) {
			Contact::pruneUnavailable($return);
		}

		return $return;
	}

	/**
	 * @brief Returns a templated group selection list
	 *
	 * @param int    $uid
	 * @param int    $gid   An optional pre-selected group
	 * @param string $label An optional label of the list
	 * @return string
	 * @throws \Exception
	 */
	public static function displayGroupSelection($uid, $gid = 0, $label = '')
	{
		$display_groups = [
			[
				'name' => '',
				'id' => '0',
				'selected' => ''
			]
		];

		$stmt = DBA::select('group', [], ['deleted' => 0, 'uid' => $uid], ['order' => ['name']]);
		while ($group = DBA::fetch($stmt)) {
			$display_groups[] = [
				'name' => $group['name'],
				'id' => $group['id'],
				'selected' => $gid == $group['id'] ? 'true' : ''
			];
		}
		DBA::close($stmt);

		Logger::info('Got groups', $display_groups);

		if ($label == '') {
			$label = L10n::t('Default privacy group for new contacts');
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('group_selection.tpl'), [
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
	 *    'standard' => include link 'Edit groups'
	 *    'extended' => include link 'Create new group'
	 *    'full' => include link 'Create new group' and provide for each group a link to edit this group
	 * @param string $group_id
	 * @param int    $cid
	 * @return string
	 * @throws \Exception
	 */
	public static function sidebarWidget($every = 'contact', $each = 'group', $editmode = 'standard', $group_id = '', $cid = 0)
	{
		if (!local_user()) {
			return '';
		}

		$display_groups = [
			[
				'text' => L10n::t('Everybody'),
				'id' => 0,
				'selected' => (($group_id === 'everyone') ? 'group-selected' : ''),
				'href' => $every,
			]
		];

		$member_of = [];
		if ($cid) {
			$member_of = self::getIdsByContactId($cid);
		}

		$stmt = DBA::select('group', [], ['deleted' => 0, 'uid' => local_user()], ['order' => ['name']]);
		while ($group = DBA::fetch($stmt)) {
			$selected = (($group_id == $group['id']) ? ' group-selected' : '');

			if ($editmode == 'full') {
				$groupedit = [
					'href' => 'group/' . $group['id'],
					'title' => L10n::t('edit'),
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
		DBA::close($stmt);

		// Don't show the groups on the network page when there is only one
		if ((count($display_groups) <= 2) && ($each == 'network')) {
			return '';
		}

		$tpl = Renderer::getMarkupTemplate('group_side.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$add' => L10n::t('add'),
			'$title' => L10n::t('Groups'),
			'$groups' => $display_groups,
			'newgroup' => $editmode == 'extended' || $editmode == 'full' ? 1 : '',
			'grouppage' => 'group/',
			'$edittext' => L10n::t('Edit group'),
			'$ungrouped' => $every === 'contact' ? L10n::t('Contacts not in any group') : '',
			'$ungrouped_selected' => (($group_id === 'none') ? 'group-selected' : ''),
			'$createtext' => L10n::t('Create a new group'),
			'$creategroup' => L10n::t('Group Name: '),
			'$editgroupstext' => L10n::t('Edit groups'),
			'$form_security_token' => BaseModule::getFormSecurityToken('group_edit'),
		]);

		return $o;
	}
}

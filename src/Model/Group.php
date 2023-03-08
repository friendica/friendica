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

namespace Friendica\Model;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;

/**
 * functions for interacting with the group database table
 */
class Group
{
	const FOLLOWERS = '~';
	const MUTUALS = '&';

	/**
	 * Fetches group record by user id and maybe includes deleted groups as well
	 *
	 * @param int  $uid User id to fetch group(s) for
	 * @param bool $includesDeleted Whether deleted groups should be included
	 * @return array|bool Array on success, bool on error
	 */
	public static function getByUserId(int $uid, bool $includesDeleted = false)
	{
		$conditions = ['uid' => $uid, 'cid' => null];

		if (!$includesDeleted) {
			$conditions['deleted'] = false;
		}

		return DBA::selectToArray('group', [], $conditions);
	}

	/**
	 * Checks whether given group id is found in database
	 *
	 * @param int $group_id Groupd it
	 * @param int $uid Optional user id
	 * @return bool
	 * @throws \Exception
	 */
	public static function exists(int $group_id, int $uid = null): bool
	{
		$condition = ['id' => $group_id, 'deleted' => false];

		if (!is_null($uid)) {
			$condition = [
				'uid' => $uid
			];
		}

		return DBA::exists('group', $condition);
	}

	/**
	 * Create a new contact group
	 *
	 * Note: If we found a deleted group with the same name, we restore it
	 *
	 * @param int    $uid User id to create group for
	 * @param string $name Name of group
	 * @return int|boolean Id of newly created group or false on error
	 * @throws \Exception
	 */
	public static function create(int $uid, string $name)
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
					DI::sysmsg()->addNotice(DI::l10n()->t('A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.'));
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
	public static function update(int $id, string $name): bool
	{
		return DBA::update('group', ['name' => $name], ['id' => $id]);
	}

	/**
	 * Get a list of group ids a contact belongs to
	 *
	 * @param int $cid Contact id
	 * @return array Group ids
	 * @throws \Exception
	 */
	public static function getIdsByContactId(int $cid): array
	{
		$contact = Contact::getById($cid, ['rel']);
		if (!$contact) {
			return [];
		}

		$groupIds = [];

		$stmt = DBA::select('group_member', ['gid'], ['contact-id' => $cid]);
		while ($group = DBA::fetch($stmt)) {
			$groupIds[] = $group['gid'];
		}
		DBA::close($stmt);

		// Meta-groups
		if ($contact['rel'] == Contact::FOLLOWER || $contact['rel'] == Contact::FRIEND) {
			$groupIds[] = self::FOLLOWERS;
		}

		if ($contact['rel'] == Contact::FRIEND) {
			$groupIds[] = self::MUTUALS;
		}

		return $groupIds;
	}

	/**
	 * count unread group items
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
				(SELECT COUNT(*) FROM `post-user`
					WHERE `uid` = ?
					AND `unseen`
					AND `contact-id` IN
						(SELECT `contact-id`
						FROM `group_member`
						WHERE `group_member`.`gid` = `group`.`id`)
					) AS `count`
				FROM `group`
				WHERE `group`.`uid` = ?;",
			DI::userSession()->getLocalUserId(),
			DI::userSession()->getLocalUserId()
		);

		return DBA::toArray($stmt);
	}

	/**
	 * Get the group id for a user/name couple
	 *
	 * Returns false if no group has been found.
	 *
	 * @param int    $uid User id
	 * @param string $name Group name
	 * @return int|boolean Groups' id number or false on error
	 * @throws \Exception
	 */
	public static function getIdByName(int $uid, string $name)
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
	 * Mark a group as deleted
	 *
	 * @param int $gid
	 * @return boolean
	 * @throws \Exception
	 */
	public static function remove(int $gid): bool
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
	 * Adds a contact to a group
	 *
	 * @param int $gid
	 * @param int $cid
	 * @return boolean
	 * @throws \Exception
	 */
	public static function addMember(int $gid, int $cid): bool
	{
		if (!$gid || !$cid) {
			return false;
		}

		// @TODO Backward compatibility with user contacts, remove by version 2022.03
		$group = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($group)) {
			throw new HTTPException\NotFoundException('Group not found.');
		}

		$cdata = Contact::getPublicAndUserContactID($cid, $group['uid']);
		if (empty($cdata['user'])) {
			throw new HTTPException\NotFoundException('Invalid contact.');
		}

		return DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $cdata['user']], Database::INSERT_IGNORE);
	}

	/**
	 * Removes a contact from a group
	 *
	 * @param int $gid
	 * @param int $cid
	 * @return boolean
	 * @throws \Exception
	 */
	public static function removeMember(int $gid, int $cid): bool
	{
		if (!$gid || !$cid) {
			return false;
		}

		// @TODO Backward compatibility with user contacts, remove by version 2022.03
		$group = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($group)) {
			throw new HTTPException\NotFoundException('Group not found.');
		}

		$cdata = Contact::getPublicAndUserContactID($cid, $group['uid']);
		if (empty($cdata['user'])) {
			throw new HTTPException\NotFoundException('Invalid contact.');
		}

		return DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $cid]);
	}

	/**
	 * Adds contacts to a group
	 *
	 * @param int $gid
	 * @param array $contacts Array with contact ids
	 * @return void
	 * @throws \Exception
	 */
	public static function addMembers(int $gid, array $contacts)
	{
		if (!$gid || !$contacts) {
			return;
		}

		// @TODO Backward compatibility with user contacts, remove by version 2022.03
		$group = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($group)) {
			throw new HTTPException\NotFoundException('Group not found.');
		}

		foreach ($contacts as $cid) {
			$cdata = Contact::getPublicAndUserContactID($cid, $group['uid']);
			if (empty($cdata['user'])) {
				throw new HTTPException\NotFoundException('Invalid contact.');
			}

			DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $cdata['user']], Database::INSERT_IGNORE);
		}
	}

	/**
	 * Removes contacts from a group
	 *
	 * @param int $gid Group id
	 * @param array $contacts Contact ids
	 * @return bool
	 * @throws \Exception
	 */
	public static function removeMembers(int $gid, array $contacts)
	{
		if (!$gid || !$contacts) {
			return false;
		}

		// @TODO Backward compatibility with user contacts, remove by version 2022.03
		$group = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($group)) {
			throw new HTTPException\NotFoundException('Group not found.');
		}

		$contactIds = [];

		foreach ($contacts as $cid) {
			$cdata = Contact::getPublicAndUserContactID($cid, $group['uid']);
			if (empty($cdata['user'])) {
				throw new HTTPException\NotFoundException('Invalid contact.');
			}

			$contactIds[] = $cdata['user'];
		}

		// Return status of deletion
		return DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $contactIds]);
	}

	/**
	 * Returns the combined list of contact ids from a group id list
	 *
	 * @param int     $uid User id
	 * @param array   $group_ids Groups ids
	 * @param boolean $check_dead Whether check "dead" records (?)
	 * @return array
	 * @throws \Exception
	 */
	public static function expand(int $uid, array $group_ids, bool $check_dead = false): array
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
			$return = Contact::pruneUnavailable($return);
		}

		return $return;
	}

	/**
	 * Returns a templated group selection list
	 *
	 * @param int    $uid User id
	 * @param int    $gid   An optional pre-selected group
	 * @param string $label An optional label of the list
	 * @return string
	 * @throws \Exception
	 */
	public static function displayGroupSelection(int $uid, int $gid = 0, string $label = ''): string
	{
		$display_groups = [
			[
				'name' => '',
				'id' => '0',
				'selected' => ''
			]
		];

		$stmt = DBA::select('group', [], ['deleted' => false, 'uid' => $uid, 'cid' => null], ['order' => ['name']]);
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
			$label = DI::l10n()->t('Default privacy group for new contacts');
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('group_selection.tpl'), [
			'$label' => $label,
			'$groups' => $display_groups
		]);
		return $o;
	}

	/**
	 * Create group sidebar widget
	 *
	 * @param string $every
	 * @param string $each
	 * @param string $editmode
	 *    'standard' => include link 'Edit groups'
	 *    'extended' => include link 'Create new group'
	 *    'full' => include link 'Create new group' and provide for each group a link to edit this group
	 * @param string|int $group_id Distinct group id or 'everyone'
	 * @param int    $cid Contact id
	 * @return string Sidebar widget HTML code
	 * @throws \Exception
	 */
	public static function sidebarWidget(string $every = 'contact', string $each = 'group', string $editmode = 'standard', $group_id = '', int $cid = 0)
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$display_groups = [
			[
				'text' => DI::l10n()->t('Everybody'),
				'id' => 0,
				'selected' => (($group_id === 'everyone') ? 'group-selected' : ''),
				'href' => $every,
			]
		];

		$member_of = [];
		if ($cid) {
			$member_of = self::getIdsByContactId($cid);
		}

		$stmt = DBA::select('group', [], ['deleted' => false, 'uid' => DI::userSession()->getLocalUserId(), 'cid' => null], ['order' => ['name']]);
		while ($group = DBA::fetch($stmt)) {
			$selected = (($group_id == $group['id']) ? ' group-selected' : '');

			if ($editmode == 'full') {
				$groupedit = [
					'href' => 'group/' . $group['id'],
					'title' => DI::l10n()->t('edit'),
				];
			} else {
				$groupedit = null;
			}

			if ($each == 'group') {
				$count = DBA::count('group_member', ['gid' => $group['id']]);
				$group_name = sprintf('%s (%d)', $group['name'], $count);
			} else {
				$group_name = $group['name'];
			}

			$display_groups[] = [
				'id'   => $group['id'],
				'cid'  => $cid,
				'text' => $group_name,
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
			'$add' => DI::l10n()->t('add'),
			'$title' => DI::l10n()->t('Groups'),
			'$groups' => $display_groups,
			'newgroup' => $editmode == 'extended' || $editmode == 'full' ? 1 : '',
			'grouppage' => 'group/',
			'$edittext' => DI::l10n()->t('Edit group'),
			'$ungrouped' => $every === 'contact' ? DI::l10n()->t('Contacts not in any group') : '',
			'$ungrouped_selected' => (($group_id === 'none') ? 'group-selected' : ''),
			'$createtext' => DI::l10n()->t('Create a new group'),
			'$creategroup' => DI::l10n()->t('Group Name: '),
			'$editgroupstext' => DI::l10n()->t('Edit groups'),
			'$form_security_token' => BaseModule::getFormSecurityToken('group_edit'),
		]);

		return $o;
	}

	/**
	 * Fetch the group id for the given contact id
	 *
	 * @param integer $id Contact ID
	 * @return integer Group IO
	 */
	public static function getIdForForum(int $id): int
	{
		Logger::info('Get id for forum id', ['id' => $id]);
		$contact = Contact::getById($id, ['uid', 'name', 'contact-type', 'manually-approve']);
		if (empty($contact) || ($contact['contact-type'] != Contact::TYPE_COMMUNITY) || !$contact['manually-approve']) {
			return 0;
		}

		$group = DBA::selectFirst('group', ['id'], ['uid' => $contact['uid'], 'cid' => $id]);
		if (empty($group)) {
			$fields = [
				'uid'  => $contact['uid'],
				'name' => $contact['name'],
				'cid'  => $id,
			];
			DBA::insert('group', $fields);
			$gid = DBA::lastInsertId();
		} else {
			$gid = $group['id'];
		}

		return $gid;
	}

	/**
	 * Fetch the followers of a given contact id and store them as group members
	 *
	 * @param integer $id Contact ID
	 * @return void
	 */
	public static function updateMembersForForum(int $id)
	{
		Logger::info('Update forum members', ['id' => $id]);

		$contact = Contact::getById($id, ['uid', 'url']);
		if (empty($contact)) {
			return;
		}

		$apcontact = APContact::getByURL($contact['url']);
		if (empty($apcontact['followers'])) {
			return;
		}

		$gid = self::getIdForForum($id);
		if (empty($gid)) {
			return;
		}

		$group_members = DBA::selectToArray('group_member', ['contact-id'], ['gid' => $gid]);
		if (!empty($group_members)) {
			$current = array_unique(array_column($group_members, 'contact-id'));
		} else {
			$current = [];
		}

		foreach (ActivityPub::fetchItems($apcontact['followers'], $contact['uid']) as $follower) {
			$id = Contact::getIdForURL($follower);
			if (!in_array($id, $current)) {
				DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $id]);
			} else {
				$key = array_search($id, $current);
				unset($current[$key]);
			}
		}

		DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $current]);
		Logger::info('Updated forum members', ['id' => $id, 'count' => DBA::count('group_member', ['gid' => $gid])]);
	}
}

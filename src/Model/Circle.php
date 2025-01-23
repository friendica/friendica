<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\BaseModule;
use Friendica\Content\Widget;
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
class Circle
{
	const FOLLOWERS = '~';
	const MUTUALS   = '&';

	/**
	 * Fetches circle record by user id and maybe includes deleted circles as well
	 *
	 * @param int  $uid User id to fetch circle(s) for
	 * @param bool $includesDeleted Whether deleted circles should be included
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
	 * Checks whether given circle id is found in database
	 *
	 * @param int $circle_id Circle id
	 * @param int $uid Optional user id
	 * @return bool
	 * @throws \Exception
	 */
	public static function exists(int $circle_id, int $uid = null): bool
	{
		$condition = ['id' => $circle_id, 'deleted' => false];

		if (!is_null($uid)) {
			$condition = [
				'uid' => $uid
			];
		}

		return DBA::exists('group', $condition);
	}

	/**
	 * Create a new contact circle
	 *
	 * Note: If we found a deleted circle with the same name, we restore it
	 *
	 * @param int    $uid User id to create circle for
	 * @param string $name Name of circle
	 * @return int|boolean Id of newly created circle or false on error
	 * @throws \Exception
	 */
	public static function create(int $uid, string $name)
	{
		$return = false;
		if (!empty($uid) && !empty($name)) {
			$gid = self::getIdByName($uid, $name); // check for dupes
			if ($gid !== false) {
				// This could be a problem.
				// Let's assume we've just created a circle which we once deleted
				// all the old members are gone, but the circle remains, so we don't break any security
				// access lists. What we're doing here is reviving the dead circle, but old content which
				// was restricted to this circle may now be seen by the new circle members.
				$circle = DBA::selectFirst('group', ['deleted'], ['id' => $gid]);
				if (DBA::isResult($circle) && $circle['deleted']) {
					DBA::update('group', ['deleted' => 0], ['id' => $gid]);
					DI::sysmsg()->addNotice(DI::l10n()->t('A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'));
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
	 * Update circle information.
	 *
	 * @param int    $id   Circle ID
	 * @param string $name Circle name
	 *
	 * @return bool Was the update successful?
	 * @throws \Exception
	 */
	public static function update(int $id, string $name): bool
	{
		return DBA::update('group', ['name' => $name], ['id' => $id]);
	}

	/**
	 * Get a list of circle ids a contact belongs to
	 *
	 * @param int $cid Contact id
	 * @return array Circle ids
	 * @throws \Exception
	 */
	public static function getIdsByContactId(int $cid): array
	{
		$contact = Contact::getById($cid, ['rel']);
		if (!$contact) {
			return [];
		}

		$circleIds = [];

		$stmt = DBA::select('group_member', ['gid'], ['contact-id' => $cid]);
		while ($circle = DBA::fetch($stmt)) {
			$circleIds[] = $circle['gid'];
		}
		DBA::close($stmt);

		// Meta-circles
		if ($contact['rel'] == Contact::FOLLOWER || $contact['rel'] == Contact::FRIEND) {
			$circleIds[] = self::FOLLOWERS;
		}

		if ($contact['rel'] == Contact::FRIEND) {
			$circleIds[] = self::MUTUALS;
		}

		return $circleIds;
	}

	/**
	 * count unread circle items
	 *
	 * Count unread items of each circle of the local user
	 *
	 * @param int $uid
	 * @return array
	 *    'id' => circle id
	 *    'name' => circle name
	 *    'count' => counted unseen circle items
	 * @throws \Exception
	 */
	public static function countUnseen(int $uid)
	{
		$stmt = DBA::p(
			"SELECT `circle`.`id`, `circle`.`name`,
				(SELECT COUNT(*) FROM `post-user`
					WHERE `uid` = ?
					AND `unseen`
					AND `contact-id` IN
						(SELECT `contact-id`
						FROM `group_member` AS `circle_member`
						WHERE `circle_member`.`gid` = `circle`.`id`)
					) AS `count`
				FROM `group` AS `circle`
				WHERE `circle`.`uid` = ?;",
			$uid,
			$uid
		);

		return DBA::toArray($stmt);
	}

	/**
	 * Get the circle id for a user/name couple
	 *
	 * Returns false if no circle has been found.
	 *
	 * @param int    $uid User id
	 * @param string $name Circle name
	 * @return int|boolean Circle's id number or false on error
	 * @throws \Exception
	 */
	public static function getIdByName(int $uid, string $name)
	{
		if (!$uid || !strlen($name)) {
			return false;
		}

		$circle = DBA::selectFirst('group', ['id'], ['uid' => $uid, 'name' => $name]);
		if (DBA::isResult($circle)) {
			return $circle['id'];
		}

		return false;
	}

	/**
	 * Mark a circle as deleted
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

		$circle = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (!DBA::isResult($circle)) {
			return false;
		}

		// remove circle from default posting lists
		$user = DBA::selectFirst('user', ['def_gid', 'allow_gid', 'deny_gid'], ['uid' => $circle['uid']]);
		if (DBA::isResult($user)) {
			$change = false;

			if ($user['def_gid'] == $gid) {
				$user['def_gid'] = 0;
				$change          = true;
			}
			if (strpos($user['allow_gid'], '<' . $gid . '>') !== false) {
				$user['allow_gid'] = str_replace('<' . $gid . '>', '', $user['allow_gid']);
				$change            = true;
			}
			if (strpos($user['deny_gid'], '<' . $gid . '>') !== false) {
				$user['deny_gid'] = str_replace('<' . $gid . '>', '', $user['deny_gid']);
				$change           = true;
			}

			if ($change) {
				DBA::update('user', $user, ['uid' => $circle['uid']]);
			}
		}

		// remove all members
		DBA::delete('group_member', ['gid' => $gid]);

		// remove circle
		$return = DBA::update('group', ['deleted' => 1], ['id' => $gid]);

		return $return;
	}

	/**
	 * Adds a contact to a circle
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
		$circle = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($circle)) {
			throw new HTTPException\NotFoundException('Circle not found.');
		}

		$ucid = Contact::getUserContactId($cid, $circle['uid']);
		if (!$ucid) {
			throw new HTTPException\NotFoundException('Invalid contact.');
		}

		return DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $ucid], Database::INSERT_IGNORE);
	}

	/**
	 * Removes a contact from a circle
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
		$circle = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($circle)) {
			throw new HTTPException\NotFoundException('Circle not found.');
		}

		$ucid = Contact::getUserContactId($cid, $circle['uid']);
		if (!$ucid) {
			throw new HTTPException\NotFoundException('Invalid contact.');
		}

		return DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $ucid]);
	}

	/**
	 * Adds contacts to a circle
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
		$circle = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($circle)) {
			throw new HTTPException\NotFoundException('Circle not found.');
		}

		foreach ($contacts as $cid) {
			$ucid = Contact::getUserContactId($cid, $circle['uid']);
			if (!$ucid) {
				throw new HTTPException\NotFoundException('Invalid contact.');
			}

			DBA::insert('group_member', ['gid' => $gid, 'contact-id' => $ucid], Database::INSERT_IGNORE);
		}
	}

	/**
	 * Removes contacts from a circle
	 *
	 * @param int $gid Circle id
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
		$circle = DBA::selectFirst('group', ['uid'], ['id' => $gid]);
		if (empty($circle)) {
			throw new HTTPException\NotFoundException('Circle not found.');
		}

		$contactIds = [];

		foreach ($contacts as $cid) {
			$ucid = Contact::getUserContactId($cid, $circle['uid']);
			if (!$ucid) {
				throw new HTTPException\NotFoundException('Invalid contact.');
			}

			$contactIds[] = $ucid;
		}

		// Return status of deletion
		return DBA::delete('group_member', ['gid' => $gid, 'contact-id' => $contactIds]);
	}

	/**
	 * Returns the combined list of contact ids from a circle id list
	 *
	 * @param int     $uid              User id
	 * @param array   $circle_ids        Circles ids
	 * @param boolean $check_dead       Whether check "dead" records (?)
	 * @param boolean $expand_followers Expand the list of followers
	 * @return array
	 * @throws \Exception
	 */
	public static function expand(int $uid, array $circle_ids, bool $check_dead = false, bool $expand_followers = true): array
	{
		if (!is_array($circle_ids) || !count($circle_ids)) {
			return [];
		}

		$return               = [];
		$pubmail              = false;
		$followers_collection = false;
		$networks             = Protocol::SUPPORT_PRIVATE;

		$mailacct = DBA::selectFirst('mailacct', ['pubmail'], ['`uid` = ? AND `server` != ""', $uid]);
		if (DBA::isResult($mailacct)) {
			$pubmail = $mailacct['pubmail'];
		}

		if (!$pubmail) {
			$networks = array_diff($networks, [Protocol::MAIL]);
		}

		$key = array_search(self::FOLLOWERS, $circle_ids);
		if ($key !== false) {
			if ($expand_followers) {
				$followers = Contact::selectToArray(['id'], [
					'uid'          => $uid,
					'rel'          => [Contact::FOLLOWER, Contact::FRIEND],
					'network'      => $networks,
					'contact-type' => [Contact::TYPE_UNKNOWN, Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION],
					'archive'      => false,
					'pending'      => false,
					'blocked'      => false,
				]);

				foreach ($followers as $follower) {
					$return[] = $follower['id'];
				}
			} else {
				$followers_collection = true;
			}
			unset($circle_ids[$key]);
		}

		$key = array_search(self::MUTUALS, $circle_ids);
		if ($key !== false) {
			$mutuals = Contact::selectToArray(['id'], [
				'uid'          => $uid,
				'rel'          => [Contact::FRIEND],
				'network'      => $networks,
				'contact-type' => [Contact::TYPE_UNKNOWN, Contact::TYPE_PERSON],
				'archive'      => false,
				'pending'      => false,
				'blocked'      => false,
			]);

			foreach ($mutuals as $mutual) {
				$return[] = $mutual['id'];
			}

			unset($circle_ids[$key]);
		}

		$stmt = DBA::select('group_member', ['contact-id'], ['gid' => $circle_ids]);
		while ($circle_member = DBA::fetch($stmt)) {
			$return[] = $circle_member['contact-id'];
		}
		DBA::close($stmt);

		if ($check_dead) {
			$return = Contact::pruneUnavailable($return);
		}

		if ($followers_collection) {
			$return[] = -1;
		}

		return $return;
	}

	/**
	 * Returns a templated circle selection list
	 *
	 * @param int    $uid User id
	 * @param int    $gid   A pre-selected circle
	 * @param string $id    The id of the option group
	 * @param string $label The label of the option group
	 * @return string
	 * @throws \Exception
	 */
	public static function getSelectorHTML(int $uid, int $gid, string $id, string $label): string
	{
		$display_circles = [
			[
				'name'     => '',
				'id'       => '0',
				'selected' => ''
			]
		];

		$stmt = DBA::select('group', [], ['deleted' => false, 'uid' => $uid, 'cid' => null], ['order' => ['name']]);
		while ($circle = DBA::fetch($stmt)) {
			$display_circles[] = [
				'name'     => $circle['name'],
				'id'       => $circle['id'],
				'selected' => $gid == $circle['id'] ? 'true' : ''
			];
		}
		DBA::close($stmt);

		DI::logger()->info('Got circles', $display_circles);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('circle_selection.tpl'), [
			'$id'      => $id,
			'$label'   => $label,
			'$circles' => $display_circles
		]);
		return $o;
	}

	/**
	 * Create circle sidebar widget
	 *
	 * @param string $every
	 * @param string $each
	 * @param string $editmode
	 *    'standard' => include link 'Edit circles'
	 *    'extended' => include link 'Create new circle'
	 *    'full' => include link 'Create new circle' and provide for each circle a link to edit this circle
	 * @param string|int $circle_id Distinct circle id or 'everyone'
	 * @param int    $cid Contact id
	 * @return string Sidebar widget HTML code
	 * @throws \Exception
	 */
	public static function sidebarWidget(string $every = 'contact', string $each = 'circle', string $editmode = 'standard', $circle_id = '', int $cid = 0)
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$display_circles = [
			[
				'text'     => DI::l10n()->t('Everybody'),
				'id'       => 0,
				'selected' => (($circle_id === 'everyone') ? 'circle-selected' : ''),
				'href'     => $every,
			]
		];

		$member_of = [];
		if ($cid) {
			$member_of = self::getIdsByContactId($cid);
		}

		$stmt = DBA::select('group', [], ['deleted' => false, 'uid' => DI::userSession()->getLocalUserId(), 'cid' => null], ['order' => ['name']]);
		while ($circle = DBA::fetch($stmt)) {
			$selected = (($circle_id == $circle['id']) ? ' circle-selected' : '');

			if ($editmode == 'full') {
				$circleedit = [
					'href'  => 'circle/' . $circle['id'],
					'title' => DI::l10n()->t('edit'),
				];
			} else {
				$circleedit = null;
			}

			if ($each == 'circle') {
				$networks   = Widget::unavailableNetworks();
				$sql_values = array_merge([$circle['id']], $networks);
				$condition  = ["`circle-id` = ? AND NOT `contact-network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")"];
				$condition  = array_merge($condition, $sql_values);

				$count       = DBA::count('circle-member-view', $condition);
				$circle_name = sprintf('%s (%d)', $circle['name'], $count);
			} else {
				$circle_name = $circle['name'];
			}

			$display_circles[] = [
				'id'       => $circle['id'],
				'cid'      => $cid,
				'text'     => $circle_name,
				'href'     => $each . '/' . $circle['id'],
				'edit'     => $circleedit,
				'selected' => $selected,
				'ismember' => in_array($circle['id'], $member_of),
			];
		}
		DBA::close($stmt);

		// Don't show the circles on the network page when there is only one
		if ((count($display_circles) <= 2) && ($each == 'network')) {
			return '';
		}

		$tpl = Renderer::getMarkupTemplate('circle_side.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$add'                 => DI::l10n()->t('add'),
			'$title'               => DI::l10n()->t('Circles'),
			'$circles'             => $display_circles,
			'$new_circle'          => $editmode == 'extended' || $editmode == 'full' ? 1 : '',
			'$circle_page'         => 'circle/',
			'$edittext'            => DI::l10n()->t('Edit circle'),
			'$uncircled'           => $every === 'contact' ? DI::l10n()->t('Contacts not in any circle') : '',
			'$uncircled_selected'  => (($circle_id === 'none') ? 'circle-selected' : ''),
			'$createtext'          => DI::l10n()->t('Create a new circle'),
			'$create_circle'       => DI::l10n()->t('Circle Name: '),
			'$edit_circles_text'   => DI::l10n()->t('Edit circles'),
			'$form_security_token' => BaseModule::getFormSecurityToken('circle_edit'),
		]);

		return $o;
	}

	/**
	 * Fetch the circle id for the given contact id
	 *
	 * @param integer $id Contact ID
	 * @return integer Circle ID
	 */
	public static function getIdForGroup(int $id): int
	{
		DI::logger()->info('Get id for group id', ['id' => $id]);
		$contact = Contact::getById($id, ['uid', 'name', 'contact-type', 'manually-approve']);
		if (empty($contact) || ($contact['contact-type'] != Contact::TYPE_COMMUNITY) || !$contact['manually-approve']) {
			return 0;
		}

		$circle = DBA::selectFirst('group', ['id'], ['uid' => $contact['uid'], 'cid' => $id]);
		if (empty($circle)) {
			$fields = [
				'uid'  => $contact['uid'],
				'name' => $contact['name'],
				'cid'  => $id,
			];
			DBA::insert('group', $fields);
			$gid = DBA::lastInsertId();
		} else {
			$gid = $circle['id'];
		}

		return $gid;
	}

	/**
	 * Fetch the followers of a given contact id and store them as circle members
	 *
	 * @param integer $id Contact ID
	 * @return void
	 */
	public static function updateMembersForGroup(int $id)
	{
		DI::logger()->info('Update group members', ['id' => $id]);

		$contact = Contact::getById($id, ['uid', 'url']);
		if (empty($contact)) {
			return;
		}

		$apcontact = APContact::getByURL($contact['url']);
		if (empty($apcontact['followers'])) {
			return;
		}

		$gid = self::getIdForGroup($id);
		if (empty($gid)) {
			return;
		}

		$circle_members = DBA::selectToArray('group_member', ['contact-id'], ['gid' => $gid]);
		if (!empty($circle_members)) {
			$current = array_unique(array_column($circle_members, 'contact-id'));
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
		DI::logger()->info('Updated group members', ['id' => $id, 'count' => DBA::count('group_member', ['gid' => $gid])]);
	}
}

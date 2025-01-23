<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Content\Text\HTML;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * This class handles methods related to the group functionality
 */
class GroupManager
{
	/**
	 * Function to list all groups a user is connected with
	 *
	 * @param int     $uid         of the profile owner
	 * @param boolean $lastitem    Sort by lastitem
	 * @param boolean $showhidden  Show groups which are not hidden
	 * @param boolean $showprivate Show private groups
	 *
	 * @return array
	 *    'url'    => group url
	 *    'name'    => group name
	 *    'id'    => number of the key from the array
	 *    'micro' => contact photo in format micro
	 *    'thumb' => contact photo in format thumb
	 * @throws \Exception
	 */
	public static function getList($uid, $lastitem, $showhidden = true, $showprivate = false)
	{
		if ($lastitem) {
			$params = ['order' => ['last-item' => true]];
		} else {
			$params = ['order' => ['name']];
		}

		$condition = [
			'contact-type' => Contact::TYPE_COMMUNITY,
			'network'      => [Protocol::DFRN, Protocol::ACTIVITYPUB],
			'uid'          => $uid,
			'blocked'      => false,
			'pending'      => false,
			'archive'      => false,
		];

		$condition = DBA::mergeConditions($condition, ["`platform` NOT IN (?, ?)", 'peertube', 'wordpress']);

		if (!$showprivate) {
			$condition = DBA::mergeConditions($condition, ['manually-approve' => false]);
		}

		if (!$showhidden) {
			$condition = DBA::mergeConditions($condition, ['hidden' => false]);
		}

		$groupList = [];

		$fields   = ['id', 'url', 'alias', 'name', 'micro', 'thumb', 'avatar', 'network', 'uid'];
		$contacts = DBA::select('account-user-view', $fields, $condition, $params);
		if (!$contacts) {
			return $groupList;
		}

		while ($contact = DBA::fetch($contacts)) {
			$groupList[] = [
				'url'   => $contact['url'],
				'alias' => $contact['alias'],
				'name'  => $contact['name'],
				'id'    => $contact['id'],
				'micro' => $contact['micro'],
				'thumb' => $contact['thumb'],
			];
		}
		DBA::close($contacts);

		return($groupList);
	}


	/**
	 * Group list widget
	 *
	 * Sidebar widget to show subscribed Friendica groups. If activated
	 * in the settings, it appears in the network page sidebar
	 *
	 * @param int $uid The ID of the User
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function widget(int $uid): string
	{
		//sort by last updated item
		$contacts      = self::getList($uid, true, true, true);
		$total         = count($contacts);
		$visibleGroups = 10;

		$id = 0;

		$entries = [];

		$contacts = [];

		foreach ($contacts as $contact) {
			$entry = [
				'url'          => 'contact/' . $contact['id'] . '/conversations',
				'external_url' => Contact::magicLinkByContact($contact),
				'name'         => $contact['name'],
				'cid'          => $contact['id'],
				'micro'        => DI::baseUrl()->remove(Contact::getMicro($contact)),
				'id'           => ++$id,
			];
			$entries[] = $entry;
		}

		$tpl = Renderer::getMarkupTemplate('widget/group_list.tpl');

		return Renderer::replaceMacros(
			$tpl,
			[
				'$title'            => DI::l10n()->t('Groups'),
				'$groups'           => $entries,
				'$link_desc'        => DI::l10n()->t('External link to group'),
				'$new_group_page'   => 'register/',
				'$total'            => $total,
				'$visible_groups'   => $visibleGroups,
				'$showless'         => DI::l10n()->t('show less'),
				'$showmore'         => DI::l10n()->t('show more'),
				'$create_new_group' => DI::l10n()->t('Create new group')
			],
		);
	}

	/**
	 * Format group list as contact block
	 *
	 * This function is used to show the group list in
	 * the advanced profile.
	 *
	 * @param int $uid The ID of the User
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function profileAdvanced($uid)
	{
		if (!Feature::isEnabled($uid, Feature::GROUPS)) {
			return '';
		}

		$o = '';

		// placeholder in case somebody wants configurability
		$show_total = 9999;

		//don't sort by last updated item
		$lastitem = false;

		$contacts = self::getList($uid, $lastitem, false, false);

		$total_shown = 0;
		foreach ($contacts as $contact) {
			$o .= HTML::micropro($contact, true, 'grouplist-profile-advanced');
			$total_shown++;
			if ($total_shown == $show_total) {
				break;
			}
		}

		return $o;
	}

	/**
	 * count unread group items
	 *
	 * Count unread items of connected groups and private groups
	 *
	 * @return array
	 *    'id' => contact id
	 *    'name' => contact/group name
	 *    'count' => counted unseen group items
	 * @throws \Exception
	 */
	public static function countUnseenItems()
	{
		$stmtContacts = DBA::p(
			"SELECT `contact`.`id`, `contact`.`name`, COUNT(*) AS `count` FROM `post-user-view`
				INNER JOIN `contact` ON `post-user-view`.`contact-id` = `contact`.`id`
				WHERE `post-user-view`.`uid` = ? AND `post-user-view`.`visible` AND NOT `post-user-view`.`deleted` AND `post-user-view`.`unseen`
				AND `contact`.`network` IN (?, ?) AND `contact`.`contact-type` = ?
				AND NOT `contact`.`blocked` AND NOT `contact`.`hidden`
				AND NOT `contact`.`pending` AND NOT `contact`.`archive`
				AND `contact`.`uid` = ?
				GROUP BY `contact`.`id`",
			DI::userSession()->getLocalUserId(),
			Protocol::DFRN,
			Protocol::ACTIVITYPUB,
			Contact::TYPE_COMMUNITY,
			DI::userSession()->getLocalUserId()
		);

		return DBA::toArray($stmtContacts);
	}
}

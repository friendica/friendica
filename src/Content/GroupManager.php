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
			'network' => [Protocol::DFRN, Protocol::ACTIVITYPUB],
			'uid' => $uid,
			'blocked' => false,
			'pending' => false,
			'archive' => false,
		];

		$condition = DBA::mergeConditions($condition, ["`platform` != ?", 'peertube']);

		if (!$showprivate) {
			$condition = DBA::mergeConditions($condition, ['manually-approve' => false]);
		}

		if (!$showhidden) {
			$condition = DBA::mergeConditions($condition, ['hidden' => false]);
		}

		$groupList = [];

		$fields = ['id', 'url', 'name', 'micro', 'thumb', 'avatar', 'network', 'uid'];
		$contacts = DBA::select('account-user-view', $fields, $condition, $params);
		if (!$contacts) {
			return $groupList;
		}

		while ($contact = DBA::fetch($contacts)) {
			$groupList[] = [
				'url'	=> $contact['url'],
				'name'	=> $contact['name'],
				'id'	=> $contact['id'],
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
	 * @param string $baseurl Base module path
	 * @param int    $uid     The ID of the User
	 * @param int    $cid     The contact id which is used to mark a group as "selected"
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function widget(string $baseurl, int $uid, int $cid = 0)
	{
		$o = '';

		//sort by last updated item
		$lastitem = true;

		$contacts = self::getList($uid, $lastitem, true, true);
		$total = count($contacts);
		$visibleGroups = 10;

		if (DBA::isResult($contacts)) {
			$id = 0;

			$entries = [];

			foreach ($contacts as $contact) {
				$selected = (($cid == $contact['id']) ? ' group-selected' : '');

				$entry = [
					'url' => $baseurl . '/' . $contact['id'],
					'external_url' => Contact::magicLinkByContact($contact),
					'name' => $contact['name'],
					'cid' => $contact['id'],
					'selected' 	=> $selected,
					'micro' => DI::baseUrl()->remove(Contact::getMicro($contact)),
					'id' => ++$id,
				];
				$entries[] = $entry;
			}

			$tpl = Renderer::getMarkupTemplate('widget/group_list.tpl');

			$o .= Renderer::replaceMacros(
				$tpl,
				[
					'$title'	=> DI::l10n()->t('Groups'),
					'$groups'	=> $entries,
					'$link_desc'	=> DI::l10n()->t('External link to group'),
					'$New_group_page' => 'register/',
					'$total'	=> $total,
					'$visible_groups' => $visibleGroups,
					'$showless'	=> DI::l10n()->t('show less'),
					'$showmore'	=> DI::l10n()->t('show more'),
					'$create_new_group' => DI::l10n()->t('Create new group')]
			);
		}

		return $o;
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
		$profile = intval(Feature::isEnabled($uid, 'forumlist_profile'));
		if (!$profile) {
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
			DI::userSession()->getLocalUserId(), Protocol::DFRN, Protocol::ACTIVITYPUB, Contact::TYPE_COMMUNITY, DI::userSession()->getLocalUserId()
		);

		return DBA::toArray($stmtContacts);
	}
}

<?php
/**
 * @file src/Content/ForumManager.php
 * @brief ForumManager class with its methods related to forum functionality
 */
namespace Friendica\Content;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\System;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

/**
 * @brief This class handles methods related to the forum functionality
 */
class ForumManager
{
	/**
	 * @brief Function to list all forums a user is connected with
	 *
	 * @param int     $uid         of the profile owner
	 * @param boolean $lastitem    Sort by lastitem
	 * @param boolean $showhidden  Show frorums which are not hidden
	 * @param boolean $showprivate Show private groups
	 *
	 * @return array
	 *	'url'	=> forum url
	 *	'name'	=> forum name
	 *	'id'	=> number of the key from the array
	 *	'micro' => contact photo in format micro
	 *	'thumb' => contact photo in format thumb
	 */
	public static function getList($uid, $lastitem, $showhidden = true, $showprivate = false)
	{
		$forumlist = [];

		$order = (($showhidden) ? '' : ' AND NOT `hidden` ');
		$order .= (($lastitem) ? ' ORDER BY `last-item` DESC ' : ' ORDER BY `name` ASC ');
		$select = '`forum` ';
		if ($showprivate) {
			$select = '(`forum` OR `prv`)';
		}

		$contacts = dba::p(
			"SELECT `contact`.`id`, `contact`.`url`, `contact`.`name`, `contact`.`micro`, `contact`.`thumb`
			FROM `contact`
				WHERE `network`= 'dfrn' AND $select AND `uid` = ?
				AND NOT `blocked` AND NOT `hidden` AND NOT `pending` AND NOT `archive`
				AND `success_update` > `failure_update`
			$order ",
			$uid
		);

		if (!$contacts) {
			return($forumlist);
		}

		while ($contact = dba::fetch($contacts)) {
			$forumlist[] = [
				'url'	=> $contact['url'],
				'name'	=> $contact['name'],
				'id'	=> $contact['id'],
				'micro' => $contact['micro'],
				'thumb' => $contact['thumb'],
			];
		}
		dba::close($contacts);

		return($forumlist);
	}


	/**
	 * @brief Forumlist widget
	 *
	 * Sidebar widget to show subcribed friendica forums. If activated
	 * in the settings, it appears at the notwork page sidebar
	 *
	 * @param int $uid The ID of the User
	 * @param int $cid The contact id which is used to mark a forum as "selected"
	 * @return string
	 */
	public static function widget($uid, $cid = 0)
	{
		if (! intval(Feature::isEnabled(local_user(), 'forumlist_widget'))) {
			return;
		}

		$o = '';

		//sort by last updated item
		$lastitem = true;

		$contacts = self::getList($uid, $lastitem, true, true);
		$total = count($contacts);
		$visible_forums = 10;

		if (DBM::isResult($contacts)) {
			$id = 0;

			foreach ($contacts as $contact) {
				$selected = (($cid == $contact['id']) ? ' forum-selected' : '');

				$entry = [
					'url' => 'network?f=&cid=' . $contact['id'],
					'external_url' => 'redir/' . $contact['id'],
					'name' => $contact['name'],
					'cid' => $contact['id'],
					'selected' 	=> $selected,
					'micro' => System::removedBaseUrl(proxy_url($contact['micro'], false, PROXY_SIZE_MICRO)),
					'id' => ++$id,
				];
				$entries[] = $entry;
			}

			$tpl = get_markup_template('widget_forumlist.tpl');

			$o .= replace_macros(
				$tpl,
				[
					'$title'	=> t('Forums'),
					'$forums'	=> $entries,
					'$link_desc'	=> t('External link to forum'),
					'$total'	=> $total,
					'$visible_forums' => $visible_forums,
					'$showmore'	=> t('show more')]
			);
		}

		return $o;
	}

	/**
	 * @brief Format forumlist as contact block
	 *
	 * This function is used to show the forumlist in
	 * the advanced profile.
	 *
	 * @param int $uid The ID of the User
	 * @return string
	 */
	public static function profileAdvanced($uid)
	{
		$profile = intval(Feature::isEnabled($uid, 'forumlist_profile'));
		if (! $profile) {
			return;
		}

		$o = '';

		// place holder in case somebody wants configurability
		$show_total = 9999;

		//don't sort by last updated item
		$lastitem = false;

		$contacts = self::getList($uid, $lastitem, false, false);

		$total_shown = 0;
		$forumlist = '';
		foreach ($contacts as $contact) {
			$forumlist .= micropro($contact, false, 'forumlist-profile-advanced');
			$total_shown ++;
			if ($total_shown == $show_total) {
				break;
			}
		}

		if (count($contacts) > 0) {
			$o .= $forumlist;
			return $o;
		}
	}

	/**
	 * @brief count unread forum items
	 *
	 * Count unread items of connected forums and private groups
	 *
	 * @return array
	 *	'id' => contact id
	 *	'name' => contact/forum name
	 *	'count' => counted unseen forum items
	 */
	public static function countUnseenItems()
	{
		$r = q(
			"SELECT `contact`.`id`, `contact`.`name`, COUNT(*) AS `count` FROM `item`
				INNER JOIN `contact` ON `item`.`contact-id` = `contact`.`id`
				WHERE `item`.`uid` = %d AND `item`.`visible` AND NOT `item`.`deleted` AND `item`.`unseen`
				AND `contact`.`network`= 'dfrn' AND (`contact`.`forum` OR `contact`.`prv`)
				AND NOT `contact`.`blocked` AND NOT `contact`.`hidden`
				AND NOT `contact`.`pending` AND NOT `contact`.`archive`
				AND `contact`.`success_update` > `failure_update`
				GROUP BY `contact`.`id` ",
			intval(local_user())
		);

		return $r;
	}
}

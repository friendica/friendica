<?php
/**
 * @file src/Content/ForumManager.php
 * ForumManager class with its methods related to forum functionality
 */
namespace Friendica\Content;

use Friendica\Content\Text\HTML;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Proxy as ProxyUtils;

/**
 * This class handles methods related to the forum functionality
 */
class ForumManager
{
	/**
	 * Function to list all forums a user is connected with
	 *
	 * @param int     $uid         of the profile owner
	 * @param boolean $lastitem    Sort by lastitem
	 * @param boolean $showhidden  Show frorums which are not hidden
	 * @param boolean $showprivate Show private groups
	 *
	 * @return array
	 *    'url'    => forum url
	 *    'name'    => forum name
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

		$condition_str = "`network` IN (?, ?) AND `uid` = ? AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND ";

		if ($showprivate) {
			$condition_str .= '(`forum` OR `prv`)';
		} else {
			$condition_str .= '`forum`';
		}

		if (!$showhidden) {
			$condition_str .=  ' AND NOT `hidden`';
		}

		$forumlist = [];

		$fields = ['id', 'url', 'name', 'micro', 'thumb'];
		$condition = [$condition_str, Protocol::DFRN, Protocol::ACTIVITYPUB, $uid];
		$contacts = DBA::select('contact', $fields, $condition, $params);
		if (!$contacts) {
			return($forumlist);
		}

		while ($contact = DBA::fetch($contacts)) {
			$forumlist[] = [
				'url'	=> $contact['url'],
				'name'	=> $contact['name'],
				'id'	=> $contact['id'],
				'micro' => $contact['micro'],
				'thumb' => $contact['thumb'],
			];
		}
		DBA::close($contacts);

		return($forumlist);
	}


	/**
	 * Forumlist widget
	 *
	 * Sidebar widget to show subcribed friendica forums. If activated
	 * in the settings, it appears at the notwork page sidebar
	 *
	 * @param int $uid The ID of the User
	 * @param int $cid The contact id which is used to mark a forum as "selected"
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function widget($uid, $cid = 0)
	{
		$o = '';

		//sort by last updated item
		$lastitem = true;

		$contacts = self::getList($uid, $lastitem, true, true);
		$total = count($contacts);
		$visible_forums = 10;

		if (DBA::isResult($contacts)) {
			$id = 0;

			$entries = [];

			foreach ($contacts as $contact) {
				$selected = (($cid == $contact['id']) ? ' forum-selected' : '');

				$entry = [
					'url' => 'network?cid=' . $contact['id'],
					'external_url' => Contact::magicLink($contact['url']),
					'name' => $contact['name'],
					'cid' => $contact['id'],
					'selected' 	=> $selected,
					'micro' => DI::baseUrl()->remove(ProxyUtils::proxifyUrl($contact['micro'], false, ProxyUtils::SIZE_MICRO)),
					'id' => ++$id,
				];
				$entries[] = $entry;
			}

			$tpl = Renderer::getMarkupTemplate('widget_forumlist.tpl');

			$o .= Renderer::replaceMacros(
				$tpl,
				[
					'$title'	=> DI::l10n()->t('Forums'),
					'$forums'	=> $entries,
					'$link_desc'	=> DI::l10n()->t('External link to forum'),
					'$total'	=> $total,
					'$visible_forums' => $visible_forums,
					'$showmore'	=> DI::l10n()->t('show more')]
			);
		}

		return $o;
	}

	/**
	 * Format forumlist as contact block
	 *
	 * This function is used to show the forumlist in
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

		// place holder in case somebody wants configurability
		$show_total = 9999;

		//don't sort by last updated item
		$lastitem = false;

		$contacts = self::getList($uid, $lastitem, false, false);

		$total_shown = 0;
		foreach ($contacts as $contact) {
			$o .= HTML::micropro($contact, true, 'forumlist-profile-advanced');
			$total_shown++;
			if ($total_shown == $show_total) {
				break;
			}
		}

		return $o;
	}

	/**
	 * count unread forum items
	 *
	 * Count unread items of connected forums and private groups
	 *
	 * @return array
	 *    'id' => contact id
	 *    'name' => contact/forum name
	 *    'count' => counted unseen forum items
	 * @throws \Exception
	 */
	public static function countUnseenItems()
	{
		$stmtContacts = DBA::p(
			"SELECT `contact`.`id`, `contact`.`name`, COUNT(*) AS `count` FROM `item`
				INNER JOIN `contact` ON `item`.`contact-id` = `contact`.`id`
				WHERE `item`.`uid` = ? AND `item`.`visible` AND NOT `item`.`deleted` AND `item`.`unseen`
				AND `contact`.`network`= 'dfrn' AND (`contact`.`forum` OR `contact`.`prv`)
				AND NOT `contact`.`blocked` AND NOT `contact`.`hidden`
				AND NOT `contact`.`pending` AND NOT `contact`.`archive`
				GROUP BY `contact`.`id` ",
			local_user()
		);

		return DBA::toArray($stmtContacts);
	}
}

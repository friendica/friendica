<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\Core\Addon;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class Widget
{
	/**
	 * Return the follow widget
	 *
	 * @param string $value optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function follow($value = "")
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/follow.tpl'), array(
			'$connect' => DI::l10n()->t('Add New Contact'),
			'$desc' => DI::l10n()->t('Enter address or web location'),
			'$hint' => DI::l10n()->t('Example: bob@example.com, http://example.com/barbara'),
			'$value' => $value,
			'$follow' => DI::l10n()->t('Connect')
		));
	}

	/**
	 * Return Find People widget
	 */
	public static function findPeople()
	{
		$global_dir = DI::config()->get('system', 'directory');

		if (DI::config()->get('system', 'invitation_only')) {
			$x = intval(DI::pConfig()->get(local_user(), 'system', 'invites_remaining'));
			if ($x || DI::app()->isSiteAdmin()) {
				DI::page()['aside'] .= '<div class="side-link widget" id="side-invite-remain">'
					. DI::l10n()->tt('%d invitation available', '%d invitations available', $x)
					. '</div>';
			}
		}

		$nv = [];
		$nv['findpeople'] = DI::l10n()->t('Find People');
		$nv['desc'] = DI::l10n()->t('Enter name or interest');
		$nv['label'] = DI::l10n()->t('Connect/Follow');
		$nv['hint'] = DI::l10n()->t('Examples: Robert Morgenstein, Fishing');
		$nv['findthem'] = DI::l10n()->t('Find');
		$nv['suggest'] = DI::l10n()->t('Friend Suggestions');
		$nv['similar'] = DI::l10n()->t('Similar Interests');
		$nv['random'] = DI::l10n()->t('Random Profile');
		$nv['inv'] = DI::l10n()->t('Invite Friends');
		$nv['directory'] = DI::l10n()->t('Global Directory');
		$nv['global_dir'] = $global_dir;
		$nv['local_directory'] = DI::l10n()->t('Local Directory');

		$aside = [];
		$aside['$nv'] = $nv;

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/peoplefind.tpl'), $aside);
	}

	/**
	 * Return unavailable networks as array
	 *
	 * @return array Unsupported networks
	 */
	public static function unavailableNetworks()
	{
		// Always hide content from these networks
		$networks = [Protocol::PHANTOM, Protocol::FACEBOOK, Protocol::APPNET, Protocol::ZOT];

		if (!Addon::isEnabled("discourse")) {
			$networks[] = Protocol::DISCOURSE;
		}

		if (!Addon::isEnabled("statusnet")) {
			$networks[] = Protocol::STATUSNET;
		}

		if (!Addon::isEnabled("pumpio")) {
			$networks[] = Protocol::PUMPIO;
		}

		if (!Addon::isEnabled("twitter")) {
			$networks[] = Protocol::TWITTER;
		}

		if (DI::config()->get("system", "ostatus_disabled")) {
			$networks[] = Protocol::OSTATUS;
		}

		if (!DI::config()->get("system", "diaspora_enabled")) {
			$networks[] = Protocol::DIASPORA;
		}

		if (!Addon::isEnabled("pnut")) {
			$networks[] = Protocol::PNUT;
		}
		return $networks;
	}

	/**
	 * Display a generic filter widget based on a list of options
	 *
	 * The options array must be the following format:
	 * [
	 *    [
	 *      'ref' => {filter value},
	 *      'name' => {option name}
	 *    ],
	 *    ...
	 * ]
	 *
	 * @param string $type The filter query string key
	 * @param string $title
	 * @param string $desc
	 * @param string $all The no filter label
	 * @param string $baseUrl The full page request URI
	 * @param array  $options
	 * @param string $selected The currently selected filter option value
	 * @return string
	 * @throws \Exception
	 */
	private static function filter($type, $title, $desc, $all, $baseUrl, array $options, $selected = null)
	{
		$queryString = parse_url($baseUrl, PHP_URL_QUERY);
		$queryArray = [];

		if ($queryString) {
			parse_str($queryString, $queryArray);
			unset($queryArray[$type]);

			if (count($queryArray)) {
				$baseUrl = substr($baseUrl, 0, strpos($baseUrl, '?')) . '?' . http_build_query($queryArray) . '&';
			} else {
				$baseUrl = substr($baseUrl, 0, strpos($baseUrl, '?')) . '?';
			}
		} else {
			$baseUrl = trim($baseUrl, '?') . '?';
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/filter.tpl'), [
			'$type'      => $type,
			'$title'     => $title,
			'$desc'      => $desc,
			'$selected'  => $selected,
			'$all_label' => $all,
			'$options'   => $options,
			'$base'      => $baseUrl,
		]);
	}

	/**
	 * Return group membership widget
	 *
	 * @param string $baseurl
	 * @param string $selected
	 * @return string
	 * @throws \Exception
	 */
	public static function groups($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		$options = array_map(function ($group) {
			return [
				'ref'  => $group['id'],
				'name' => $group['name']
			];
		}, Group::getByUserId(local_user()));

		return self::filter(
			'group',
			DI::l10n()->t('Groups'),
			'',
			DI::l10n()->t('Everyone'),
			$baseurl,
			$options,
			$selected
		);
	}

	/**
	 * Return contact relationship widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 * @return string
	 * @throws \Exception
	 */
	public static function contactRels($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		$options = [
			['ref' => 'followers', 'name' => DI::l10n()->t('Followers')],
			['ref' => 'following', 'name' => DI::l10n()->t('Following')],
			['ref' => 'mutuals', 'name' => DI::l10n()->t('Mutual friends')],
		];

		return self::filter(
			'rel',
			DI::l10n()->t('Relationships'),
			'',
			DI::l10n()->t('All Contacts'),
			$baseurl,
			$options,
			$selected
		);
	}

	/**
	 * Return networks widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networks($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		$networks = self::unavailableNetworks();
		$query = "`uid` = ? AND NOT `deleted` AND `network` != '' AND NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")";
		$condition = array_merge([$query], array_merge([local_user()], $networks));

		$r = DBA::select('contact', ['network'], $condition, ['group_by' => ['network'], 'order' => ['network']]);

		$nets = array();
		while ($rr = DBA::fetch($r)) {
			$nets[] = ['ref' => $rr['network'], 'name' => ContactSelector::networkToName($rr['network'])];
		}
		DBA::close($r);

		if (count($nets) < 2) {
			return '';
		}

		return self::filter(
			'nets',
			DI::l10n()->t('Protocols'),
			'',
			DI::l10n()->t('All Protocols'),
			$baseurl,
			$nets,
			$selected
		);
	}

	/**
	 * Return file as widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 * @return string|void
	 * @throws \Exception
	 */
	public static function fileAs($baseurl, $selected = '')
	{
		if (!local_user()) {
			return '';
		}

		$terms = [];
		foreach (Post\Category::getArray(local_user(), Post\Category::FILE) as $savedFolderName) {
			$terms[] = ['ref' => $savedFolderName, 'name' => $savedFolderName];
		}

		return self::filter(
			'file',
			DI::l10n()->t('Saved Folders'),
			'',
			DI::l10n()->t('Everything'),
			$baseurl,
			$terms,
			$selected
		);
	}

	/**
	 * Return categories widget
	 *
	 * @param string $baseurl  baseurl
	 * @param string $selected optional, default empty
	 * @return string|void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function categories($baseurl, $selected = '')
	{
		$a = DI::app();

		$uid = intval($a->getProfileOwner());

		if (!Feature::isEnabled($uid, 'categories')) {
			return '';
		}

		$terms = array();
		foreach (Post\Category::getArray(local_user(), Post\Category::CATEGORY) as $savedFolderName) {
			$terms[] = ['ref' => $savedFolderName, 'name' => $savedFolderName];
		}

		return self::filter(
			'category',
			DI::l10n()->t('Categories'),
			'',
			DI::l10n()->t('Everything'),
			$baseurl,
			$terms,
			$selected
		);
	}

	/**
	 * Show a random selection of five common contacts between the visitor and the viewed profile user.
	 *
	 * @param int    $uid      Viewed profile user ID
	 * @param string $nickname Viewed profile user nickname
	 * @return string|void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function commonFriendsVisitor(int $uid, string $nickname)
	{
		if (local_user() == $uid) {
			return '';
		}

		$visitorPCid = local_user() ? Contact::getPublicIdByUserId(local_user()) : remote_user();
		if (!$visitorPCid) {
			return '';
		}

		$localPCid = Contact::getPublicIdByUserId($uid);

		$condition = [
			'NOT `self` AND NOT `blocked` AND NOT `hidden` AND `id` != ?',
			$localPCid,
		];

		$total = Contact\Relation::countCommon($localPCid, $visitorPCid, $condition);
		if (!$total) {
			return '';
		}

		$commonContacts = Contact\Relation::listCommon($localPCid, $visitorPCid, $condition, 0, 5, true);
		if (!DBA::isResult($commonContacts)) {
			return '';
		}

		$entries = [];
		foreach ($commonContacts as $contact) {
			$entries[] = [
				'url'   => Contact::magicLinkByContact($contact),
				'name'  => $contact['name'],
				'photo' => Contact::getThumb($contact),
			];
		}

		$tpl = Renderer::getMarkupTemplate('widget/remote_friends_common.tpl');
		return Renderer::replaceMacros($tpl, [
			'$desc'     => DI::l10n()->tt("%d contact in common", "%d contacts in common", $total),
			'$base'     => DI::baseUrl(),
			'$nickname' => $nickname,
			'$linkmore' => $total > 5 ? 'true' : '',
			'$more'     => DI::l10n()->t('show more'),
			'$contacts' => $entries
		]);
	}

	/**
	 * Insert a tag cloud widget for the present profile.
	 *
	 * @param int $uid   User ID
	 * @param int $limit Max number of displayed tags.
	 * @return string HTML formatted output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function tagCloud(int $uid, int $limit = 50)
	{
		if (empty($uid)) {
			return '';
		}

		if (Feature::isEnabled($uid, 'tagadelic')) {
			$owner_id = Contact::getPublicIdByUserId($uid);

			if (!$owner_id) {
				return '';
			}
			return Widget\TagCloud::getHTML($uid, $limit, $owner_id, 'wall');
		}

		return '';
	}

	/**
	 * @param string $url Base page URL
	 * @param int    $uid User ID consulting/publishing posts
	 * @param bool   $wall True: Posted by User; False: Posted to User (network timeline)
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function postedByYear(string $url, int $uid, bool $wall)
	{
		$o = '';

		$visible_years = DI::pConfig()->get($uid, 'system', 'archive_visible_years', 5);

		/* arrange the list in years */
		$dnow = DateTimeFormat::localNow('Y-m-d');

		$ret = [];

		$cachekey = 'Widget::postedByYear' . $uid . '-' . (int)$wall;
		$dthen = DI::cache()->get($cachekey);
		if (empty($dthen)) {
			$dthen = Item::firstPostDate($uid, $wall);
			DI::cache()->set($cachekey, $dthen, Duration::HOUR);
		}

		if ($dthen) {
			// Set the start and end date to the beginning of the month
			$dnow = substr($dnow, 0, 8) . '01';
			$dthen = substr($dthen, 0, 8) . '01';

			/*
			 * Starting with the current month, get the first and last days of every
			 * month down to and including the month of the first post
			 */
			while (substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
				$dyear = intval(substr($dnow, 0, 4));
				$dstart = substr($dnow, 0, 8) . '01';
				$dend = substr($dnow, 0, 8) . Temporal::getDaysInMonth(intval($dnow), intval(substr($dnow, 5)));
				$start_month = DateTimeFormat::utc($dstart, 'Y-m-d');
				$end_month = DateTimeFormat::utc($dend, 'Y-m-d');
				$str = DI::l10n()->getDay(DateTimeFormat::utc($dnow, 'F'));

				if (empty($ret[$dyear])) {
					$ret[$dyear] = [];
				}

				$ret[$dyear][] = [$str, $end_month, $start_month];
				$dnow = DateTimeFormat::utc($dnow . ' -1 month', 'Y-m-d');
			}
		}

		if (!DBA::isResult($ret)) {
			return $o;
		}


		$cutoff_year = intval(DateTimeFormat::localNow('Y')) - $visible_years;
		$cutoff = array_key_exists($cutoff_year, $ret);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/posted_date.tpl'),[
			'$title' => DI::l10n()->t('Archives'),
			'$size' => $visible_years,
			'$cutoff_year' => $cutoff_year,
			'$cutoff' => $cutoff,
			'$url' => $url,
			'$dates' => $ret,
			'$showless' => DI::l10n()->t('show less'),
			'$showmore' => DI::l10n()->t('show more')
		]);

		return $o;
	}

	/**
	 * Display the account types sidebar
	 * The account type value is added as a parameter to the url
	 *
	 * @param string $base        Basepath
	 * @param int    $accounttype Acount type
	 * @return string
	 */
	public static function accounttypes(string $base, $accounttype)
	{
		$accounts = [
			['ref' => 'person', 'name' => DI::l10n()->t('Persons')],
			['ref' => 'organisation', 'name' => DI::l10n()->t('Organisations')],
			['ref' => 'news', 'name' => DI::l10n()->t('News')],
			['ref' => 'community', 'name' => DI::l10n()->t('Forums')],
		];

		return self::filter('accounttype', DI::l10n()->t('Account Types'), '',
			DI::l10n()->t('All'), $base, $accounts, $accounttype);
	}
}

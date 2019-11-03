<?php
/**
 * @file src/Content/Widget.php
 */
namespace Friendica\Content;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\FileTag;
use Friendica\Model\GContact;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
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
		$global_dir = Config::get('system', 'directory');

		if (Config::get('system', 'invitation_only')) {
			$x = intval(DI::pConfig()->get(local_user(), 'system', 'invites_remaining'));
			if ($x || is_site_admin()) {
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
	 * Return unavailable networks
	 */
	public static function unavailableNetworks()
	{
		// Always hide content from these networks
		$networks = ['face', 'apdn'];

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

		if (Config::get("system", "ostatus_disabled")) {
			$networks[] = Protocol::OSTATUS;
		}

		if (!Config::get("system", "diaspora_enabled")) {
			$networks[] = Protocol::DIASPORA;
		}

		if (!Addon::isEnabled("pnut")) {
			$networks[] = Protocol::PNUT;
		}

		if (!sizeof($networks)) {
			return "";
		}

		$network_filter = implode("','", $networks);

		$network_filter = "AND `network` NOT IN ('$network_filter')";

		return $network_filter;
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
	 * Return networks widget
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

		if (!Feature::isEnabled(local_user(), 'networks')) {
			return '';
		}

		$extra_sql = self::unavailableNetworks();

		$r = DBA::p("SELECT DISTINCT(`network`) FROM `contact` WHERE `uid` = ? AND NOT `deleted` AND `network` != '' $extra_sql ORDER BY `network`",
			local_user()
		);

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

		$saved = DI::pConfig()->get(local_user(), 'system', 'filetags');
		if (!strlen($saved)) {
			return;
		}

		$terms = [];
		foreach (FileTag::fileToArray($saved) as $savedFolderName) {
			$terms[] = ['ref' => $savedFolderName, 'name' => $savedFolderName];
		}
		
		usort($terms, function ($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

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

		$uid = intval($a->profile['uid']);

		if (!Feature::isEnabled($uid, 'categories')) {
			return '';
		}

		$saved = DI::pConfig()->get($uid, 'system', 'filetags');
		if (!strlen($saved)) {
			return;
		}

		$terms = array();
		foreach (FileTag::fileToArray($saved, 'category') as $savedFolderName) {
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
	 * Return common friends visitor widget
	 *
	 * @param string $profile_uid uid
	 * @return string|void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function commonFriendsVisitor($profile_uid)
	{
		if (local_user() == $profile_uid) {
			return;
		}

		$zcid = 0;

		$cid = Session::getRemoteContactID($profile_uid);

		if (!$cid) {
			if (Profile::getMyURL()) {
				$contact = DBA::selectFirst('contact', ['id'],
						['nurl' => Strings::normaliseLink(Profile::getMyURL()), 'uid' => $profile_uid]);
				if (DBA::isResult($contact)) {
					$cid = $contact['id'];
				} else {
					$gcontact = DBA::selectFirst('gcontact', ['id'], ['nurl' => Strings::normaliseLink(Profile::getMyURL())]);
					if (DBA::isResult($gcontact)) {
						$zcid = $gcontact['id'];
					}
				}
			}
		}

		if ($cid == 0 && $zcid == 0) {
			return;
		}

		if ($cid) {
			$t = GContact::countCommonFriends($profile_uid, $cid);
		} else {
			$t = GContact::countCommonFriendsZcid($profile_uid, $zcid);
		}

		if (!$t) {
			return;
		}

		if ($cid) {
			$r = GContact::commonFriends($profile_uid, $cid, 0, 5, true);
		} else {
			$r = GContact::commonFriendsZcid($profile_uid, $zcid, 0, 5, true);
		}

		if (!DBA::isResult($r)) {
			return;
		}

		$entries = [];
		foreach ($r as $rr) {
			$entry = [
				'url'   => Contact::magicLink($rr['url']),
				'name'  => $rr['name'],
				'photo' => ProxyUtils::proxifyUrl($rr['photo'], false, ProxyUtils::SIZE_THUMB),
			];
			$entries[] = $entry;
		}

		$tpl = Renderer::getMarkupTemplate('widget/remote_friends_common.tpl');
		return Renderer::replaceMacros($tpl, [
			'$desc'     => DI::l10n()->tt("%d contact in common", "%d contacts in common", $t),
			'$base'     => DI::baseUrl(),
			'$uid'      => $profile_uid,
			'$cid'      => (($cid) ? $cid : '0'),
			'$linkmore' => (($t > 5) ? 'true' : ''),
			'$more'     => DI::l10n()->t('show more'),
			'$items'    => $entries
		]);
	}

	/**
	 * Insert a tag cloud widget for the present profile.
	 *
	 * @param int $limit Max number of displayed tags.
	 * @return string HTML formatted output.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function tagCloud($limit = 50)
	{
		$a = DI::app();

		$uid = intval($a->profile['uid']);

		if (!$uid || !$a->profile['url']) {
			return '';
		}

		if (Feature::isEnabled($uid, 'tagadelic')) {
			$owner_id = Contact::getIdForURL($a->profile['url'], 0, true);

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

		if (!Feature::isEnabled($uid, 'archives')) {
			return $o;
		}

		$visible_years = DI::pConfig()->get($uid, 'system', 'archive_visible_years', 5);

		/* arrange the list in years */
		$dnow = DateTimeFormat::localNow('Y-m-d');

		$ret = [];

		$dthen = Item::firstPostDate($uid, $wall);
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
			'$showmore' => DI::l10n()->t('show more')
		]);

		return $o;
	}
}

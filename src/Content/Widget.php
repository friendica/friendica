<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Security\OpenWebAuth;
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
	public static function follow(string $value = ''): string
	{
		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/follow.tpl'), [
			'$connect' => DI::l10n()->t('Add New Contact'),
			'$desc'    => DI::l10n()->t('Enter address or web location'),
			'$hint'    => DI::l10n()->t('Example: bob@example.com, http://example.com/barbara'),
			'$value'   => $value,
			'$follow'  => DI::l10n()->t('Connect')
		]);
	}

	/**
	 * Return Find People widget
	 *
	 * @return string HTML code representing "People Widget"
	 */
	public static function findPeople(): string
	{
		$global_dir = Search::getGlobalDirectory();

		if (DI::config()->get('system', 'invitation_only')) {
			$x = intval(DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'invites_remaining'));
			if ($x || DI::userSession()->isSiteAdmin()) {
				DI::page()['aside'] .= '<div class="side-link widget" id="side-invite-remain">'
					. DI::l10n()->tt('%d invitation available', '%d invitations available', $x)
					. '</div>';
			}
		}

		$nv                    = [];
		$nv['findpeople']      = DI::l10n()->t('Find People');
		$nv['desc']            = DI::l10n()->t('Enter name or interest');
		$nv['label']           = DI::l10n()->t('Connect/Follow');
		$nv['hint']            = DI::l10n()->t('Examples: Robert Morgenstein, Fishing');
		$nv['findthem']        = DI::l10n()->t('Find');
		$nv['suggest']         = DI::l10n()->t('Friend Suggestions');
		$nv['similar']         = DI::l10n()->t('Similar Interests');
		$nv['random']          = DI::l10n()->t('Random Profile');
		$nv['inv']             = DI::l10n()->t('Invite Friends');
		$nv['directory']       = DI::l10n()->t('Global Directory');
		$nv['global_dir']      = OpenWebAuth::getZrlUrl($global_dir, true);
		$nv['local_directory'] = DI::l10n()->t('Local Directory');

		$aside        = [];
		$aside['$nv'] = $nv;

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/peoplefind.tpl'), $aside);
	}

	/**
	 * Return unavailable networks as array
	 *
	 * @return array Unsupported networks
	 */
	public static function unavailableNetworks(): array
	{
		$addonHelper = DI::addonHelper();

		// Always hide content from these networks
		$networks = [Protocol::PHANTOM, Protocol::FACEBOOK, Protocol::APPNET, Protocol::TWITTER, Protocol::ZOT, Protocol::OSTATUS, Protocol::STATUSNET];
		$addonHelper->loadAddons();

		if (!$addonHelper->isAddonEnabled('discourse')) {
			$networks[] = Protocol::DISCOURSE;
		}

		if (!$addonHelper->isAddonEnabled('pumpio')) {
			$networks[] = Protocol::PUMPIO;
		}

		if (!$addonHelper->isAddonEnabled('tumblr')) {
			$networks[] = Protocol::TUMBLR;
		}

		if (!DI::config()->get('system', 'diaspora_enabled')) {
			$networks[] = Protocol::DIASPORA;
		}

		if (!$addonHelper->isAddonEnabled('pnut')) {
			$networks[] = Protocol::PNUT;
		}
		return $networks;
	}

	/**
	 * Return available networks as array
	 *
	 * @return array Supported networks
	 */
	public static function availableNetworks(): array
	{
		$addonHelper = DI::addonHelper();

		$networks = [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::FEED];
		$addonHelper->loadAddons();

		if ($addonHelper->isAddonEnabled('discourse')) {
			$networks[] = Protocol::DISCOURSE;
		}

		if ($addonHelper->isAddonEnabled('pumpio')) {
			$networks[] = Protocol::PUMPIO;
		}

		if ($addonHelper->isAddonEnabled('tumblr')) {
			$networks[] = Protocol::TUMBLR;
		}

		if (DI::config()->get('system', 'diaspora_enabled')) {
			$networks[] = Protocol::DIASPORA;
		}

		if (function_exists('imap_open') && !DI::config()->get('system', 'imap_disabled')) {
			$networks[] = Protocol::MAIL;
		}

		if ($addonHelper->isAddonEnabled('pnut')) {
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
	private static function filter(string $type, string $title, string $desc, string $all, string $baseUrl, array $options, string $selected = null): string
	{
		$queryString = parse_url($baseUrl, PHP_URL_QUERY);
		$queryArray  = [];

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

		array_walk($options, function (&$value) {
			$value['ref'] = rawurlencode($value['ref']);
		});

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
	 * Return circle membership widget
	 *
	 * @param string $baseurl
	 * @param string $selected
	 * @return string
	 * @throws \Exception
	 */
	public static function circles(string $baseurl, string $selected = ''): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$options = array_map(function ($circle) {
			return [
				'ref'  => $circle['id'],
				'name' => $circle['name']
			];
		}, Circle::getByUserId(DI::userSession()->getLocalUserId()));

		return self::filter(
			'circle',
			DI::l10n()->t('Circles'),
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
	public static function contactRels(string $baseurl, string $selected = ''): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$options = [
			['ref' => 'followers', 'name' => DI::l10n()->t('Followers')],
			['ref' => 'following', 'name' => DI::l10n()->t('Following')],
			['ref' => 'mutuals', 'name' => DI::l10n()->t('Mutual friends')],
			['ref' => 'nothing', 'name' => DI::l10n()->t('No relationship')],
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
	public static function networks(string $baseurl, string $selected = ''): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$networks  = self::unavailableNetworks();
		$query     = "`uid` = ? AND NOT `deleted` AND `network` != '' AND NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")";
		$condition = array_merge([$query], array_merge([DI::userSession()->getLocalUserId()], $networks));

		$r = DBA::select('contact', ['network'], $condition, ['group_by' => ['network'], 'order' => ['network']]);

		$nets = [];
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
	 * @return string
	 * @throws \Exception
	 */
	public static function fileAs(string $baseurl, string $selected = ''): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return '';
		}

		$terms = [];
		foreach (Post\Category::getArray(DI::userSession()->getLocalUserId(), Post\Category::FILE) as $savedFolderName) {
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
	 * @param int    $uid      Id of the user owning the categories
	 * @param string $baseurl  Base page URL
	 * @param string $selected Selected category
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function categories(int $uid, string $baseurl, string $selected = ''): string
	{
		if (!Feature::isEnabled($uid, Feature::CATEGORIES)) {
			return '';
		}

		$terms = [];
		foreach (Post\Category::getArray($uid, Post\Category::CATEGORY) as $savedFolderName) {
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
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function commonFriendsVisitor(int $uid, string $nickname): string
	{
		if (DI::userSession()->getLocalUserId() == $uid) {
			return '';
		}

		$visitorPCid = DI::userSession()->getPublicContactId() ?: DI::userSession()->getRemoteUserId();
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

		$commonContacts = Contact\Relation::listCommon($localPCid, $visitorPCid, $condition, 0, 5);
		if (!DBA::isResult($commonContacts)) {
			return '';
		}

		$entries = [];
		foreach ($commonContacts as $contact) {
			$entries[] = [
				'url'   => Contact::magicLinkByContact($contact),
				'name'  => $contact['name'],
				'photo' => Contact::getThumb($contact, true),
			];
		}

		$tpl = Renderer::getMarkupTemplate('widget/remote_friends_common.tpl');
		return Renderer::replaceMacros($tpl, [
			'$desc'     => DI::l10n()->tt('%d contact in common', '%d contacts in common', $total),
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
	public static function tagCloud(int $uid, int $limit = 50): string
	{
		if (empty($uid)) {
			return '';
		}

		if (Feature::isEnabled($uid, Feature::TAGCLOUD)) {
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
	public static function postedByYear(string $url, int $uid, bool $wall): string
	{
		$o = '';

		$visible_years = DI::pConfig()->get($uid, 'system', 'archive_visible_years', 5);

		/* arrange the list in years */
		$dnow = DateTimeFormat::localNow('Y-m-d');

		$ret = [];

		$cachekey = 'Widget::postedByYear' . $uid . '-' . (int)$wall;
		$dthen    = DI::cache()->get($cachekey);
		if (empty($dthen)) {
			$dthen = Item::firstPostDate($uid, $wall);
			DI::cache()->set($cachekey, $dthen, Duration::HOUR);
		}

		$cutoffday = '';
		$thisday   = '';
		$nextday   = '';

		if ($dthen) {
			// Set the start and end date to the beginning of the month
			$cutoffday = $dthen;
			$thisday   = substr($dnow, 4);
			$nextday   = date('Y-m-d', strtotime($dnow . ' + 1 day'));
			$nextday   = substr($nextday, 4);
			$dnow      = substr($dnow, 0, 8) . '01';
			$dthen     = substr($dthen, 0, 8) . '01';

			/*
			 * Starting with the current month, get the first and last days of every
			 * month down to and including the month of the first post
			 */
			while (substr($dnow, 0, 7) >= substr($dthen, 0, 7)) {
				$dyear       = intval(substr($dnow, 0, 4));
				$dstart      = substr($dnow, 0, 8) . '01';
				$dend        = substr($dnow, 0, 8) . Temporal::getDaysInMonth(intval($dnow), intval(substr($dnow, 5)));
				$start_month = DateTimeFormat::utc($dstart, 'Y-m-d');
				$end_month   = DateTimeFormat::utc($dend, 'Y-m-d');
				$str         = DI::l10n()->getDay(DateTimeFormat::utc($dnow, 'F'));

				if (empty($ret[$dyear])) {
					$ret[$dyear] = [];
				}

				$ret[$dyear][] = [$str, $end_month, $start_month];
				$dnow          = DateTimeFormat::utc($dnow . ' -1 month', 'Y-m-d');
			}
		}

		if (!DBA::isResult($ret)) {
			return $o;
		}

		$cutoff_year = intval(DateTimeFormat::localNow('Y')) - $visible_years;
		$cutoff      = array_key_exists($cutoff_year, $ret);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/posted_date.tpl'), [
			'$title'       => DI::l10n()->t('Archives'),
			'$size'        => $visible_years,
			'$cutoff_year' => $cutoff_year,
			'$cutoff'      => $cutoff,
			'$url'         => $url,
			'$dates'       => $ret,
			'$showless'    => DI::l10n()->t('show less'),
			'$showmore'    => DI::l10n()->t('show more'),
			'$onthisdate'  => DI::l10n()->t('On this date'),
			'$thisday'     => $thisday,
			'$nextday'     => $nextday,
			'$cutoffday'   => $cutoffday
		]);

		return $o;
	}

	/**
	 * Display the account types sidebar
	 * The account type value is added as a parameter to the url
	 *
	 * @param string $base        Basepath
	 * @param string $accounttype Account type
	 * @return string
	 */
	public static function accountTypes(string $base, string $accounttype): string
	{
		$accounts = [
			['ref' => 'person', 'name' => DI::l10n()->t('Persons')],
			['ref' => 'organisation', 'name' => DI::l10n()->t('Organisations')],
			['ref' => 'news', 'name' => DI::l10n()->t('News')],
			['ref' => 'community', 'name' => DI::l10n()->t('Groups')],
			['ref' => 'relay', 'name' => DI::l10n()->t('Relays')],
		];

		return self::filter(
			'accounttype',
			DI::l10n()->t('Account Types'),
			'',
			DI::l10n()->t('All'),
			$base,
			$accounts,
			$accounttype
		);
	}

	/**
	 * Get a list of all channels
	 *
	 * @param string $base
	 * @param string $channelname
	 * @param integer $uid
	 * @return string
	 */
	public static function channels(string $base, string $channelname, int $uid): string
	{
		$channels = [];

		$enabled = DI::pConfig()->get($uid, 'system', 'enabled_timelines', []);

		foreach (DI::NetworkFactory()->getTimelines('') as $channel) {
			if (empty($enabled) || in_array($channel->code, $enabled)) {
				$channels[] = ['ref' => $channel->code, 'name' => $channel->label];
			}
		}

		foreach (DI::ChannelFactory()->getTimelines($uid) as $channel) {
			if (empty($enabled) || in_array($channel->code, $enabled)) {
				$channels[] = ['ref' => $channel->code, 'name' => $channel->label];
			}
		}

		foreach (DI::userDefinedChannel()->selectByUid($uid) as $channel) {
			if (empty($enabled) || in_array($channel->code, $enabled)) {
				$channels[] = ['ref' => $channel->code, 'name' => $channel->label];
			}
		}

		foreach (DI::CommunityFactory()->getTimelines(true) as $community) {
			if (empty($enabled) || in_array($community->code, $enabled)) {
				$channels[] = ['ref' => $community->code, 'name' => $community->label];
			}
		}

		return self::filter(
			'channel',
			DI::l10n()->t('Channels'),
			'',
			'',
			$base,
			$channels,
			$channelname
		);
	}
}

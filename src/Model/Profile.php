<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Widget\ContactBlock;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Diaspora;
use Friendica\Security\PermissionSet\Entity\PermissionSet;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;

class Profile
{
	/**
	 * Returns default profile for a given user id
	 *
	 * @param int $uid User ID
	 *
	 * @return array|bool Profile data or false on error
	 * @throws \Exception
	 */
	public static function getByUID(int $uid)
	{
		return DBA::selectFirst('profile', [], ['uid' => $uid]);
	}

	/**
	 * Returns default profile for a given user ID and ID
	 *
	 * @param int $uid The contact ID
	 * @param int $id The contact owner ID
	 * @param array $fields The selected fields
	 *
	 * @return array|bool Profile data for the ID or false on error
	 * @throws \Exception
	 */
	public static function getById(int $uid, int $id, array $fields = [])
	{
		return DBA::selectFirst('profile', $fields, ['uid' => $uid, 'id' => $id]);
	}

	/**
	 * Returns profile data for the contact owner
	 *
	 * @param int   $uid    The User ID
	 * @param array $fields The fields to retrieve or false on error
	 *
	 * @return array Array of profile data
	 * @throws \Exception
	 */
	public static function getListByUser(int $uid, array $fields = [])
	{
		return DBA::selectToArray('profile', $fields, ['uid' => $uid]);
	}

	/**
	 * Update a profile entry and distribute the changes if needed
	 *
	 * @param array   $fields Profile fields to update
	 * @param integer $uid    User id
	 *
	 * @return boolean Whether update was successful
	 * @throws \Exception
	 */
	public static function update(array $fields, int $uid): bool
	{
		$old_owner = User::getOwnerDataById($uid);
		if (empty($old_owner)) {
			return false;
		}

		if (!DBA::update('profile', $fields, ['uid' => $uid])) {
			return false;
		}

		$update = Contact::updateSelfFromUserID($uid);

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			return false;
		}

		$profile_fields = ['postal-code', 'dob', 'prv_keywords', 'homepage'];
		foreach ($profile_fields as $field) {
			if ($old_owner[$field] != $owner[$field]) {
				$update = true;
			}
		}

		if ($update) {
			self::publishUpdate($uid, ($old_owner['net-publish'] != $owner['net-publish']));
		}

		return true;
	}

	/**
	 * Publish a changed profile
	 *
	 * @param int  $uid User id
	 * @param bool $force Force publishing to the directory
	 *
	 * @return void
	 */
	public static function publishUpdate(int $uid, bool $force = false)
	{
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			return;
		}

		if ($owner['net-publish'] || $force) {
			// Update global directory in background
			if (Search::getGlobalDirectory()) {
				Worker::add(Worker::PRIORITY_LOW, 'Directory', $owner['url']);
			}
		}

		Worker::add(Worker::PRIORITY_LOW, 'ProfileUpdate', $uid);
	}

	/**
	 * Returns a formatted location string from the given profile array
	 *
	 * @param array $profile Profile array (Generated from the "profile" table)
	 *
	 * @return string Location string
	 */
	public static function formatLocation(array $profile): string
	{
		$location = '';

		if (!empty($profile['locality'])) {
			$location .= $profile['locality'];
		}

		if (!empty($profile['region']) && (($profile['locality'] ?? '') != $profile['region'])) {
			if ($location) {
				$location .= ', ';
			}

			$location .= $profile['region'];
		}

		if (!empty($profile['country-name'])) {
			if ($location) {
				$location .= ', ';
			}

			$location .= $profile['country-name'];
		}

		return $location;
	}

	/**
	 * Loads a profile into the page sidebar.
	 *
	 * The function requires a writeable copy of the main App structure, and the nickname
	 * of a registered local account.
	 *
	 * If the viewer is an authenticated remote viewer, the profile displayed is the
	 * one that has been configured for his/her viewing in the Contact manager.
	 * Passing a non-zero profile ID can also allow a preview of a selected profile
	 * by the owner.
	 *
	 * Profile information is placed in the App structure for later retrieval.
	 * Honours the owner's chosen theme for display.
	 *
	 * @attention Should only be run in the _init() functions of a module. That ensures that
	 *      the theme is chosen before the _init() function of a theme is run, which will usually
	 *      load a lot of theme-specific content
	 *
	 * @param string $nickname string
	 * @param bool   $show_contacts
	 *
	 * @return array Profile
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function load(AppHelper $appHelper, string $nickname, bool $show_contacts = true): array
	{
		$profile = User::getOwnerDataByNick($nickname);
		if (!isset($profile['account_removed']) || $profile['account_removed']) {
			DI::logger()->info('profile error: ' . DI::args()->getQueryString());
			return [];
		}

		// System user, aborting
		if ($profile['uid'] === 0) {
			DI::logger()->warning('System user found in Profile::load', ['nickname' => $nickname]);
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$appHelper->setProfileOwner($profile['uid']);

		DI::page()['title'] = $profile['name'] . ' @ ' . DI::config()->get('config', 'sitename');

		if (!DI::userSession()->getLocalUserId()) {
			$appHelper->setCurrentTheme($profile['theme']);
			$appHelper->setCurrentMobileTheme(DI::pConfig()->get($appHelper->getProfileOwner(), 'system', 'mobile_theme') ?? '');
		}

		/*
		* load/reload current theme info
		*/

		Renderer::setActiveTemplateEngine(); // reset the template engine to the default in case the user's theme doesn't specify one

		$theme_info_file = 'view/theme/' . $appHelper->getCurrentTheme() . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		$block = (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated());

		/**
		 * @todo
		 * By now, the contact block isn't shown, when a different profile is given
		 * But: When this profile was on the same server, then we could display the contacts
		 */
		DI::page()['aside'] .= self::getVCardHtml($profile, $block, $show_contacts);

		return $profile;
	}

	/**
	 * Formats a profile for display in the sidebar.
	 *
	 * It is very difficult to templatise the HTML completely
	 * because of all the conditional logic.
	 *
	 * @param array $profile       Profile array
	 * @param bool  $block         Block personal details
	 * @param bool  $show_contacts Show contact block
	 *
	 * @return string HTML sidebar module
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @note  Returns empty string if passed $profile is wrong type or not populated
	 *
	 * @hooks 'profile_sidebar_enter'
	 *      array $profile - profile data
	 * @hooks 'profile_sidebar'
	 *      array $arr
	 */
	public static function getVCardHtml(array $profile, bool $block, bool $show_contacts): string
	{
		$o        = '';
		$location = false;

		$profile_contact = [];

		if (DI::userSession()->getLocalUserId() && ($profile['uid'] ?? 0) != DI::userSession()->getLocalUserId()) {
			$profile_contact = Contact::getByURL($profile['nurl'], null, [], DI::userSession()->getLocalUserId());
		}
		if (!empty($profile['cid']) && DI::userSession()->getMyUrl()) {
			$profile_contact = Contact::selectFirst([], ['id' => $profile['cid']]);
		}

		$profile['picdate'] = urlencode($profile['picdate']);

		$profile['network_link'] = '';

		Hook::callAll('profile_sidebar_enter', $profile);

		$profile_url = $profile['url'];

		$contact = Contact::selectFirst(['id'], ['uri-id' => $profile['uri-id'], 'uid' => 0]);
		if (!$contact) {
			return $o;
		}

		$cid = $contact['id'];

		$follow_link      = null;
		$unfollow_link    = null;
		$wallmessage_link = null;

		// Who is the logged-in user to this profile?
		$visitor_contact = [];
		if (!empty($profile['uid']) && DI::userSession()->getMyUrl()) {
			$visitor_contact = Contact::selectFirst(['rel'], ['uid' => $profile['uid'], 'nurl' => Strings::normaliseLink(DI::userSession()->getMyUrl())]);
		}

		$local_user_is_self       = DI::userSession()->getMyUrl() && ($profile['url'] == DI::userSession()->getMyUrl());
		$visitor_is_authenticated = (bool)DI::userSession()->getMyUrl();
		$visitor_is_following     = in_array($visitor_contact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND])
			|| in_array($profile_contact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND]);
		$visitor_is_followed = in_array($visitor_contact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND])
			|| in_array($profile_contact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND]);
		$visitor_base_path = DI::userSession()->getMyUrl() ? preg_replace('=/profile/(.*)=ism', '', DI::userSession()->getMyUrl()) : '';

		if (!$local_user_is_self) {
			if (!$visitor_is_authenticated) {
				// Remote follow is only available for local profiles
				if (!empty($profile['nickname']) && strpos($profile_url, (string)DI::baseUrl()) === 0) {
					$follow_link = 'profile/' . $profile['nickname'] . '/remote_follow';
				}
			} else {
				if ($visitor_is_following) {
					$unfollow_link = $visitor_base_path . '/contact/unfollow?url=' . urlencode($profile_url) . '&auto=1';
				} else {
					$follow_link = $visitor_base_path . '/contact/follow?binurl=' . bin2hex($profile_url) . '&auto=1';
				}
			}

			if (Contact::canReceivePrivateMessages($profile_contact)) {
				if ($visitor_is_followed || $visitor_is_following) {
					$wallmessage_link = $visitor_base_path . '/message/new/' . $profile_contact['id'];
				}
			}
		}

		// show edit profile to yourself, but only if this is not meant to be
		// rendered as a "contact". i.e., if 'self' (a "contact" table column) isn't
		// set in $profile.
		if (!isset($profile['self']) && $local_user_is_self) {
			$profile['edit'] = [DI::baseUrl() . '/settings/profile', DI::l10n()->t('Edit profile'), '', DI::l10n()->t('Edit profile')];
			$profile['menu'] = [
				'chg_photo' => DI::l10n()->t('Change profile photo'),
				'cr_new'    => null,
				'entries'   => [],
			];
		}

		// Fetch the account type
		$account_type = Contact::getAccountType($profile['account-type']);

		if (!empty($profile['address']) || !empty($profile['location'])) {
			$location = DI::l10n()->t('Location:');
		}

		$homepage = !empty($profile['homepage']) ? DI::l10n()->t('Homepage:') : false;
		$about    = !empty($profile['about'])    ? DI::l10n()->t('About:')    : false;
		$xmpp     = !empty($profile['xmpp'])     ? DI::l10n()->t('XMPP:')     : false;
		$matrix   = !empty($profile['matrix'])   ? DI::l10n()->t('Matrix:')   : false;

		if ((!empty($profile['hidewall']) || $block) && !DI::userSession()->isAuthenticated()) {
			$location = $homepage = $about = false;
		}

		$split_name = Diaspora::splitName($profile['name']);
		$firstname  = $split_name['first'];
		$lastname   = $split_name['last'];

		if (!empty($profile['guid'])) {
			$diaspora = [
				'guid'       => $profile['guid'],
				'podloc'     => DI::baseUrl(),
				'searchable' => ($profile['net-publish'] ? 'true' : 'false'),
				'nickname'   => $profile['nickname'],
				'fullname'   => $profile['name'],
				'firstname'  => $firstname,
				'lastname'   => $lastname,
				'photo300'   => $profile['photo'] ?? '',
				'photo100'   => $profile['thumb'] ?? '',
				'photo50'    => $profile['micro'] ?? '',
			];
		} else {
			$diaspora = false;
		}

		$contact_block = '';
		$updated       = '';
		$contact_count = 0;

		if (!empty($profile['last-item'])) {
			$updated = date('c', strtotime($profile['last-item']));
		}

		if (!$block && $show_contacts) {
			$contact_block = ContactBlock::getHTML($profile, DI::userSession()->getLocalUserId());

			if (is_array($profile) && !$profile['hide-friends']) {
				$contact_count = DBA::count('contact', [
					'uid'     => $profile['uid'],
					'self'    => false,
					'blocked' => false,
					'pending' => false,
					'hidden'  => false,
					'archive' => false,
					'failed'  => false,
					'network' => Protocol::FEDERATED,
				]);
			}
		}

		// Expected profile/vcard.tpl profile.* template variables
		$p = [
			'address' => null,
			'edit'    => null,
			'upubkey' => null,
		];
		foreach ($profile as $k => $v) {
			$k     = str_replace('-', '_', $k);
			$p[$k] = $v;
		}

		if (isset($p['about'])) {
			$p['about'] = BBCode::convertForUriId($profile['uri-id'] ?? 0, $p['about']);
		}

		if (isset($p['address'])) {
			$p['address'] = BBCode::convertForUriId($profile['uri-id'] ?? 0, $p['address']);
		}

		$p['photo'] = Contact::getAvatarUrlForId($cid, Proxy::SIZE_SMALL);

		$p['url'] = Contact::magicLinkById($cid, $profile['url']);

		if (!isset($profile['hidewall'])) {
			DI::logger()->warning('Missing hidewall key in profile array', ['profile' => $profile]);
		}

		if ($profile['account-type'] == Contact::TYPE_COMMUNITY) {
			$mention_label = DI::l10n()->t('Post to group');
			$mention_url   = 'compose/0?body=!' . $profile['addr'];
			$network_label = DI::l10n()->t('View group');
		} else {
			$mention_label = DI::l10n()->t('Mention');
			$mention_url   = 'compose/0?body=@' . $profile['addr'];
			$network_label = DI::l10n()->t('Network Posts');
		}
		$network_url = 'contact/' . $cid . '/conversations';

		$tpl = Renderer::getMarkupTemplate('profile/vcard.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$profile'             => $p,
			'$xmpp'                => $xmpp,
			'$matrix'              => $matrix,
			'$follow'              => DI::l10n()->t('Follow'),
			'$follow_link'         => $follow_link,
			'$unfollow'            => DI::l10n()->t('Unfollow'),
			'$unfollow_link'       => $unfollow_link,
			'$subscribe_feed'      => DI::l10n()->t('Atom feed'),
			'$subscribe_feed_link' => $profile['hidewall'] ?? 0 ? '' : $profile['poll'],
			'$wallmessage'         => DI::l10n()->t('Message'),
			'$wallmessage_link'    => $wallmessage_link,
			'$account_type'        => $account_type,
			'$location'            => $location,
			'$homepage'            => $homepage,
			'$homepage_verified'   => DI::l10n()->t('This website has been verified to belong to the same person.'),
			'$about'               => $about,
			'$network'             => DI::l10n()->t('Network:'),
			'$contacts'            => $contact_count,
			'$updated'             => $updated,
			'$diaspora'            => $diaspora,
			'$contact_block'       => $contact_block,
			'$mention_label'       => $mention_label,
			'$mention_url'         => $mention_url,
			'$network_label'       => $network_label,
			'$network_url'         => $network_url,
		]);

		$arr = ['profile' => &$profile, 'entry' => &$o];

		Hook::callAll('profile_sidebar', $arr);

		return $o;
	}

	/**
	 * Check if the event list should be displayed
	 *
	 * @param integer $uid
	 * @param Mode $mode
	 * @return boolean
	 */
	public static function shouldDisplayEventList(int $uid, Mode $mode): bool
	{
		if (empty($uid) || $mode->isMobile()) {
			return false;
		}

		if (!DI::pConfig()->get($uid, 'system', 'display_eventlist', true)) {
			return false;
		}

		return !DI::config()->get('theme', 'hide_eventlist');
	}

	/**
	 * Returns the upcoming birthdays of contacts of the current user as HTML content
	 * @param int $uid  User Id
	 *
	 * @return string The upcoming birthdays (HTML)
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\ServiceUnavailableException
	 * @throws \ImagickException
	 */
	public static function getBirthdays(int $uid): string
	{
		$bd_short = DI::l10n()->t('F d');

		$cacheKey = 'get_birthdays:' . $uid;
		$events   = DI::cache()->get($cacheKey);
		if (is_null($events)) {
			$result = DBA::p(
				"SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event`
				INNER JOIN `contact`
					ON `contact`.`id` = `event`.`cid`
					AND (`contact`.`rel` = ? OR `contact`.`rel` = ?)
					AND NOT `contact`.`pending`
					AND NOT `contact`.`hidden`
					AND NOT `contact`.`blocked`
					AND NOT `contact`.`archive`
					AND NOT `contact`.`deleted`
				WHERE `event`.`uid` = ? AND `type` = 'birthday' AND `start` < ? AND `finish` > ?
				ORDER BY `start`",
				Contact::SHARING,
				Contact::FRIEND,
				$uid,
				DateTimeFormat::utc('now + 6 days'),
				DateTimeFormat::utcNow()
			);
			if (DBA::isResult($result)) {
				$events = DBA::toArray($result);
				DI::cache()->set($cacheKey, $events, Duration::HOUR);
			}
		}

		$total      = 0;
		$classToday = '';
		$tpl_events = [];
		if (DBA::isResult($events)) {
			$now  = strtotime('now');
			$cids = [];

			$isToday = false;
			foreach ($events as $event) {
				if (strlen($event['name'])) {
					$total++;
				}
				if ((strtotime($event['start'] . ' +00:00') < $now) && (strtotime($event['finish'] . ' +00:00') > $now)) {
					$isToday = true;
				}
			}
			$classToday = $isToday ? ' birthday-today ' : '';
			if ($total) {
				foreach ($events as $event) {
					if (!strlen($event['name'])) {
						continue;
					}

					// avoid duplicates
					if (in_array($event['cid'], $cids)) {
						continue;
					}
					$cids[] = $event['cid'];

					$today = (strtotime($event['start'] . ' +00:00') < $now) && (strtotime($event['finish'] . ' +00:00') > $now);

					$tpl_events[] = [
						'id'    => $event['id'],
						'link'  => Contact::magicLinkById($event['cid']),
						'title' => $event['name'],
						'date'  => DI::l10n()->getDay(DateTimeFormat::local($event['start'], $bd_short)) . (($today) ? ' ' . DI::l10n()->t('[today]') : '')
					];
				}
			}
		}
		$tpl = Renderer::getMarkupTemplate('birthdays_reminder.tpl');
		return Renderer::replaceMacros($tpl, [
			'$classtoday'      => $classToday,
			'$count'           => $total,
			'$event_reminders' => DI::l10n()->t('Birthday Reminders'),
			'$event_title'     => DI::l10n()->t('Birthdays this week:'),
			'$events'          => $tpl_events,
			'$lbr'             => '{', // raw brackets mess up if/endif macro processing
			'$rbr'             => '}'
		]);
	}

	/**
	 * Renders HTML for event reminder (e.g. contact birthdays
	 * @param int $uid  User Id
	 * @param int $pcid Public Contact Id
	 *
	 * @return string Rendered HTML
	 */
	public static function getEventsReminderHTML(int $uid, int $pcid): string
	{
		$bd_format  = DI::l10n()->t('g A l F d'); // 8 AM Friday January 18
		$classtoday = '';

		$condition = [
			"`uid` = ? AND `type` != 'birthday' AND `start` < ? AND `start` >= ?",
			$uid, DateTimeFormat::utc('now + 7 days'), DateTimeFormat::utc('now - 1 days')
		];
		$s = DBA::select('event', [], $condition, ['order' => ['start']]);

		$r = [];

		if (DBA::isResult($s)) {
			$istoday = false;
			$total   = 0;

			while ($rr = DBA::fetch($s)) {
				$condition = [
					'parent-uri' => $rr['uri'], 'uid' => $rr['uid'], 'author-id' => $pcid,
					'vid'        => [Verb::getID(Activity::ATTEND), Verb::getID(Activity::ATTENDMAYBE)],
					'visible'    => true, 'deleted' => false
				];
				if (!Post::exists($condition)) {
					continue;
				}

				if (strlen($rr['summary'])) {
					$total++;
				}

				$strt = DateTimeFormat::local($rr['start'], 'Y-m-d');
				if ($strt === DateTimeFormat::localNow('Y-m-d')) {
					$istoday = true;
				}

				$title = BBCode::toPlaintext($rr['summary'], false);

				if (strlen($title) > 35) {
					$title = substr($title, 0, 32) . '... ';
				}

				$description = BBCode::toPlaintext($rr['desc'], false) . '... ';
				if (!$description) {
					$description = DI::l10n()->t('[No description]');
				}

				$strt = DateTimeFormat::local($rr['start']);

				if (substr($strt, 0, 10) < DateTimeFormat::localNow('Y-m-d')) {
					continue;
				}

				$today = substr($strt, 0, 10) === DateTimeFormat::localNow('Y-m-d');

				$rr['title']       = $title;
				$rr['description'] = $description;
				$rr['date']        = DI::l10n()->getDay(DateTimeFormat::local($rr['start'], $bd_format)) . (($today) ? ' ' . DI::l10n()->t('[today]') : '');
				$rr['startime']    = $strt;
				$rr['today']       = $today;

				$r[] = $rr;
			}
			DBA::close($s);
			$classtoday = (($istoday) ? 'event-today' : '');
		}
		$tpl = Renderer::getMarkupTemplate('events_reminder.tpl');
		return Renderer::replaceMacros($tpl, [
			'$classtoday'      => $classtoday,
			'$count'           => count($r),
			'$event_reminders' => DI::l10n()->t('Event Reminders'),
			'$event_title'     => DI::l10n()->t('Upcoming events the next 7 days:'),
			'$events'          => $r,
		]);
	}

	/**
	 * Get the user ID of the page owner.
	 *
	 * Used from within PCSS themes to set theme parameters. If there's a
	 * profile_uid variable set in App, that is the "page owner" and normally their theme
	 * settings take precedence; unless a local user is logged in which means they don't
	 * want to see anybody else's theme settings except their own while on this site.
	 *
	 * @return int user ID
	 *
	 * @note Returns local_user instead of user ID if "always_my_theme" is set to true
	 */
	public static function getThemeUid(AppHelper $appHelper): int
	{
		return DI::userSession()->getLocalUserId() ?: $appHelper->getProfileOwner();
	}

	/**
	 * search for Profiles
	 *
	 * @param int  $start Starting record (see LIMIT start,count)
	 * @param int  $count Maximum records (see LIMIT start,count)
	 * @param string $search Optional search word (see LIKE %s?%s)
	 *
	 * @return array [ 'total' => 123, 'entries' => [...] ];
	 *
	 * @throws \Exception
	 */
	public static function searchProfiles(int $start = 0, int $count = 100, string $search = null): array
	{
		if (!empty($search)) {
			$publish    = (DI::config()->get('system', 'publish_all') ? '' : "AND `publish` ");
			$searchTerm = '%' . $search . '%';
			$condition  = [
				"`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`
				$publish
				AND ((`name` LIKE ?) OR
				(`nickname` LIKE ?) OR
				(`about` LIKE ?) OR
				(`locality` LIKE ?) OR
				(`region` LIKE ?) OR
				(`country-name` LIKE ?) OR
				(`pub_keywords` LIKE ?) OR
				(`prv_keywords` LIKE ?))",
				$searchTerm, $searchTerm, $searchTerm, $searchTerm,
				$searchTerm, $searchTerm, $searchTerm, $searchTerm
			];
		} else {
			$condition = ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false];
			if (!DI::config()->get('system', 'publish_all')) {
				$condition['publish'] = true;
			}
		}

		$total = DBA::count('owner-view', $condition);

		// If nothing found, don't try to select details
		if ($total > 0) {
			$profiles = DBA::selectToArray('owner-view', [], $condition, ['order' => ['name'], 'limit' => [$start, $count]]);
		} else {
			$profiles = [];
		}

		return ['total' => $total, 'entries' => $profiles];
	}

	/**
	 * Migrates a legacy profile to the new slimmer profile with extra custom fields.
	 * Multi profiles are converted to ACl-protected custom fields and deleted.
	 *
	 * @param array $profile One profile array
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function migrate(array $profile)
	{
		// Already processed, aborting
		if ($profile['is-default'] === null) {
			return;
		}

		$contacts = [];

		if (!$profile['is-default']) {
			$contacts = Contact::selectToArray(['id'], [
				'uid'        => $profile['uid'],
				'profile-id' => $profile['id']
			]);
			if (!count($contacts)) {
				// No contact visibility selected defaults to user-only permission
				$contacts = Contact::selectToArray(['id'], ['uid' => $profile['uid'], 'self' => true]);
			}
		}

		$permissionSet = DI::permissionSet()->selectOrCreate(
			new PermissionSet(
				$profile['uid'],
				array_column($contacts, 'id') ?? []
			)
		);

		$order = 1;

		$custom_fields = [
			'hometown'  => DI::l10n()->t('Hometown:'),
			'marital'   => DI::l10n()->t('Marital Status:'),
			'with'      => DI::l10n()->t('With:'),
			'howlong'   => DI::l10n()->t('Since:'),
			'sexual'    => DI::l10n()->t('Sexual Preference:'),
			'politic'   => DI::l10n()->t('Political Views:'),
			'religion'  => DI::l10n()->t('Religious Views:'),
			'likes'     => DI::l10n()->t('Likes:'),
			'dislikes'  => DI::l10n()->t('Dislikes:'),
			'pdesc'     => DI::l10n()->t('Title/Description:'),
			'summary'   => DI::l10n()->t('Summary'),
			'music'     => DI::l10n()->t('Musical interests'),
			'book'      => DI::l10n()->t('Books, literature'),
			'tv'        => DI::l10n()->t('Television'),
			'film'      => DI::l10n()->t('Film/dance/culture/entertainment'),
			'interest'  => DI::l10n()->t('Hobbies/Interests'),
			'romance'   => DI::l10n()->t('Love/romance'),
			'work'      => DI::l10n()->t('Work/employment'),
			'education' => DI::l10n()->t('School/education'),
			'contact'   => DI::l10n()->t('Contact information and Social Networks'),
		];

		foreach ($custom_fields as $field => $label) {
			if (!empty($profile[$field]) && $profile[$field] > DBA::NULL_DATE && $profile[$field] > DBA::NULL_DATETIME) {
				DI::profileField()->save(DI::profileFieldFactory()->createFromValues(
					$profile['uid'],
					$order,
					trim($label, ':'),
					$profile[$field],
					$permissionSet
				));
			}

			$profile[$field] = null;
		}

		if ($profile['is-default']) {
			$profile['profile-name'] = null;
			$profile['is-default']   = null;
			DBA::update('profile', $profile, ['id' => $profile['id']]);
		} elseif (!empty($profile['id'])) {
			DBA::delete('profile', ['id' => $profile['id']]);
		}
	}

	/**
	 * Get "about" field with the added responsible relay contact if appropriate.
	 *
	 * @param string $about
	 * @param integer|null $parent_uid
	 * @param integer $account_type
	 * @param string $language
	 * @return string
	 */
	public static function addResponsibleRelayContact(string $about = null, int $parent_uid = null, int $account_type, string $language): ?string
	{
		if (($account_type != User::ACCOUNT_TYPE_RELAY) || empty($parent_uid)) {
			return $about;
		}

		$parent = User::getOwnerDataById($parent_uid);
		if (strpos($about, $parent['addr']) || strpos($about, $parent['url'])) {
			return $about;
		}

		$l10n = DI::l10n()->withLang($language);

		return $about .= "\n" . $l10n->t('Responsible account: %s', $parent['addr']);
	}

	/**
	 * Set "about" field with the added responsible relay contact if appropriate.
	 *
	 * @param integer $uid
	 * @return void
	 */
	public static function setResponsibleRelayContact(int $uid)
	{
		$owner = User::getOwnerDataById($uid);
		$about = self::addResponsibleRelayContact($owner['about'], $owner['parent-uid'], $owner['account-type'], $owner['language']);
		if ($about != $owner['about']) {
			self::update(['about' => $about], $uid);
		}
	}
}

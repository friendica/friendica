<?php
/**
 * @file src/Model/Profile.php
 */
namespace Friendica\Model;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Temporal;

require_once 'include/dba.php';
require_once 'mod/proxy.php';

class Profile
{
	/**
	 * @brief Returns a formatted location string from the given profile array
	 *
	 * @param array $profile Profile array (Generated from the "profile" table)
	 *
	 * @return string Location string
	 */
	public static function formatLocation(array $profile)
	{
		$location = '';

		if (!empty($profile['locality'])) {
			$location .= $profile['locality'];
		}

		if (!empty($profile['region']) && (defaults($profile, 'locality', '') != $profile['region'])) {
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
	 *
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
	 * @brief Loads a profile into the page sidebar.
	 * @param object  $a            App
	 * @param string  $nickname     string
	 * @param int     $profile      int
	 * @param array   $profiledata  array
	 * @param boolean $show_connect Show connect link
	 */
	public static function load(App $a, $nickname, $profile = 0, array $profiledata = [], $show_connect = true)
	{
		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nickname, 'account_removed' => false]);

		if (!DBA::isResult($user) && empty($profiledata)) {
			logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
			notice(L10n::t('Requested account is not available.') . EOL);
			$a->error = 404;
			return;
		}

		if (count($profiledata) > 0) {
			// Add profile data to sidebar
			$a->page['aside'] .= self::sidebar($profiledata, true, $show_connect);

			if (!DBA::isResult($user)) {
				return;
			}
		}

		$pdata = self::getByNickname($nickname, $user['uid'], $profile);

		if (empty($pdata) && empty($profiledata)) {
			logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
			notice(L10n::t('Requested profile is not available.') . EOL);
			$a->error = 404;
			return;
		}

		// fetch user tags if this isn't the default profile

		if (!$pdata['is-default']) {
			$x = q(
				"SELECT `pub_keywords` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
				intval($pdata['profile_uid'])
			);
			if ($x && count($x)) {
				$pdata['pub_keywords'] = $x[0]['pub_keywords'];
			}
		}

		$a->profile = $pdata;
		$a->profile_uid = $pdata['profile_uid'];

		$a->profile['mobile-theme'] = PConfig::get($a->profile['profile_uid'], 'system', 'mobile_theme');
		$a->profile['network'] = NETWORK_DFRN;

		$a->page['title'] = $a->profile['name'] . ' @ ' . Config::get('config', 'sitename');

		if (!$profiledata && !PConfig::get(local_user(), 'system', 'always_my_theme')) {
			$_SESSION['theme'] = $a->profile['theme'];
		}

		$_SESSION['mobile-theme'] = $a->profile['mobile-theme'];

		/*
		* load/reload current theme info
		*/

		$a->set_template_engine(); // reset the template engine to the default in case the user's theme doesn't specify one

		$theme_info_file = 'view/theme/' . $a->getCurrentTheme() . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		if (local_user() && local_user() == $a->profile['uid'] && $profiledata) {
			$a->page['aside'] .= replace_macros(
				get_markup_template('profile_edlink.tpl'),
				[
					'$editprofile' => L10n::t('Edit profile'),
					'$profid' => $a->profile['id']
				]
			);
		}

		$block = ((Config::get('system', 'block_public') && !local_user() && !remote_user()) ? true : false);

		/**
		 * @todo
		 * By now, the contact block isn't shown, when a different profile is given
		 * But: When this profile was on the same server, then we could display the contacts
		 */
		if (!$profiledata) {
			$a->page['aside'] .= self::sidebar($a->profile, $block, $show_connect);
		}

		return;
	}

	/**
	 * Get all profile data of a local user
	 *
	 * If the viewer is an authenticated remote viewer, the profile displayed is the
	 * one that has been configured for his/her viewing in the Contact manager.
	 * Passing a non-zero profile ID can also allow a preview of a selected profile
	 * by the owner
	 *
	 * Includes all available profile data
	 *
	 * @brief Get all profile data of a local user
	 * @param string $nickname nick
	 * @param int    $uid      uid
	 * @param int    $profile_id  ID of the profile
	 * @return array
	 */
	public static function getByNickname($nickname, $uid = 0, $profile_id = 0)
	{
		if (remote_user() && count($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $visitor) {
				if ($visitor['uid'] == $uid) {
					$contact = DBA::selectFirst('contact', ['profile-id'], ['id' => $visitor['cid']]);
					if (DBA::isResult($contact)) {
						$profile_id = $contact['profile-id'];
					}
					break;
				}
			}
		}

		$profile = null;

		if ($profile_id) {
			$profile = DBA::fetchFirst(
				"SELECT `contact`.`id` AS `contact_id`, `contact`.`photo` AS `contact_photo`,
					`contact`.`thumb` AS `contact_thumb`, `contact`.`micro` AS `contact_micro`,
					`profile`.`uid` AS `profile_uid`, `profile`.*,
					`contact`.`avatar-date` AS picdate, `contact`.`addr`, `contact`.`url`, `user`.*
				FROM `profile`
				INNER JOIN `contact` on `contact`.`uid` = `profile`.`uid` AND `contact`.`self`
				INNER JOIN `user` ON `profile`.`uid` = `user`.`uid`
				WHERE `user`.`nickname` = ? AND `profile`.`id` = ? LIMIT 1",
				$nickname,
				intval($profile_id)
			);
		}
		if (!DBA::isResult($profile)) {
			$profile = DBA::fetchFirst(
				"SELECT `contact`.`id` AS `contact_id`, `contact`.`photo` as `contact_photo`,
					`contact`.`thumb` AS `contact_thumb`, `contact`.`micro` AS `contact_micro`,
					`profile`.`uid` AS `profile_uid`, `profile`.*,
					`contact`.`avatar-date` AS picdate, `contact`.`addr`, `contact`.`url`, `user`.*
				FROM `profile`
				INNER JOIN `contact` ON `contact`.`uid` = `profile`.`uid` AND `contact`.`self`
				INNER JOIN `user` ON `profile`.`uid` = `user`.`uid`
				WHERE `user`.`nickname` = ? AND `profile`.`is-default` LIMIT 1",
				$nickname
			);
		}

		return $profile;
	}

	/**
	 * Formats a profile for display in the sidebar.
	 *
	 * It is very difficult to templatise the HTML completely
	 * because of all the conditional logic.
	 *
	 * @brief Formats a profile for display in the sidebar.
	 * @param array $profile
	 * @param int $block
	 * @param boolean $show_connect Show connect link
	 *
	 * @return string HTML sidebar module
	 *
	 * @note Returns empty string if passed $profile is wrong type or not populated
	 *
	 * @hooks 'profile_sidebar_enter'
	 *      array $profile - profile data
	 * @hooks 'profile_sidebar'
	 *      array $arr
	 */
	private static function sidebar($profile, $block = 0, $show_connect = true)
	{
		$a = get_app();

		$o = '';
		$location = false;

		// This function can also use contact information in $profile
		$is_contact = x($profile, 'cid');

		if (!is_array($profile) && !count($profile)) {
			return $o;
		}

		$profile['picdate'] = urlencode(defaults($profile, 'picdate', ''));

		if (($profile['network'] != '') && ($profile['network'] != NETWORK_DFRN)) {
			$profile['network_name'] = format_network_name($profile['network'], $profile['url']);
		} else {
			$profile['network_name'] = '';
		}

		Addon::callHooks('profile_sidebar_enter', $profile);


		// don't show connect link to yourself
		$connect = $profile['uid'] != local_user() ? L10n::t('Connect') : false;

		// don't show connect link to authenticated visitors either
		if (remote_user() && count($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $visitor) {
				if ($visitor['uid'] == $profile['uid']) {
					$connect = false;
					break;
				}
			}
		}

		if (!$show_connect) {
			$connect = false;
		}

		$profile_url = '';

		// Is the local user already connected to that user?
		if ($connect && local_user()) {
			if (isset($profile['url'])) {
				$profile_url = normalise_link($profile['url']);
			} else {
				$profile_url = normalise_link(System::baseUrl() . '/profile/' . $profile['nickname']);
			}

			if (DBA::exists('contact', ['pending' => false, 'uid' => local_user(), 'nurl' => $profile_url])) {
				$connect = false;
			}
		}

		if ($connect && ($profile['network'] != NETWORK_DFRN) && !isset($profile['remoteconnect'])) {
			$connect = false;
		}

		$remoteconnect = null;
		if (isset($profile['remoteconnect'])) {
			$remoteconnect = $profile['remoteconnect'];
		}

		if ($connect && ($profile['network'] == NETWORK_DFRN) && !isset($remoteconnect)) {
			$subscribe_feed = L10n::t('Atom feed');
		} else {
			$subscribe_feed = false;
		}

		if (remote_user() || (self::getMyURL() && x($profile, 'unkmail') && ($profile['uid'] != local_user()))) {
			$wallmessage = L10n::t('Message');
			$wallmessage_link = 'wallmessage/' . $profile['nickname'];

			if (remote_user()) {
				$r = q(
					"SELECT `url` FROM `contact` WHERE `uid` = %d AND `id` = '%s' AND `rel` = %d",
					intval($profile['uid']),
					intval(remote_user()),
					intval(Contact::CONTACT_IS_FRIEND)
				);
			} else {
				$r = q(
					"SELECT `url` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `rel` = %d",
					intval($profile['uid']),
					DBA::escape(normalise_link(self::getMyURL())),
					intval(Contact::CONTACT_IS_FRIEND)
				);
			}
			if ($r) {
				$remote_url = $r[0]['url'];
				$message_path = preg_replace('=(.*)/profile/(.*)=ism', '$1/message/new/', $remote_url);
				$wallmessage_link = $message_path . base64_encode($profile['addr']);
			}
		} else {
			$wallmessage = false;
			$wallmessage_link = false;
		}

		// show edit profile to yourself
		if (!$is_contact && $profile['uid'] == local_user() && Feature::isEnabled(local_user(), 'multi_profiles')) {
			$profile['edit'] = [System::baseUrl() . '/profiles', L10n::t('Profiles'), '', L10n::t('Manage/edit profiles')];
			$r = q(
				"SELECT * FROM `profile` WHERE `uid` = %d",
				local_user()
			);

			$profile['menu'] = [
				'chg_photo' => L10n::t('Change profile photo'),
				'cr_new' => L10n::t('Create New Profile'),
				'entries' => [],
			];

			if (DBA::isResult($r)) {
				foreach ($r as $rr) {
					$profile['menu']['entries'][] = [
						'photo' => $rr['thumb'],
						'id' => $rr['id'],
						'alt' => L10n::t('Profile Image'),
						'profile_name' => $rr['profile-name'],
						'isdefault' => $rr['is-default'],
						'visibile_to_everybody' => L10n::t('visible to everybody'),
						'edit_visibility' => L10n::t('Edit visibility'),
					];
				}
			}
		}
		if (!$is_contact && $profile['uid'] == local_user() && !Feature::isEnabled(local_user(), 'multi_profiles')) {
			$profile['edit'] = [System::baseUrl() . '/profiles/' . $profile['id'], L10n::t('Edit profile'), '', L10n::t('Edit profile')];
			$profile['menu'] = [
				'chg_photo' => L10n::t('Change profile photo'),
				'cr_new' => null,
				'entries' => [],
			];
		}

		// Fetch the account type
		$account_type = Contact::getAccountType($profile);

		if (x($profile, 'address')
			|| x($profile, 'location')
			|| x($profile, 'locality')
			|| x($profile, 'region')
			|| x($profile, 'postal-code')
			|| x($profile, 'country-name')
		) {
			$location = L10n::t('Location:');
		}

		$gender   = x($profile, 'gender')   ? L10n::t('Gender:')   : false;
		$marital  = x($profile, 'marital')  ? L10n::t('Status:')   : false;
		$homepage = x($profile, 'homepage') ? L10n::t('Homepage:') : false;
		$about    = x($profile, 'about')    ? L10n::t('About:')    : false;
		$xmpp     = x($profile, 'xmpp')     ? L10n::t('XMPP:')     : false;

		if ((x($profile, 'hidewall') || $block) && !local_user() && !remote_user()) {
			$location = $gender = $marital = $homepage = $about = false;
		}

		$split_name = Diaspora::splitName($profile['name']);
		$firstname = $split_name['first'];
		$lastname = $split_name['last'];

		if (x($profile, 'guid')) {
			$diaspora = [
				'guid' => $profile['guid'],
				'podloc' => System::baseUrl(),
				'searchable' => (($profile['publish'] && $profile['net-publish']) ? 'true' : 'false' ),
				'nickname' => $profile['nickname'],
				'fullname' => $profile['name'],
				'firstname' => $firstname,
				'lastname' => $lastname,
				'photo300' => defaults($profile, 'contact_photo', ''),
				'photo100' => defaults($profile, 'contact_thumb', ''),
				'photo50' => defaults($profile, 'contact_micro', ''),
			];
		} else {
			$diaspora = false;
		}

		$contact_block = '';
		$updated = '';
		$contacts = 0;
		if (!$block) {
			$contact_block = contact_block();

			if (is_array($a->profile) && !$a->profile['hide-friends']) {
				$r = q(
					"SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
					intval($a->profile['uid'])
				);
				if (DBA::isResult($r)) {
					$updated = date('c', strtotime($r[0]['updated']));
				}

				$r = q(
					"SELECT COUNT(*) AS `total` FROM `contact`
					WHERE `uid` = %d
						AND NOT `self` AND NOT `blocked` AND NOT `pending`
						AND NOT `hidden` AND NOT `archive`
						AND `network` IN ('%s', '%s', '%s', '')",
					intval($profile['uid']),
					DBA::escape(NETWORK_DFRN),
					DBA::escape(NETWORK_DIASPORA),
					DBA::escape(NETWORK_OSTATUS)
				);
				if (DBA::isResult($r)) {
					$contacts = intval($r[0]['total']);
				}
			}
		}

		$p = [];
		foreach ($profile as $k => $v) {
			$k = str_replace('-', '_', $k);
			$p[$k] = $v;
		}

		if (isset($p['about'])) {
			$p['about'] = BBCode::convert($p['about']);
		}

		if (isset($p['address'])) {
			$p['address'] = BBCode::convert($p['address']);
		} else {
			$p['address'] = BBCode::convert($p['location']);
		}

		if (isset($p['photo'])) {
			$p['photo'] = proxy_url($p['photo'], false, PROXY_SIZE_SMALL);
		}

		$p['url'] = Contact::magicLink(defaults($p, 'url', $profile_url));

		$tpl = get_markup_template('profile_vcard.tpl');
		$o .= replace_macros($tpl, [
			'$profile' => $p,
			'$xmpp' => $xmpp,
			'$connect' => $connect,
			'$remoteconnect' => $remoteconnect,
			'$subscribe_feed' => $subscribe_feed,
			'$wallmessage' => $wallmessage,
			'$wallmessage_link' => $wallmessage_link,
			'$account_type' => $account_type,
			'$location' => $location,
			'$gender' => $gender,
			'$marital' => $marital,
			'$homepage' => $homepage,
			'$about' => $about,
			'$network' => L10n::t('Network:'),
			'$contacts' => $contacts,
			'$updated' => $updated,
			'$diaspora' => $diaspora,
			'$contact_block' => $contact_block,
		]);

		$arr = ['profile' => &$profile, 'entry' => &$o];

		Addon::callHooks('profile_sidebar', $arr);

		return $o;
	}

	public static function getBirthdays()
	{
		$a = get_app();
		$o = '';

		if (!local_user() || $a->is_mobile || $a->is_tablet) {
			return $o;
		}

		/*
		* $mobile_detect = new Mobile_Detect();
		* $is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		* 		if ($is_mobile)
		* 			return $o;
		*/

		$bd_format = L10n::t('g A l F d'); // 8 AM Friday January 18
		$bd_short = L10n::t('F d');

		$cachekey = 'get_birthdays:' . local_user();
		$r = Cache::get($cachekey);
		if (is_null($r)) {
			$s = DBA::p(
				"SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event`
				INNER JOIN `contact` ON `contact`.`id` = `event`.`cid`
				WHERE `event`.`uid` = ? AND `type` = 'birthday' AND `start` < ? AND `finish` > ?
				ORDER BY `start` ASC ",
				local_user(),
				DateTimeFormat::utc('now + 6 days'),
				DateTimeFormat::utcNow()
			);
			if (DBA::isResult($s)) {
				$r = DBA::toArray($s);
				Cache::set($cachekey, $r, CACHE_HOUR);
			}
		}

		$total = 0;
		$classtoday = '';
		if (DBA::isResult($r)) {
			$now = strtotime('now');
			$cids = [];

			$istoday = false;
			foreach ($r as $rr) {
				if (strlen($rr['name'])) {
					$total ++;
				}
				if ((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now)) {
					$istoday = true;
				}
			}
			$classtoday = $istoday ? ' birthday-today ' : '';
			if ($total) {
				foreach ($r as &$rr) {
					if (!strlen($rr['name'])) {
						continue;
					}

					// avoid duplicates

					if (in_array($rr['cid'], $cids)) {
						continue;
					}
					$cids[] = $rr['cid'];

					$today = (((strtotime($rr['start'] . ' +00:00') < $now) && (strtotime($rr['finish'] . ' +00:00') > $now)) ? true : false);

					$rr['link'] = Contact::magicLink($rr['url']);
					$rr['title'] = $rr['name'];
					$rr['date'] = day_translate(DateTimeFormat::convert($rr['start'], $a->timezone, 'UTC', $rr['adjust'] ? $bd_format : $bd_short)) . (($today) ? ' ' . L10n::t('[today]') : '');
					$rr['startime'] = null;
					$rr['today'] = $today;
				}
			}
		}
		$tpl = get_markup_template('birthdays_reminder.tpl');
		return replace_macros($tpl, [
			'$baseurl' => System::baseUrl(),
			'$classtoday' => $classtoday,
			'$count' => $total,
			'$event_reminders' => L10n::t('Birthday Reminders'),
			'$event_title' => L10n::t('Birthdays this week:'),
			'$events' => $r,
			'$lbr' => '{', // raw brackets mess up if/endif macro processing
			'$rbr' => '}'
		]);
	}

	public static function getEventsReminderHTML()
	{
		$a = get_app();
		$o = '';

		if (!local_user() || $a->is_mobile || $a->is_tablet) {
			return $o;
		}

		/*
		* 	$mobile_detect = new Mobile_Detect();
		* 		$is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		* 		if ($is_mobile)
		* 			return $o;
		*/

		$bd_format = L10n::t('g A l F d'); // 8 AM Friday January 18
		$classtoday = '';

		$s = DBA::p(
			"SELECT `event`.*
			FROM `event`
			INNER JOIN `item`
				ON `item`.`uid` = `event`.`uid`
				AND `item`.`parent-uri` = `event`.`uri`
			WHERE `event`.`uid` = ?
			AND `event`.`type` != 'birthday'
			AND `event`.`start` < ?
			AND `event`.`start` >= ?
			AND `item`.`author-id` = ?
			AND (`item`.`verb` = ? OR `item`.`verb` = ?)
			AND `item`.`visible`
			AND NOT `item`.`deleted`
			ORDER BY  `event`.`start` ASC",
			local_user(),
			DateTimeFormat::utc('now + 7 days'),
			DateTimeFormat::utc('now - 1 days'),
			public_contact(),
			ACTIVITY_ATTEND,
			ACTIVITY_ATTENDMAYBE
		);

		$r = [];

		if (DBA::isResult($s)) {
			$istoday = false;

			while ($rr = DBA::fetch($s)) {
				if (strlen($rr['name'])) {
					$total ++;
				}

				$strt = DateTimeFormat::convert($rr['start'], $rr['convert'] ? $a->timezone : 'UTC', 'UTC', 'Y-m-d');
				if ($strt === DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) {
					$istoday = true;
				}

				$title = strip_tags(html_entity_decode(BBCode::convert($rr['summary']), ENT_QUOTES, 'UTF-8'));

				if (strlen($title) > 35) {
					$title = substr($title, 0, 32) . '... ';
				}

				$description = substr(strip_tags(BBCode::convert($rr['desc'])), 0, 32) . '... ';
				if (!$description) {
					$description = L10n::t('[No description]');
				}

				$strt = DateTimeFormat::convert($rr['start'], $rr['convert'] ? $a->timezone : 'UTC');

				if (substr($strt, 0, 10) < DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) {
					continue;
				}

				$today = ((substr($strt, 0, 10) === DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) ? true : false);

				$rr['title'] = $title;
				$rr['description'] = $description;
				$rr['date'] = day_translate(DateTimeFormat::convert($rr['start'], $rr['adjust'] ? $a->timezone : 'UTC', 'UTC', $bd_format)) . (($today) ? ' ' . L10n::t('[today]') : '');
				$rr['startime'] = $strt;
				$rr['today'] = $today;

				$r[] = $rr;
			}
			DBA::close($s);
			$classtoday = (($istoday) ? 'event-today' : '');
		}
		$tpl = get_markup_template('events_reminder.tpl');
		return replace_macros($tpl, [
			'$baseurl' => System::baseUrl(),
			'$classtoday' => $classtoday,
			'$count' => count($r),
			'$event_reminders' => L10n::t('Event Reminders'),
			'$event_title' => L10n::t('Upcoming events the next 7 days:'),
			'$events' => $r,
		]);
	}

	public static function getAdvanced(App $a)
	{
		$o = '';
		$uid = $a->profile['uid'];

		$o .= replace_macros(
			get_markup_template('section_title.tpl'),
			['$title' => L10n::t('Profile')]
		);

		if ($a->profile['name']) {
			$tpl = get_markup_template('profile_advanced.tpl');

			$profile = [];

			$profile['fullname'] = [L10n::t('Full Name:'), $a->profile['name']];

			if (Feature::isEnabled($uid, 'profile_membersince')) {
				$profile['membersince'] = [L10n::t('Member since:'), DateTimeFormat::local($a->profile['register_date'])];
			}

			if ($a->profile['gender']) {
				$profile['gender'] = [L10n::t('Gender:'), $a->profile['gender']];
			}

			if (($a->profile['dob']) && ($a->profile['dob'] > '0001-01-01')) {
				$year_bd_format = L10n::t('j F, Y');
				$short_bd_format = L10n::t('j F');

				$val = day_translate(
					intval($a->profile['dob']) ?
						DateTimeFormat::utc($a->profile['dob'] . ' 00:00 +00:00', $year_bd_format)
						: DateTimeFormat::utc('2001-' . substr($a->profile['dob'], 5) . ' 00:00 +00:00', $short_bd_format)
				);

				$profile['birthday'] = [L10n::t('Birthday:'), $val];
			}

			if (!empty($a->profile['dob'])
				&& $a->profile['dob'] > '0001-01-01'
				&& $age = Temporal::getAgeByTimezone($a->profile['dob'], $a->profile['timezone'], '')
			) {
				$profile['age'] = [L10n::t('Age:'), $age];
			}

			if ($a->profile['marital']) {
				$profile['marital'] = [L10n::t('Status:'), $a->profile['marital']];
			}

			/// @TODO Maybe use x() here, plus below?
			if ($a->profile['with']) {
				$profile['marital']['with'] = $a->profile['with'];
			}

			if (strlen($a->profile['howlong']) && $a->profile['howlong'] >= NULL_DATE) {
				$profile['howlong'] = Temporal::getRelativeDate($a->profile['howlong'], L10n::t('for %1$d %2$s'));
			}

			if ($a->profile['sexual']) {
				$profile['sexual'] = [L10n::t('Sexual Preference:'), $a->profile['sexual']];
			}

			if ($a->profile['homepage']) {
				$profile['homepage'] = [L10n::t('Homepage:'), linkify($a->profile['homepage'])];
			}

			if ($a->profile['hometown']) {
				$profile['hometown'] = [L10n::t('Hometown:'), linkify($a->profile['hometown'])];
			}

			if ($a->profile['pub_keywords']) {
				$profile['pub_keywords'] = [L10n::t('Tags:'), $a->profile['pub_keywords']];
			}

			if ($a->profile['politic']) {
				$profile['politic'] = [L10n::t('Political Views:'), $a->profile['politic']];
			}

			if ($a->profile['religion']) {
				$profile['religion'] = [L10n::t('Religion:'), $a->profile['religion']];
			}

			if ($txt = prepare_text($a->profile['about'])) {
				$profile['about'] = [L10n::t('About:'), $txt];
			}

			if ($txt = prepare_text($a->profile['interest'])) {
				$profile['interest'] = [L10n::t('Hobbies/Interests:'), $txt];
			}

			if ($txt = prepare_text($a->profile['likes'])) {
				$profile['likes'] = [L10n::t('Likes:'), $txt];
			}

			if ($txt = prepare_text($a->profile['dislikes'])) {
				$profile['dislikes'] = [L10n::t('Dislikes:'), $txt];
			}

			if ($txt = prepare_text($a->profile['contact'])) {
				$profile['contact'] = [L10n::t('Contact information and Social Networks:'), $txt];
			}

			if ($txt = prepare_text($a->profile['music'])) {
				$profile['music'] = [L10n::t('Musical interests:'), $txt];
			}

			if ($txt = prepare_text($a->profile['book'])) {
				$profile['book'] = [L10n::t('Books, literature:'), $txt];
			}

			if ($txt = prepare_text($a->profile['tv'])) {
				$profile['tv'] = [L10n::t('Television:'), $txt];
			}

			if ($txt = prepare_text($a->profile['film'])) {
				$profile['film'] = [L10n::t('Film/dance/culture/entertainment:'), $txt];
			}

			if ($txt = prepare_text($a->profile['romance'])) {
				$profile['romance'] = [L10n::t('Love/Romance:'), $txt];
			}

			if ($txt = prepare_text($a->profile['work'])) {
				$profile['work'] = [L10n::t('Work/employment:'), $txt];
			}

			if ($txt = prepare_text($a->profile['education'])) {
				$profile['education'] = [L10n::t('School/education:'), $txt];
			}

			//show subcribed forum if it is enabled in the usersettings
			if (Feature::isEnabled($uid, 'forumlist_profile')) {
				$profile['forumlist'] = [L10n::t('Forums:'), ForumManager::profileAdvanced($uid)];
			}

			if ($a->profile['uid'] == local_user()) {
				$profile['edit'] = [System::baseUrl() . '/profiles/' . $a->profile['id'], L10n::t('Edit profile'), '', L10n::t('Edit profile')];
			}

			return replace_macros($tpl, [
				'$title' => L10n::t('Profile'),
				'$basic' => L10n::t('Basic'),
				'$advanced' => L10n::t('Advanced'),
				'$profile' => $profile
			]);
		}

		return '';
	}

	public static function getTabs($a, $is_owner = false, $nickname = null)
	{
		if (is_null($nickname)) {
			$nickname = $a->user['nickname'];
		}

		$tab = false;
		if (x($_GET, 'tab')) {
			$tab = notags(trim($_GET['tab']));
		}

		$url = System::baseUrl() . '/profile/' . $nickname;

		$tabs = [
			[
				'label' => L10n::t('Status'),
				'url'   => $url,
				'sel'   => !$tab && $a->argv[0] == 'profile' ? 'active' : '',
				'title' => L10n::t('Status Messages and Posts'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => L10n::t('Profile'),
				'url'   => $url . '/?tab=profile',
				'sel'   => $tab == 'profile' ? 'active' : '',
				'title' => L10n::t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'r',
			],
			[
				'label' => L10n::t('Photos'),
				'url'   => System::baseUrl() . '/photos/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'photos' ? 'active' : '',
				'title' => L10n::t('Photo Albums'),
				'id'    => 'photo-tab',
				'accesskey' => 'h',
			],
			[
				'label' => L10n::t('Videos'),
				'url'   => System::baseUrl() . '/videos/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'videos' ? 'active' : '',
				'title' => L10n::t('Videos'),
				'id'    => 'video-tab',
				'accesskey' => 'v',
			],
		];

		// the calendar link for the full featured events calendar
		if ($is_owner && $a->theme_events_in_profile) {
			$tabs[] = [
				'label' => L10n::t('Events'),
				'url'   => System::baseUrl() . '/events',
				'sel'   => !$tab && $a->argv[0] == 'events' ? 'active' : '',
				'title' => L10n::t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
			// if the user is not the owner of the calendar we only show a calendar
			// with the public events of the calendar owner
		} elseif (!$is_owner) {
			$tabs[] = [
				'label' => L10n::t('Events'),
				'url'   => System::baseUrl() . '/cal/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'cal' ? 'active' : '',
				'title' => L10n::t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
		}

		if ($is_owner) {
			$tabs[] = [
				'label' => L10n::t('Personal Notes'),
				'url'   => System::baseUrl() . '/notes',
				'sel'   => !$tab && $a->argv[0] == 'notes' ? 'active' : '',
				'title' => L10n::t('Only You Can See This'),
				'id'    => 'notes-tab',
				'accesskey' => 't',
			];
		}

		if (!empty($_SESSION['new_member']) && $is_owner) {
			$tabs[] = [
				'label' => L10n::t('Tips for New Members'),
				'url'   => System::baseUrl() . '/newmember',
				'sel'   => false,
				'title' => L10n::t('Tips for New Members'),
				'id'    => 'newmember-tab',
			];
		}

		if (!$is_owner && empty($a->profile['hide-friends'])) {
			$tabs[] = [
				'label' => L10n::t('Contacts'),
				'url'   => System::baseUrl() . '/viewcontacts/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'viewcontacts' ? 'active' : '',
				'title' => L10n::t('Contacts'),
				'id'    => 'viewcontacts-tab',
				'accesskey' => 'k',
			];
		}

		$arr = ['is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => $tab, 'tabs' => $tabs];
		Addon::callHooks('profile_tabs', $arr);

		$tpl = get_markup_template('common_tabs.tpl');

		return replace_macros($tpl, ['$tabs' => $arr['tabs']]);
	}

	/**
	 * Retrieves the my_url session variable
	 *
	 * @return string
	 */
	public static function getMyURL()
	{
		if (x($_SESSION, 'my_url')) {
			return $_SESSION['my_url'];
		}
		return null;
	}

	/**
	 * Process the 'zrl' parameter and initiate the remote authentication.
	 *
	 * This method checks if the visitor has a public contact entry and
	 * redirects the visitor to his/her instance to start the magic auth (Authentication)
	 * process.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/channel.php
	 *
	 * @param App $a Application instance.
	 */
	public static function zrlInit(App $a)
	{
		$my_url = self::getMyURL();
		$my_url = Network::isUrlValid($my_url);

		if ($my_url) {
			if (!local_user()) {
				// Is it a DDoS attempt?
				// The check fetches the cached value from gprobe to reduce the load for this system
				$urlparts = parse_url($my_url);

				$result = Cache::get('gprobe:' . $urlparts['host']);
				if ((!is_null($result)) && (in_array($result['network'], [NETWORK_FEED, NETWORK_PHANTOM]))) {
					logger('DDoS attempt detected for ' . $urlparts['host'] . ' by ' . $_SERVER['REMOTE_ADDR'] . '. server data: ' . print_r($_SERVER, true), LOGGER_DEBUG);
					return;
				}

				Worker::add(PRIORITY_LOW, 'GProbe', $my_url);
				$arr = ['zrl' => $my_url, 'url' => $a->cmd];
				Addon::callHooks('zrl_init', $arr);

				// Try to find the public contact entry of the visitor.
				$cid = Contact::getIdForURL($my_url);
				if (!$cid) {
					logger('No contact record found for ' . $my_url, LOGGER_DEBUG);
					return;
				}

				$contact = DBA::selectFirst('contact',['id', 'url'], ['id' => $cid]);

				if (DBA::isResult($contact) && remote_user() && remote_user() == $contact['id']) {
					// The visitor is already authenticated.
					return;
				}

				logger('Not authenticated. Invoking reverse magic-auth for ' . $my_url, LOGGER_DEBUG);

				// Try to avoid recursion - but send them home to do a proper magic auth.
				$query = str_replace(array('?zrl=', '&zid='), array('?rzrl=', '&rzrl='), $a->query_string);
				// The other instance needs to know where to redirect.
				$dest = urlencode(System::baseUrl() . '/' . $query);

				// We need to extract the basebath from the profile url
				// to redirect the visitors '/magic' module.
				// Note: We should have the basepath of a contact also in the contact table.
				$urlarr = explode('/profile/', $contact['url']);
				$basepath = $urlarr[0];

				if ($basepath != System::baseUrl() && !strstr($dest, '/magic') && !strstr($dest, '/rmagic')) {
					goaway($basepath . '/magic' . '?f=&owa=1&dest=' . $dest);
				}
			}
		}
	}

	/**
	 * OpenWebAuth authentication.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/zid.php
	 *
	 * @param string $token
	 */
	public static function openWebAuthInit($token)
	{
		$a = get_app();

		// Clean old OpenWebAuthToken entries.
		OpenWebAuthToken::purge('owt', '3 MINUTE');

		// Check if the token we got is the same one
		// we have stored in the database.
		$visitor_handle = OpenWebAuthToken::getMeta('owt', 0, $token);

		if($visitor_handle === false) {
			return;
		}

		// Try to find the public contact entry of the visitor.
		$cid = Contact::getIdForURL($visitor_handle);
		if(!$cid) {
			logger('owt: unable to finger ' . $visitor_handle, LOGGER_DEBUG);
			return;
		}

		$visitor = DBA::selectFirst('contact', [], ['id' => $cid]);

		// Authenticate the visitor.
		$_SESSION['authenticated'] = 1;
		$_SESSION['visitor_id'] = $visitor['id'];
		$_SESSION['visitor_handle'] = $visitor['addr'];
		$_SESSION['visitor_home'] = $visitor['url'];
		$_SESSION['my_url'] = $visitor['url'];

		$arr = [
			'visitor' => $visitor,
			'url' => $a->query_string
		];
		/**
		 * @hooks magic_auth_success
		 *   Called when a magic-auth was successful.
		 *   * \e array \b visitor
		 *   * \e string \b url
		 */
		Addon::callHooks('magic_auth_success', $arr);

		$a->contact = $arr['visitor'];

		info(L10n::t('OpenWebAuth: %1$s welcomes %2$s', $a->get_hostname(), $visitor['name']));

		logger('OpenWebAuth: auth success from ' . $visitor['addr'], LOGGER_DEBUG);
	}

	public static function zrl($s, $force = false)
	{
		if (!strlen($s)) {
			return $s;
		}
		if ((!strpos($s, '/profile/')) && (!$force)) {
			return $s;
		}
		if ($force && substr($s, -1, 1) !== '/') {
			$s = $s . '/';
		}
		$achar = strpos($s, '?') ? '&' : '?';
		$mine = self::getMyURL();
		if ($mine && !link_compare($mine, $s)) {
			return $s . $achar . 'zrl=' . urlencode($mine);
		}
		return $s;
	}

	/**
	 * Get the user ID of the page owner.
	 *
	 * Used from within PCSS themes to set theme parameters. If there's a
	 * puid request variable, that is the "page owner" and normally their theme
	 * settings take precedence; unless a local user sets the "always_my_theme"
	 * system pconfig, which means they don't want to see anybody else's theme
	 * settings except their own while on this site.
	 *
	 * @brief Get the user ID of the page owner
	 * @return int user ID
	 *
	 * @note Returns local_user instead of user ID if "always_my_theme"
	 *      is set to true
	 */
	public static function getThemeUid()
	{
		$uid = ((!empty($_REQUEST['puid'])) ? intval($_REQUEST['puid']) : 0);
		if ((local_user()) && ((PConfig::get(local_user(), 'system', 'always_my_theme')) || (!$uid))) {
			return local_user();
		}

		return $uid;
	}

	/**
	* Stip zrl parameter from a string.
	*
	* @param string $s The input string.
	* @return string The zrl.
	*/
	public static function stripZrls($s)
	{
		return preg_replace('/[\?&]zrl=(.*?)([\?&]|$)/is', '', $s);
	}

	/**
	* Stip query parameter from a string.
	*
	* @param string $s The input string.
	* @return string The query parameter.
	*/
	public static function stripQueryParam($s, $param)
	{
		return preg_replace('/[\?&]' . $param . '=(.*?)(&|$)/ism', '$2', $s);
	}
}

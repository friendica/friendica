<?php
/**
 * @file src/Model/Profile.php
 */

namespace Friendica\Model;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Core\Addon;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Protocol\Diaspora;
use dba;

require_once 'include/dba.php';
require_once 'include/bbcode.php';
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

		if ($profile['locality']) {
			$location .= $profile['locality'];
		}

		if ($profile['region'] && ($profile['locality'] != $profile['region'])) {
			if ($location) {
				$location .= ', ';
			}

			$location .= $profile['region'];
		}

		if ($profile['country-name']) {
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
	public static function load(App $a, $nickname, $profile = 0, $profiledata = [], $show_connect = true)
	{
		$user = dba::selectFirst('user', ['uid'], ['nickname' => $nickname]);

		if (!$user && !count($user) && !count($profiledata)) {
			logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
			notice(t('Requested account is not available.') . EOL);
			$a->error = 404;
			return;
		}

		if (!x($a->page, 'aside')) {
			$a->page['aside'] = '';
		}

		if ($profiledata) {
			$a->page['aside'] .= self::sidebar($profiledata, true, $show_connect);

			if (!DBM::isResult($user)) {
				return;
			}
		}

		$pdata = self::getByNickname($nickname, $user[0]['uid'], $profile);

		if (empty($pdata) && empty($profiledata)) {
			logger('profile error: ' . $a->query_string, LOGGER_DEBUG);
			notice(t('Requested profile is not available.') . EOL);
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

		$a->page['title'] = $a->profile['name'] . ' @ ' . $a->config['sitename'];

		if (!$profiledata && !PConfig::get(local_user(), 'system', 'always_my_theme')) {
			$_SESSION['theme'] = $a->profile['theme'];
		}

		$_SESSION['mobile-theme'] = $a->profile['mobile-theme'];

		/*
		* load/reload current theme info
		*/

		$a->set_template_engine(); // reset the template engine to the default in case the user's theme doesn't specify one

		$theme_info_file = 'view/theme/' . current_theme() . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		if (!x($a->page, 'aside')) {
			$a->page['aside'] = '';
		}

		if (local_user() && local_user() == $a->profile['uid'] && $profiledata) {
			$a->page['aside'] .= replace_macros(
				get_markup_template('profile_edlink.tpl'),
				[
					'$editprofile' => t('Edit profile'),
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
					$contact = dba::selectFirst('contact', ['profile-id'], ['id' => $visitor['cid']]);
					if (DBM::isResult($contact)) {
						$profile_id = $contact['profile-id'];
					}
					break;
				}
			}
		}

		$profile = null;

		if ($profile_id) {
			$profile = dba::fetch_first(
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
		if (!DBM::isResult($profile)) {
			$profile = dba::fetch_first(
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
	 * @return HTML string suitable for sidebar inclusion
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
		$connect = $profile['uid'] != local_user() ? t('Connect') : false;

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

		// Is the local user already connected to that user?
		if ($connect && local_user()) {
			if (isset($profile['url'])) {
				$profile_url = normalise_link($profile['url']);
			} else {
				$profile_url = normalise_link(System::baseUrl() . '/profile/' . $profile['nickname']);
			}

			if (dba::exists('contact', ['pending' => false, 'uid' => local_user(), 'nurl' => $profile_url])) {
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
			$subscribe_feed = t('Atom feed');
		} else {
			$subscribe_feed = false;
		}

		if (remote_user() || (self::getMyURL() && x($profile, 'unkmail') && ($profile['uid'] != local_user()))) {
			$wallmessage = t('Message');
			$wallmessage_link = 'wallmessage/' . $profile['nickname'];

			if (remote_user()) {
				$r = q(
					"SELECT `url` FROM `contact` WHERE `uid` = %d AND `id` = '%s' AND `rel` = %d",
					intval($profile['uid']),
					intval(remote_user()),
					intval(CONTACT_IS_FRIEND)
				);
			} else {
				$r = q(
					"SELECT `url` FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND `rel` = %d",
					intval($profile['uid']),
					dbesc(normalise_link(self::getMyURL())),
					intval(CONTACT_IS_FRIEND)
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
			$profile['edit'] = [System::baseUrl() . '/profiles', t('Profiles'), '', t('Manage/edit profiles')];
			$r = q(
				"SELECT * FROM `profile` WHERE `uid` = %d",
				local_user()
			);

			$profile['menu'] = [
				'chg_photo' => t('Change profile photo'),
				'cr_new' => t('Create New Profile'),
				'entries' => [],
			];

			if (DBM::isResult($r)) {
				foreach ($r as $rr) {
					$profile['menu']['entries'][] = [
						'photo' => $rr['thumb'],
						'id' => $rr['id'],
						'alt' => t('Profile Image'),
						'profile_name' => $rr['profile-name'],
						'isdefault' => $rr['is-default'],
						'visibile_to_everybody' => t('visible to everybody'),
						'edit_visibility' => t('Edit visibility'),
					];
				}
			}
		}
		if (!$is_contact && $profile['uid'] == local_user() && !Feature::isEnabled(local_user(), 'multi_profiles')) {
			$profile['edit'] = [System::baseUrl() . '/profiles/' . $profile['id'], t('Edit profile'), '', t('Edit profile')];
			$profile['menu'] = [
				'chg_photo' => t('Change profile photo'),
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
			$location = t('Location:');
		}

		$gender   = x($profile, 'gender')   ? t('Gender:')   : false;
		$marital  = x($profile, 'marital')  ? t('Status:')   : false;
		$homepage = x($profile, 'homepage') ? t('Homepage:') : false;
		$about    = x($profile, 'about')    ? t('About:')    : false;
		$xmpp     = x($profile, 'xmpp')     ? t('XMPP:')     : false;

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
				'photo300' => $profile['contact_photo'],
				'photo100' => $profile['contact_thumb'],
				'photo50' => $profile['contact_micro'],
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
				if (DBM::isResult($r)) {
					$updated = date('c', strtotime($r[0]['updated']));
				}

				$r = q(
					"SELECT COUNT(*) AS `total` FROM `contact`
					WHERE `uid` = %d
						AND NOT `self` AND NOT `blocked` AND NOT `pending`
						AND NOT `hidden` AND NOT `archive`
						AND `network` IN ('%s', '%s', '%s', '')",
					intval($profile['uid']),
					dbesc(NETWORK_DFRN),
					dbesc(NETWORK_DIASPORA),
					dbesc(NETWORK_OSTATUS)
				);
				if (DBM::isResult($r)) {
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
			$p['about'] = bbcode($p['about']);
		}

		if (isset($p['address'])) {
			$p['address'] = bbcode($p['address']);
		} else {
			$p['address'] = bbcode($p['location']);
		}

		if (isset($p['photo'])) {
			$p['photo'] = proxy_url($p['photo'], false, PROXY_SIZE_SMALL);
		}

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
			'$network' => t('Network:'),
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

		$bd_format = t('g A l F d'); // 8 AM Friday January 18
		$bd_short = t('F d');

		$cachekey = 'get_birthdays:' . local_user();
		$r = Cache::get($cachekey);
		if (is_null($r)) {
			$s = dba::p(
				"SELECT `event`.*, `event`.`id` AS `eid`, `contact`.* FROM `event`
				INNER JOIN `contact` ON `contact`.`id` = `event`.`cid`
				WHERE `event`.`uid` = ? AND `type` = 'birthday' AND `start` < ? AND `finish` > ?
				ORDER BY `start` ASC ",
				local_user(),
				datetime_convert('UTC', 'UTC', 'now + 6 days'),
				datetime_convert('UTC', 'UTC', 'now')
			);
			if (DBM::isResult($s)) {
				$r = dba::inArray($s);
				Cache::set($cachekey, $r, CACHE_HOUR);
			}
		}
		if (DBM::isResult($r)) {
			$total = 0;
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
					$url = $rr['url'];
					if ($rr['network'] === NETWORK_DFRN) {
						$url = System::baseUrl() . '/redir/' . $rr['cid'];
					}

					$rr['link'] = $url;
					$rr['title'] = $rr['name'];
					$rr['date'] = day_translate(datetime_convert('UTC', $a->timezone, $rr['start'], $rr['adjust'] ? $bd_format : $bd_short)) . (($today) ? ' ' . t('[today]') : '');
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
			'$event_reminders' => t('Birthday Reminders'),
			'$event_title' => t('Birthdays this week:'),
			'$events' => $r,
			'$lbr' => '{', // raw brackets mess up if/endif macro processing
			'$rbr' => '}'
		]);
	}

	public static function getEvents()
	{
		require_once 'include/bbcode.php';

		$a = get_app();

		if (!local_user() || $a->is_mobile || $a->is_tablet) {
			return $o;
		}

		/*
		* 	$mobile_detect = new Mobile_Detect();
		* 		$is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		* 		if ($is_mobile)
		* 			return $o;
		*/

		$bd_format = t('g A l F d'); // 8 AM Friday January 18
		$classtoday = '';

		$s = dba::p(
			"SELECT `event`.* FROM `event`
			WHERE `event`.`uid` = ? AND `type` != 'birthday' AND `start` < ? AND `start` >= ?
			ORDER BY `start` ASC ",
			local_user(),
			datetime_convert('UTC', 'UTC', 'now + 7 days'),
			datetime_convert('UTC', 'UTC', 'now - 1 days')
		);

		$r = [];

		if (DBM::isResult($s)) {
			$istoday = false;

			while ($rr = dba::fetch($s)) {
				if (strlen($rr['name'])) {
					$total ++;
				}

				$strt = datetime_convert('UTC', $rr['convert'] ? $a->timezone : 'UTC', $rr['start'], 'Y-m-d');
				if ($strt === datetime_convert('UTC', $a->timezone, 'now', 'Y-m-d')) {
					$istoday = true;
				}

				$title = strip_tags(html_entity_decode(bbcode($rr['summary']), ENT_QUOTES, 'UTF-8'));

				if (strlen($title) > 35) {
					$title = substr($title, 0, 32) . '... ';
				}

				$description = substr(strip_tags(bbcode($rr['desc'])), 0, 32) . '... ';
				if (!$description) {
					$description = t('[No description]');
				}

				$strt = datetime_convert('UTC', $rr['convert'] ? $a->timezone : 'UTC', $rr['start']);

				if (substr($strt, 0, 10) < datetime_convert('UTC', $a->timezone, 'now', 'Y-m-d')) {
					continue;
				}

				$today = ((substr($strt, 0, 10) === datetime_convert('UTC', $a->timezone, 'now', 'Y-m-d')) ? true : false);

				$rr['title'] = $title;
				$rr['description'] = $description;
				$rr['date'] = day_translate(datetime_convert('UTC', $rr['adjust'] ? $a->timezone : 'UTC', $rr['start'], $bd_format)) . (($today) ? ' ' . t('[today]') : '');
				$rr['startime'] = $strt;
				$rr['today'] = $today;

				$r[] = $rr;
			}
			dba::close($s);
			$classtoday = (($istoday) ? 'event-today' : '');
		}
		$tpl = get_markup_template('events_reminder.tpl');
		return replace_macros($tpl, [
			'$baseurl' => System::baseUrl(),
			'$classtoday' => $classtoday,
			'$count' => count($r),
			'$event_reminders' => t('Event Reminders'),
			'$event_title' => t('Events this week:'),
			'$events' => $r,
		]);
	}

	public static function getAdvanced(App $a)
	{
		$o = '';
		$uid = $a->profile['uid'];

		$o .= replace_macros(
			get_markup_template('section_title.tpl'),
			['$title' => t('Profile')]
		);

		if ($a->profile['name']) {
			$tpl = get_markup_template('profile_advanced.tpl');

			$profile = [];

			$profile['fullname'] = [t('Full Name:'), $a->profile['name']];

			if ($a->profile['gender']) {
				$profile['gender'] = [t('Gender:'), $a->profile['gender']];
			}

			if (($a->profile['dob']) && ($a->profile['dob'] > '0001-01-01')) {
				$year_bd_format = t('j F, Y');
				$short_bd_format = t('j F');

				$val = intval($a->profile['dob']) ?
					day_translate(datetime_convert('UTC', 'UTC', $a->profile['dob'] . ' 00:00 +00:00', $year_bd_format))
					: day_translate(datetime_convert('UTC', 'UTC', '2001-' . substr($a->profile['dob'], 5) . ' 00:00 +00:00', $short_bd_format));

				$profile['birthday'] = [t('Birthday:'), $val];
			}

			if (!empty($a->profile['dob'])
				&& $a->profile['dob'] > '0001-01-01'
				&& $age = age($a->profile['dob'], $a->profile['timezone'], '')
			) {
				$profile['age'] = [t('Age:'), $age];
			}

			if ($a->profile['marital']) {
				$profile['marital'] = [t('Status:'), $a->profile['marital']];
			}

			/// @TODO Maybe use x() here, plus below?
			if ($a->profile['with']) {
				$profile['marital']['with'] = $a->profile['with'];
			}

			if (strlen($a->profile['howlong']) && $a->profile['howlong'] >= NULL_DATE) {
				$profile['howlong'] = relative_date($a->profile['howlong'], t('for %1$d %2$s'));
			}

			if ($a->profile['sexual']) {
				$profile['sexual'] = [t('Sexual Preference:'), $a->profile['sexual']];
			}

			if ($a->profile['homepage']) {
				$profile['homepage'] = [t('Homepage:'), linkify($a->profile['homepage'])];
			}

			if ($a->profile['hometown']) {
				$profile['hometown'] = [t('Hometown:'), linkify($a->profile['hometown'])];
			}

			if ($a->profile['pub_keywords']) {
				$profile['pub_keywords'] = [t('Tags:'), $a->profile['pub_keywords']];
			}

			if ($a->profile['politic']) {
				$profile['politic'] = [t('Political Views:'), $a->profile['politic']];
			}

			if ($a->profile['religion']) {
				$profile['religion'] = [t('Religion:'), $a->profile['religion']];
			}

			if ($txt = prepare_text($a->profile['about'])) {
				$profile['about'] = [t('About:'), $txt];
			}

			if ($txt = prepare_text($a->profile['interest'])) {
				$profile['interest'] = [t('Hobbies/Interests:'), $txt];
			}

			if ($txt = prepare_text($a->profile['likes'])) {
				$profile['likes'] = [t('Likes:'), $txt];
			}

			if ($txt = prepare_text($a->profile['dislikes'])) {
				$profile['dislikes'] = [t('Dislikes:'), $txt];
			}

			if ($txt = prepare_text($a->profile['contact'])) {
				$profile['contact'] = [t('Contact information and Social Networks:'), $txt];
			}

			if ($txt = prepare_text($a->profile['music'])) {
				$profile['music'] = [t('Musical interests:'), $txt];
			}

			if ($txt = prepare_text($a->profile['book'])) {
				$profile['book'] = [t('Books, literature:'), $txt];
			}

			if ($txt = prepare_text($a->profile['tv'])) {
				$profile['tv'] = [t('Television:'), $txt];
			}

			if ($txt = prepare_text($a->profile['film'])) {
				$profile['film'] = [t('Film/dance/culture/entertainment:'), $txt];
			}

			if ($txt = prepare_text($a->profile['romance'])) {
				$profile['romance'] = [t('Love/Romance:'), $txt];
			}

			if ($txt = prepare_text($a->profile['work'])) {
				$profile['work'] = [t('Work/employment:'), $txt];
			}

			if ($txt = prepare_text($a->profile['education'])) {
				$profile['education'] = [t('School/education:'), $txt];
			}

			//show subcribed forum if it is enabled in the usersettings
			if (Feature::isEnabled($uid, 'forumlist_profile')) {
				$profile['forumlist'] = [t('Forums:'), ForumManager::profileAdvanced($uid)];
			}

			if ($a->profile['uid'] == local_user()) {
				$profile['edit'] = [System::baseUrl() . '/profiles/' . $a->profile['id'], t('Edit profile'), '', t('Edit profile')];
			}

			return replace_macros($tpl, [
				'$title' => t('Profile'),
				'$basic' => t('Basic'),
				'$advanced' => t('Advanced'),
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
				'label' => t('Status'),
				'url'   => $url,
				'sel'   => !$tab && $a->argv[0] == 'profile' ? 'active' : '',
				'title' => t('Status Messages and Posts'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => t('Profile'),
				'url'   => $url . '/?tab=profile',
				'sel'   => $tab == 'profile' ? 'active' : '',
				'title' => t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'r',
			],
			[
				'label' => t('Photos'),
				'url'   => System::baseUrl() . '/photos/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'photos' ? 'active' : '',
				'title' => t('Photo Albums'),
				'id'    => 'photo-tab',
				'accesskey' => 'h',
			],
			[
				'label' => t('Videos'),
				'url'   => System::baseUrl() . '/videos/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'videos' ? 'active' : '',
				'title' => t('Videos'),
				'id'    => 'video-tab',
				'accesskey' => 'v',
			],
		];

		// the calendar link for the full featured events calendar
		if ($is_owner && $a->theme_events_in_profile) {
			$tabs[] = [
				'label' => t('Events'),
				'url'   => System::baseUrl() . '/events',
				'sel'   => !$tab && $a->argv[0] == 'events' ? 'active' : '',
				'title' => t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
			// if the user is not the owner of the calendar we only show a calendar
			// with the public events of the calendar owner
		} elseif (!$is_owner) {
			$tabs[] = [
				'label' => t('Events'),
				'url'   => System::baseUrl() . '/cal/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'cal' ? 'active' : '',
				'title' => t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
		}

		if ($is_owner) {
			$tabs[] = [
				'label' => t('Personal Notes'),
				'url'   => System::baseUrl() . '/notes',
				'sel'   => !$tab && $a->argv[0] == 'notes' ? 'active' : '',
				'title' => t('Only You Can See This'),
				'id'    => 'notes-tab',
				'accesskey' => 't',
			];
		}

		if ((!$is_owner) && ((count($a->profile)) || (!$a->profile['hide-friends']))) {
			$tabs[] = [
				'label' => t('Contacts'),
				'url'   => System::baseUrl() . '/viewcontacts/' . $nickname,
				'sel'   => !$tab && $a->argv[0] == 'viewcontacts' ? 'active' : '',
				'title' => t('Contacts'),
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

	public static function zrlInit(App $a)
	{
		$my_url = self::getMyURL();
		$my_url = validate_url($my_url);
		if ($my_url) {
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
		}
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
}

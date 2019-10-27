<?php
/**
 * @file src/Model/Profile.php
 */
namespace Friendica\Model;

use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Content\ForumManager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Content\Widget\ContactBlock;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Diaspora;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;

class Profile
{
	/**
	 * Returns default profile for a given user id
	 *
	 * @param integer User ID
	 *
	 * @return array Profile data
	 * @throws \Exception
	 */
	public static function getByUID($uid)
	{
		$profile = DBA::selectFirst('profile', [], ['uid' => $uid, 'is-default' => true]);
		return $profile;
	}

	/**
	 * Returns default profile for a given user ID and ID
	 *
	 * @param int $uid The contact ID
	 * @param int $id The contact owner ID
	 * @param array $fields The selected fields
	 *
	 * @return array Profile data for the ID
	 * @throws \Exception
	 */
	public static function getById(int $uid, int $id, array $fields = [])
	{
		return DBA::selectFirst('profile', $fields, ['uid' => $uid, 'id' => $id]);
	}

	/**
	 * Returns profile data for the contact owner
	 *
	 * @param int $uid The User ID
	 * @param array $fields The fields to retrieve
	 *
	 * @return array Array of profile data
	 * @throws \Exception
	 */
	public static function getListByUser(int $uid, array $fields = [])
	{
		return DBA::selectToArray('profile', $fields, ['uid' => $uid]);
	}

	/**
	 * Returns a formatted location string from the given profile array
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
	 * @param App     $a
	 * @param string  $nickname     string
	 * @param int     $profile      int
	 * @param array   $profiledata  array
	 * @param boolean $show_connect Show connect link
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function load(App $a, $nickname, $profile = 0, array $profiledata = [], $show_connect = true)
	{
		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nickname, 'account_removed' => false]);

		if (!DBA::isResult($user) && empty($profiledata)) {
			Logger::log('profile error: ' . DI::args()->getQueryString(), Logger::DEBUG);
			return;
		}

		if (count($profiledata) > 0) {
			// Ensure to have a "nickname" field
			if (empty($profiledata['nickname']) && !empty($profiledata['nick'])) {
				$profiledata['nickname'] = $profiledata['nick'];
			}

			// Add profile data to sidebar
			DI::page()['aside'] .= self::sidebar($a, $profiledata, true, $show_connect);

			if (!DBA::isResult($user)) {
				return;
			}
		}

		$pdata = self::getByNickname($nickname, $user['uid'], $profile);

		if (empty($pdata) && empty($profiledata)) {
			Logger::log('profile error: ' . DI::args()->getQueryString(), Logger::DEBUG);
			return;
		}

		if (empty($pdata)) {
			$pdata = ['uid' => 0, 'profile_uid' => 0, 'is-default' => false,'name' => $nickname];
		}

		// fetch user tags if this isn't the default profile

		if (!$pdata['is-default']) {
			$condition = ['uid' => $pdata['profile_uid'], 'is-default' => true];
			$profile = DBA::selectFirst('profile', ['pub_keywords'], $condition);
			if (DBA::isResult($profile)) {
				$pdata['pub_keywords'] = $profile['pub_keywords'];
			}
		}

		$a->profile = $pdata;
		$a->profile_uid = $pdata['profile_uid'];

		$a->profile['mobile-theme'] = DI::pConfig()->get($a->profile['profile_uid'], 'system', 'mobile_theme');
		$a->profile['network'] = Protocol::DFRN;

		DI::page()['title'] = $a->profile['name'] . ' @ ' . Config::get('config', 'sitename');

		if (!$profiledata && !DI::pConfig()->get(local_user(), 'system', 'always_my_theme')) {
			$a->setCurrentTheme($a->profile['theme']);
			$a->setCurrentMobileTheme($a->profile['mobile-theme']);
		}

		/*
		* load/reload current theme info
		*/

		Renderer::setActiveTemplateEngine(); // reset the template engine to the default in case the user's theme doesn't specify one

		$theme_info_file = 'view/theme/' . $a->getCurrentTheme() . '/theme.php';
		if (file_exists($theme_info_file)) {
			require_once $theme_info_file;
		}

		if (local_user() && local_user() == $a->profile['uid'] && $profiledata) {
			DI::page()['aside'] .= Renderer::replaceMacros(
				Renderer::getMarkupTemplate('settings/profile/link.tpl'),
				[
					'$editprofile' => DI::l10n()->t('Edit profile'),
					'$profid' => $a->profile['id']
				]
			);
		}

		$block = ((Config::get('system', 'block_public') && !Session::isAuthenticated()) ? true : false);

		/**
		 * @todo
		 * By now, the contact block isn't shown, when a different profile is given
		 * But: When this profile was on the same server, then we could display the contacts
		 */
		if (!$profiledata) {
			DI::page()['aside'] .= self::sidebar($a, $a->profile, $block, $show_connect);
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
	 * @param string $nickname   nick
	 * @param int    $uid        uid
	 * @param int    $profile_id ID of the profile
	 * @return array
	 * @throws \Exception
	 */
	public static function getByNickname($nickname, $uid = 0, $profile_id = 0)
	{
		if (!empty(Session::getRemoteContactID($uid))) {
			$contact = DBA::selectFirst('contact', ['profile-id'], ['id' => Session::getRemoteContactID($uid)]);
			if (DBA::isResult($contact)) {
				$profile_id = $contact['profile-id'];
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
	 * @param array   $profile
	 * @param int     $block
	 * @param boolean $show_connect Show connect link
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
	private static function sidebar(App $a, $profile, $block = 0, $show_connect = true)
	{
		$o = '';
		$location = false;

		// This function can also use contact information in $profile
		$is_contact = !empty($profile['cid']);

		if (!is_array($profile) && !count($profile)) {
			return $o;
		}

		$profile['picdate'] = urlencode($profile['picdate'] ?? '');

		if (($profile['network'] != '') && ($profile['network'] != Protocol::DFRN)) {
			$profile['network_link'] = Strings::formatNetworkName($profile['network'], $profile['url']);
		} else {
			$profile['network_link'] = '';
		}

		Hook::callAll('profile_sidebar_enter', $profile);

		if (isset($profile['url'])) {
			$profile_url = $profile['url'];
		} else {
			$profile_url = DI::baseUrl()->get() . '/profile/' . $profile['nickname'];
		}

		$follow_link = null;
		$unfollow_link = null;
		$subscribe_feed_link = null;
		$wallmessage_link = null;



		$visitor_contact = [];
		if (!empty($profile['uid']) && self::getMyURL()) {
			$visitor_contact = Contact::selectFirst(['rel'], ['uid' => $profile['uid'], 'nurl' => Strings::normaliseLink(self::getMyURL())]);
		}

		$profile_contact = [];
		if (!empty($profile['cid']) && self::getMyURL()) {
			$profile_contact = Contact::selectFirst(['rel'], ['id' => $profile['cid']]);
		}

		$profile_is_dfrn = $profile['network'] == Protocol::DFRN;
		$profile_is_native = in_array($profile['network'], Protocol::NATIVE_SUPPORT);
		$local_user_is_self = local_user() && local_user() == ($profile['profile_uid'] ?? 0);
		$visitor_is_authenticated = (bool)self::getMyURL();
		$visitor_is_following =
			in_array($visitor_contact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND])
			|| in_array($profile_contact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND]);
		$visitor_is_followed =
			in_array($visitor_contact['rel'] ?? 0, [Contact::SHARING, Contact::FRIEND])
			|| in_array($profile_contact['rel'] ?? 0, [Contact::FOLLOWER, Contact::FRIEND]);
		$visitor_base_path = self::getMyURL() ? preg_replace('=/profile/(.*)=ism', '', self::getMyURL()) : '';

		if (!$local_user_is_self && $show_connect) {
			if (!$visitor_is_authenticated) {
				if (!empty($profile['nickname'])) {
					$follow_link = 'dfrn_request/' . $profile['nickname'];
				}
			} elseif ($profile_is_native) {
				if ($visitor_is_following) {
					$unfollow_link = $visitor_base_path . '/unfollow?url=' . urlencode($profile_url);
				} else {
					$follow_link =  $visitor_base_path .'/follow?url=' . urlencode($profile_url);
				}
			}

			if ($profile_is_dfrn) {
				$subscribe_feed_link = 'dfrn_poll/' . $profile['nickname'];
			}

			if (Contact::canReceivePrivateMessages($profile)) {
				if ($visitor_is_followed || $visitor_is_following) {
					$wallmessage_link = $visitor_base_path . '/message/new/' . base64_encode($profile['addr'] ?? '');
				} elseif ($visitor_is_authenticated && !empty($profile['unkmail'])) {
					$wallmessage_link = 'wallmessage/' . $profile['nickname'];
				}
			}
		}

		// show edit profile to yourself
		if (!$is_contact && $local_user_is_self) {
			$profile['edit'] = [DI::baseUrl() . '/settings/profile', DI::l10n()->t('Edit profile'), '', DI::l10n()->t('Edit profile')];
			$profile['menu'] = [
				'chg_photo' => DI::l10n()->t('Change profile photo'),
				'cr_new' => null,
				'entries' => [],
			];
		}

		// Fetch the account type
		$account_type = Contact::getAccountType($profile);

		if (!empty($profile['address'])
			|| !empty($profile['location'])
			|| !empty($profile['locality'])
			|| !empty($profile['region'])
			|| !empty($profile['postal-code'])
			|| !empty($profile['country-name'])
		) {
			$location = DI::l10n()->t('Location:');
		}

		$gender   = !empty($profile['gender'])   ? DI::l10n()->t('Gender:')   : false;
		$marital  = !empty($profile['marital'])  ? DI::l10n()->t('Status:')   : false;
		$homepage = !empty($profile['homepage']) ? DI::l10n()->t('Homepage:') : false;
		$about    = !empty($profile['about'])    ? DI::l10n()->t('About:')    : false;
		$xmpp     = !empty($profile['xmpp'])     ? DI::l10n()->t('XMPP:')     : false;

		if ((!empty($profile['hidewall']) || $block) && !Session::isAuthenticated()) {
			$location = $gender = $marital = $homepage = $about = false;
		}

		$split_name = Diaspora::splitName($profile['name']);
		$firstname = $split_name['first'];
		$lastname = $split_name['last'];

		if (!empty($profile['guid'])) {
			$diaspora = [
				'guid' => $profile['guid'],
				'podloc' => DI::baseUrl(),
				'searchable' => (($profile['publish'] && $profile['net-publish']) ? 'true' : 'false'),
				'nickname' => $profile['nickname'],
				'fullname' => $profile['name'],
				'firstname' => $firstname,
				'lastname' => $lastname,
				'photo300' => $profile['contact_photo'] ?? '',
				'photo100' => $profile['contact_thumb'] ?? '',
				'photo50' => $profile['contact_micro'] ?? '',
			];
		} else {
			$diaspora = false;
		}

		$contact_block = '';
		$updated = '';
		$contact_count = 0;
		if (!$block) {
			$contact_block = ContactBlock::getHTML($a->profile);

			if (is_array($a->profile) && !$a->profile['hide-friends']) {
				$r = q(
					"SELECT `gcontact`.`updated` FROM `contact` INNER JOIN `gcontact` WHERE `gcontact`.`nurl` = `contact`.`nurl` AND `self` AND `uid` = %d LIMIT 1",
					intval($a->profile['uid'])
				);
				if (DBA::isResult($r)) {
					$updated = date('c', strtotime($r[0]['updated']));
				}

				$contact_count = DBA::count('contact', [
					'uid' => $profile['uid'],
					'self' => false,
					'blocked' => false,
					'pending' => false,
					'hidden' => false,
					'archive' => false,
					'network' => Protocol::FEDERATED,
				]);
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

		if (empty($p['address']) && !empty($p['location'])) {
			$p['address'] = $p['location'];
		}

		if (isset($p['address'])) {
			$p['address'] = BBCode::convert($p['address']);
		}

		if (isset($p['gender'])) {
			$p['gender'] = DI::l10n()->t($p['gender']);
		}

		if (isset($p['marital'])) {
			$p['marital'] = DI::l10n()->t($p['marital']);
		}

		if (isset($p['photo'])) {
			$p['photo'] = ProxyUtils::proxifyUrl($p['photo'], false, ProxyUtils::SIZE_SMALL);
		}

		$p['url'] = Contact::magicLink(($p['url'] ?? '') ?: $profile_url);

		$tpl = Renderer::getMarkupTemplate('profile/vcard.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$profile' => $p,
			'$xmpp' => $xmpp,
			'$follow' => DI::l10n()->t('Follow'),
			'$follow_link' => $follow_link,
			'$unfollow' => DI::l10n()->t('Unfollow'),
			'$unfollow_link' => $unfollow_link,
			'$subscribe_feed' => DI::l10n()->t('Atom feed'),
			'$subscribe_feed_link' => $subscribe_feed_link,
			'$wallmessage' => DI::l10n()->t('Message'),
			'$wallmessage_link' => $wallmessage_link,
			'$account_type' => $account_type,
			'$location' => $location,
			'$gender' => $gender,
			'$marital' => $marital,
			'$homepage' => $homepage,
			'$about' => $about,
			'$network' => DI::l10n()->t('Network:'),
			'$contacts' => $contact_count,
			'$updated' => $updated,
			'$diaspora' => $diaspora,
			'$contact_block' => $contact_block,
		]);

		$arr = ['profile' => &$profile, 'entry' => &$o];

		Hook::callAll('profile_sidebar', $arr);

		return $o;
	}

	public static function getBirthdays()
	{
		$a = DI::app();
		$o = '';

		if (!local_user() || DI::mode()->isMobile() || DI::mode()->isMobile()) {
			return $o;
		}

		/*
		* $mobile_detect = new Mobile_Detect();
		* $is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		* 		if ($is_mobile)
		* 			return $o;
		*/

		$bd_format = DI::l10n()->t('g A l F d'); // 8 AM Friday January 18
		$bd_short = DI::l10n()->t('F d');

		$cachekey = 'get_birthdays:' . local_user();
		$r = DI::cache()->get($cachekey);
		if (is_null($r)) {
			$s = DBA::p(
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
				ORDER BY `start` ASC ",
				Contact::SHARING,
				Contact::FRIEND,
				local_user(),
				DateTimeFormat::utc('now + 6 days'),
				DateTimeFormat::utcNow()
			);
			if (DBA::isResult($s)) {
				$r = DBA::toArray($s);
				DI::cache()->set($cachekey, $r, Duration::HOUR);
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
					$rr['date'] = DI::l10n()->getDay(DateTimeFormat::convert($rr['start'], $a->timezone, 'UTC', $rr['adjust'] ? $bd_format : $bd_short)) . (($today) ? ' ' . DI::l10n()->t('[today]') : '');
					$rr['startime'] = null;
					$rr['today'] = $today;
				}
			}
		}
		$tpl = Renderer::getMarkupTemplate('birthdays_reminder.tpl');
		return Renderer::replaceMacros($tpl, [
			'$classtoday' => $classtoday,
			'$count' => $total,
			'$event_reminders' => DI::l10n()->t('Birthday Reminders'),
			'$event_title' => DI::l10n()->t('Birthdays this week:'),
			'$events' => $r,
			'$lbr' => '{', // raw brackets mess up if/endif macro processing
			'$rbr' => '}'
		]);
	}

	public static function getEventsReminderHTML()
	{
		$a = DI::app();
		$o = '';

		if (!local_user() || DI::mode()->isMobile() || DI::mode()->isMobile()) {
			return $o;
		}

		/*
		* 	$mobile_detect = new Mobile_Detect();
		* 		$is_mobile = $mobile_detect->isMobile() || $mobile_detect->isTablet();
		* 		if ($is_mobile)
		* 			return $o;
		*/

		$bd_format = DI::l10n()->t('g A l F d'); // 8 AM Friday January 18
		$classtoday = '';

		$condition = ["`uid` = ? AND `type` != 'birthday' AND `start` < ? AND `start` >= ?",
			local_user(), DateTimeFormat::utc('now + 7 days'), DateTimeFormat::utc('now - 1 days')];
		$s = DBA::select('event', [], $condition, ['order' => ['start']]);

		$r = [];

		if (DBA::isResult($s)) {
			$istoday = false;
			$total = 0;

			while ($rr = DBA::fetch($s)) {
				$condition = ['parent-uri' => $rr['uri'], 'uid' => $rr['uid'], 'author-id' => public_contact(),
					'activity' => [Item::activityToIndex( Activity::ATTEND), Item::activityToIndex(Activity::ATTENDMAYBE)],
					'visible' => true, 'deleted' => false];
				if (!Item::exists($condition)) {
					continue;
				}

				if (strlen($rr['summary'])) {
					$total++;
				}

				$strt = DateTimeFormat::convert($rr['start'], $rr['adjust'] ? $a->timezone : 'UTC', 'UTC', 'Y-m-d');
				if ($strt === DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) {
					$istoday = true;
				}

				$title = strip_tags(html_entity_decode(BBCode::convert($rr['summary']), ENT_QUOTES, 'UTF-8'));

				if (strlen($title) > 35) {
					$title = substr($title, 0, 32) . '... ';
				}

				$description = substr(strip_tags(BBCode::convert($rr['desc'])), 0, 32) . '... ';
				if (!$description) {
					$description = DI::l10n()->t('[No description]');
				}

				$strt = DateTimeFormat::convert($rr['start'], $rr['adjust'] ? $a->timezone : 'UTC');

				if (substr($strt, 0, 10) < DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) {
					continue;
				}

				$today = ((substr($strt, 0, 10) === DateTimeFormat::timezoneNow($a->timezone, 'Y-m-d')) ? true : false);

				$rr['title'] = $title;
				$rr['description'] = $description;
				$rr['date'] = DI::l10n()->getDay(DateTimeFormat::convert($rr['start'], $rr['adjust'] ? $a->timezone : 'UTC', 'UTC', $bd_format)) . (($today) ? ' ' . DI::l10n()->t('[today]') : '');
				$rr['startime'] = $strt;
				$rr['today'] = $today;

				$r[] = $rr;
			}
			DBA::close($s);
			$classtoday = (($istoday) ? 'event-today' : '');
		}
		$tpl = Renderer::getMarkupTemplate('events_reminder.tpl');
		return Renderer::replaceMacros($tpl, [
			'$classtoday' => $classtoday,
			'$count' => count($r),
			'$event_reminders' => DI::l10n()->t('Event Reminders'),
			'$event_title' => DI::l10n()->t('Upcoming events the next 7 days:'),
			'$events' => $r,
		]);
	}

	public static function getAdvanced(App $a)
	{
		$uid = intval($a->profile['uid']);

		if ($a->profile['name']) {
			$profile = [];

			$profile['fullname'] = [DI::l10n()->t('Full Name:'), $a->profile['name']];

			if (Feature::isEnabled($uid, 'profile_membersince')) {
				$profile['membersince'] = [DI::l10n()->t('Member since:'), DateTimeFormat::local($a->profile['register_date'])];
			}

			if ($a->profile['gender']) {
				$profile['gender'] = [DI::l10n()->t('Gender:'), DI::l10n()->t($a->profile['gender'])];
			}

			if (!empty($a->profile['dob']) && $a->profile['dob'] > DBA::NULL_DATE) {
				$year_bd_format = DI::l10n()->t('j F, Y');
				$short_bd_format = DI::l10n()->t('j F');

				$val = DI::l10n()->getDay(
					intval($a->profile['dob']) ?
						DateTimeFormat::utc($a->profile['dob'] . ' 00:00 +00:00', $year_bd_format)
						: DateTimeFormat::utc('2001-' . substr($a->profile['dob'], 5) . ' 00:00 +00:00', $short_bd_format)
				);

				$profile['birthday'] = [DI::l10n()->t('Birthday:'), $val];
			}

			if (!empty($a->profile['dob'])
				&& $a->profile['dob'] > DBA::NULL_DATE
				&& $age = Temporal::getAgeByTimezone($a->profile['dob'], $a->profile['timezone'], '')
			) {
				$profile['age'] = [DI::l10n()->t('Age:'), $age];
			}

			if ($a->profile['marital']) {
				$profile['marital'] = [DI::l10n()->t('Status:'), DI::l10n()->t($a->profile['marital'])];
			}

			/// @TODO Maybe use x() here, plus below?
			if ($a->profile['with']) {
				$profile['marital']['with'] = $a->profile['with'];
			}

			if (strlen($a->profile['howlong']) && $a->profile['howlong'] > DBA::NULL_DATETIME) {
				$profile['howlong'] = Temporal::getRelativeDate($a->profile['howlong'], DI::l10n()->t('for %1$d %2$s'));
			}

			if ($a->profile['sexual']) {
				$profile['sexual'] = [DI::l10n()->t('Sexual Preference:'), DI::l10n()->t($a->profile['sexual'])];
			}

			if ($a->profile['homepage']) {
				$profile['homepage'] = [DI::l10n()->t('Homepage:'), HTML::toLink($a->profile['homepage'])];
			}

			if ($a->profile['hometown']) {
				$profile['hometown'] = [DI::l10n()->t('Hometown:'), HTML::toLink($a->profile['hometown'])];
			}

			if ($a->profile['pub_keywords']) {
				$profile['pub_keywords'] = [DI::l10n()->t('Tags:'), $a->profile['pub_keywords']];
			}

			if ($a->profile['politic']) {
				$profile['politic'] = [DI::l10n()->t('Political Views:'), $a->profile['politic']];
			}

			if ($a->profile['religion']) {
				$profile['religion'] = [DI::l10n()->t('Religion:'), $a->profile['religion']];
			}

			if ($txt = BBCode::convert($a->profile['about'])) {
				$profile['about'] = [DI::l10n()->t('About:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['interest'])) {
				$profile['interest'] = [DI::l10n()->t('Hobbies/Interests:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['likes'])) {
				$profile['likes'] = [DI::l10n()->t('Likes:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['dislikes'])) {
				$profile['dislikes'] = [DI::l10n()->t('Dislikes:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['contact'])) {
				$profile['contact'] = [DI::l10n()->t('Contact information and Social Networks:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['music'])) {
				$profile['music'] = [DI::l10n()->t('Musical interests:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['book'])) {
				$profile['book'] = [DI::l10n()->t('Books, literature:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['tv'])) {
				$profile['tv'] = [DI::l10n()->t('Television:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['film'])) {
				$profile['film'] = [DI::l10n()->t('Film/dance/culture/entertainment:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['romance'])) {
				$profile['romance'] = [DI::l10n()->t('Love/Romance:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['work'])) {
				$profile['work'] = [DI::l10n()->t('Work/employment:'), $txt];
			}

			if ($txt = BBCode::convert($a->profile['education'])) {
				$profile['education'] = [DI::l10n()->t('School/education:'), $txt];
			}

			//show subcribed forum if it is enabled in the usersettings
			if (Feature::isEnabled($uid, 'forumlist_profile')) {
				$profile['forumlist'] = [DI::l10n()->t('Forums:'), ForumManager::profileAdvanced($uid)];
			}

			if ($a->profile['uid'] == local_user()) {
				$profile['edit'] = [DI::baseUrl() . '/settings/profile', DI::l10n()->t('Edit profile'), '', DI::l10n()->t('Edit profile')];
			}

			$tpl = Renderer::getMarkupTemplate('profile/advanced.tpl');
			return Renderer::replaceMacros($tpl, [
				'$title' => DI::l10n()->t('Profile'),
				'$basic' => DI::l10n()->t('Basic'),
				'$advanced' => DI::l10n()->t('Advanced'),
				'$profile' => $profile
			]);
		}

		return '';
	}

    /**
     * @param App    $a
     * @param string $current
     * @param bool   $is_owner
     * @param string $nickname
     * @return string
     * @throws \Friendica\Network\HTTPException\InternalServerErrorException
     */
	public static function getTabs(App $a, string $current, bool $is_owner, string $nickname = null)
	{
		if (is_null($nickname)) {
			$nickname = $a->user['nickname'];
		}

		$baseProfileUrl = DI::baseUrl() . '/profile/' . $nickname;

		$tabs = [
			[
				'label' => DI::l10n()->t('Profile'),
				'url'   => $baseProfileUrl,
				'sel'   => $current == 'profile' ? 'active' : '',
				'title' => DI::l10n()->t('Profile Details'),
				'id'    => 'profile-tab',
				'accesskey' => 'r',
			],
			[
				'label' => DI::l10n()->t('Status'),
				'url'   => $baseProfileUrl . '/status',
				'sel'   => $current == 'status' ? 'active' : '',
				'title' => DI::l10n()->t('Status Messages and Posts'),
				'id'    => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label' => DI::l10n()->t('Photos'),
				'url'   => DI::baseUrl() . '/photos/' . $nickname,
				'sel'   => $current == 'photos' ? 'active' : '',
				'title' => DI::l10n()->t('Photo Albums'),
				'id'    => 'photo-tab',
				'accesskey' => 'h',
			],
			[
				'label' => DI::l10n()->t('Videos'),
				'url'   => DI::baseUrl() . '/videos/' . $nickname,
				'sel'   => $current == 'videos' ? 'active' : '',
				'title' => DI::l10n()->t('Videos'),
				'id'    => 'video-tab',
				'accesskey' => 'v',
			],
		];

		// the calendar link for the full featured events calendar
		if ($is_owner && $a->theme_events_in_profile) {
			$tabs[] = [
				'label' => DI::l10n()->t('Events'),
				'url'   => DI::baseUrl() . '/events',
				'sel'   => $current == 'events' ? 'active' : '',
				'title' => DI::l10n()->t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
			// if the user is not the owner of the calendar we only show a calendar
			// with the public events of the calendar owner
		} elseif (!$is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Events'),
				'url'   => DI::baseUrl() . '/cal/' . $nickname,
				'sel'   => $current == 'cal' ? 'active' : '',
				'title' => DI::l10n()->t('Events and Calendar'),
				'id'    => 'events-tab',
				'accesskey' => 'e',
			];
		}

		if ($is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Personal Notes'),
				'url'   => DI::baseUrl() . '/notes',
				'sel'   => $current == 'notes' ? 'active' : '',
				'title' => DI::l10n()->t('Only You Can See This'),
				'id'    => 'notes-tab',
				'accesskey' => 't',
			];
		}

		if (empty($a->profile['hide-friends'])) {
			$tabs[] = [
				'label' => DI::l10n()->t('Contacts'),
				'url'   => $baseProfileUrl . '/contacts',
				'sel'   => $current == 'contacts' ? 'active' : '',
				'title' => DI::l10n()->t('Contacts'),
				'id'    => 'viewcontacts-tab',
				'accesskey' => 'k',
			];
		}

		if (!empty($_SESSION['new_member']) && $is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Tips for New Members'),
				'url'   => DI::baseUrl() . '/newmember',
				'sel'   => false,
				'title' => DI::l10n()->t('Tips for New Members'),
				'id'    => 'newmember-tab',
			];
		}

		$arr = ['is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => $current, 'tabs' => $tabs];

		Hook::callAll('profile_tabs', $arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs']]);
	}

	/**
	 * Retrieves the my_url session variable
	 *
	 * @return string
	 */
	public static function getMyURL()
	{
		return Session::get('my_url');
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
	 * The implementation for Friendica sadly differs in some points from the one for Hubzilla:
	 * - Hubzilla uses the "zid" parameter, while for Friendica it had been replaced with "zrl"
	 * - There seem to be some reverse authentication (rmagic) that isn't implemented in Friendica at all
	 *
	 * It would be favourable to harmonize the two implementations.
	 *
	 * @param App $a Application instance.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function zrlInit(App $a)
	{
		$my_url = self::getMyURL();
		$my_url = Network::isUrlValid($my_url);

		if (empty($my_url) || local_user()) {
			return;
		}

		$addr = $_GET['addr'] ?? $my_url;

		$arr = ['zrl' => $my_url, 'url' => DI::args()->getCommand()];
		Hook::callAll('zrl_init', $arr);

		// Try to find the public contact entry of the visitor.
		$cid = Contact::getIdForURL($my_url);
		if (!$cid) {
			Logger::log('No contact record found for ' . $my_url, Logger::DEBUG);
			return;
		}

		$contact = DBA::selectFirst('contact',['id', 'url'], ['id' => $cid]);

		if (DBA::isResult($contact) && remote_user() && remote_user() == $contact['id']) {
			Logger::log('The visitor ' . $my_url . ' is already authenticated', Logger::DEBUG);
			return;
		}

		// Avoid endless loops
		$cachekey = 'zrlInit:' . $my_url;
		if (DI::cache()->get($cachekey)) {
			Logger::log('URL ' . $my_url . ' already tried to authenticate.', Logger::DEBUG);
			return;
		} else {
			DI::cache()->set($cachekey, true, Duration::MINUTE);
		}

		Logger::log('Not authenticated. Invoking reverse magic-auth for ' . $my_url, Logger::DEBUG);

		// Remove the "addr" parameter from the destination. It is later added as separate parameter again.
		$addr_request = 'addr=' . urlencode($addr);
		$query = rtrim(str_replace($addr_request, '', DI::args()->getQueryString()), '?&');

		// The other instance needs to know where to redirect.
		$dest = urlencode(DI::baseUrl()->get() . '/' . $query);

		// We need to extract the basebath from the profile url
		// to redirect the visitors '/magic' module.
		$basepath = Contact::getBasepath($contact['url']);

		if ($basepath != DI::baseUrl()->get() && !strstr($dest, '/magic')) {
			$magic_path = $basepath . '/magic' . '?owa=1&dest=' . $dest . '&' . $addr_request;

			// We have to check if the remote server does understand /magic without invoking something
			$serverret = Network::curl($basepath . '/magic');
			if ($serverret->isSuccess()) {
				Logger::log('Doing magic auth for visitor ' . $my_url . ' to ' . $magic_path, Logger::DEBUG);
				System::externalRedirect($magic_path);
			}
		}
	}

	/**
	 * Set the visitor cookies (see remote_user()) for the given handle
	 *
	 * @param string $handle Visitor handle
	 * @return array Visitor contact array
	 */
	public static function addVisitorCookieForHandle($handle)
	{
		$a = DI::app();

		// Try to find the public contact entry of the visitor.
		$cid = Contact::getIdForURL($handle);
		if (!$cid) {
			Logger::log('unable to finger ' . $handle, Logger::DEBUG);
			return [];
		}

		$visitor = DBA::selectFirst('contact', [], ['id' => $cid]);

		// Authenticate the visitor.
		$_SESSION['authenticated'] = 1;
		$_SESSION['visitor_id'] = $visitor['id'];
		$_SESSION['visitor_handle'] = $visitor['addr'];
		$_SESSION['visitor_home'] = $visitor['url'];
		$_SESSION['my_url'] = $visitor['url'];

		Session::setVisitorsContacts();

		$a->contact = $visitor;

		Logger::info('Authenticated visitor', ['url' => $visitor['url']]);

		return $visitor;
	}

	/**
	 * OpenWebAuth authentication.
	 *
	 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/include/zid.php
	 *
	 * @param string $token
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function openWebAuthInit($token)
	{
		$a = DI::app();

		// Clean old OpenWebAuthToken entries.
		OpenWebAuthToken::purge('owt', '3 MINUTE');

		// Check if the token we got is the same one
		// we have stored in the database.
		$visitor_handle = OpenWebAuthToken::getMeta('owt', 0, $token);

		if ($visitor_handle === false) {
			return;
		}

		$visitor = self::addVisitorCookieForHandle($visitor_handle);
		if (empty($visitor)) {
			return;
		}

		$arr = [
			'visitor' => $visitor,
			'url' => DI::args()->getQueryString()
		];
		/**
		 * @hooks magic_auth_success
		 *   Called when a magic-auth was successful.
		 *   * \e array \b visitor
		 *   * \e string \b url
		 */
		Hook::callAll('magic_auth_success', $arr);

		$a->contact = $arr['visitor'];

		info(DI::l10n()->t('OpenWebAuth: %1$s welcomes %2$s', DI::baseUrl()->getHostname(), $visitor['name']));

		Logger::log('OpenWebAuth: auth success from ' . $visitor['addr'], Logger::DEBUG);
	}

	public static function zrl($s, $force = false)
	{
		if (!strlen($s)) {
			return $s;
		}
		if (!strpos($s, '/profile/') && !$force) {
			return $s;
		}
		if ($force && substr($s, -1, 1) !== '/') {
			$s = $s . '/';
		}
		$achar = strpos($s, '?') ? '&' : '?';
		$mine = self::getMyURL();
		if ($mine && !Strings::compareLink($mine, $s)) {
			return $s . $achar . 'zrl=' . urlencode($mine);
		}
		return $s;
	}

	/**
	 * Get the user ID of the page owner.
	 *
	 * Used from within PCSS themes to set theme parameters. If there's a
	 * profile_uid variable set in App, that is the "page owner" and normally their theme
	 * settings take precedence; unless a local user sets the "always_my_theme"
	 * system pconfig, which means they don't want to see anybody else's theme
	 * settings except their own while on this site.
	 *
	 * @return int user ID
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @note Returns local_user instead of user ID if "always_my_theme" is set to true
	 */
	public static function getThemeUid(App $a)
	{
		$uid = !empty($a->profile_uid) ? intval($a->profile_uid) : 0;
		if (local_user() && (DI::pConfig()->get(local_user(), 'system', 'always_my_theme') || !$uid)) {
			return local_user();
		}

		return $uid;
	}

	/**
	 * search for Profiles
	 *
	 * @param int  $start
	 * @param int  $count
	 * @param null $search
	 *
	 * @return array [ 'total' => 123, 'entries' => [...] ];
	 *
	 * @throws \Exception
	 */
	public static function searchProfiles($start = 0, $count = 100, $search = null)
	{
		$publish = (Config::get('system', 'publish_all') ? '' : " AND `publish` = 1 ");
		$total = 0;

		if (!empty($search)) {
			$searchTerm = '%' . $search . '%';
			$cnt = DBA::fetchFirst("SELECT COUNT(*) AS `total`
				FROM `profile`
				LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE `is-default` $publish AND NOT `user`.`blocked` AND NOT `user`.`account_removed`
				AND ((`profile`.`name` LIKE ?) OR
				(`user`.`nickname` LIKE ?) OR
				(`profile`.`pdesc` LIKE ?) OR
				(`profile`.`locality` LIKE ?) OR
				(`profile`.`region` LIKE ?) OR
				(`profile`.`country-name` LIKE ?) OR
				(`profile`.`gender` LIKE ?) OR
				(`profile`.`marital` LIKE ?) OR
				(`profile`.`sexual` LIKE ?) OR
				(`profile`.`about` LIKE ?) OR
				(`profile`.`romance` LIKE ?) OR
				(`profile`.`work` LIKE ?) OR
				(`profile`.`education` LIKE ?) OR
				(`profile`.`pub_keywords` LIKE ?) OR
				(`profile`.`prv_keywords` LIKE ?))",
				$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
				$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
		} else {
			$cnt = DBA::fetchFirst("SELECT COUNT(*) AS `total`
				FROM `profile`
				LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
				WHERE `is-default` $publish AND NOT `user`.`blocked` AND NOT `user`.`account_removed`");
		}

		if (DBA::isResult($cnt)) {
			$total = $cnt['total'];
		}

		$order = " ORDER BY `name` ASC ";
		$profiles = [];

		// If nothing found, don't try to select details
		if ($total > 0) {
			if (!empty($search)) {
				$searchTerm = '%' . $search . '%';

				$profiles = DBA::p("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`, `user`.`timezone` , `user`.`page-flags`,
			`contact`.`addr`, `contact`.`url` AS `profile_url`
			FROM `profile`
			LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
			LEFT JOIN `contact` ON `contact`.`uid` = `user`.`uid`
			WHERE `is-default` $publish AND NOT `user`.`blocked` AND NOT `user`.`account_removed` AND `contact`.`self`
			AND ((`profile`.`name` LIKE ?) OR
				(`user`.`nickname` LIKE ?) OR
				(`profile`.`pdesc` LIKE ?) OR
				(`profile`.`locality` LIKE ?) OR
				(`profile`.`region` LIKE ?) OR
				(`profile`.`country-name` LIKE ?) OR
				(`profile`.`gender` LIKE ?) OR
				(`profile`.`marital` LIKE ?) OR
				(`profile`.`sexual` LIKE ?) OR
				(`profile`.`about` LIKE ?) OR
				(`profile`.`romance` LIKE ?) OR
				(`profile`.`work` LIKE ?) OR
				(`profile`.`education` LIKE ?) OR
				(`profile`.`pub_keywords` LIKE ?) OR
				(`profile`.`prv_keywords` LIKE ?))
			$order LIMIT ?,?",
					$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
					$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
					$start, $count
				);
			} else {
				$profiles = DBA::p("SELECT `profile`.*, `profile`.`uid` AS `profile_uid`, `user`.`nickname`, `user`.`timezone` , `user`.`page-flags`,
			`contact`.`addr`, `contact`.`url` AS `profile_url`
			FROM `profile`
			LEFT JOIN `user` ON `user`.`uid` = `profile`.`uid`
			LEFT JOIN `contact` ON `contact`.`uid` = `user`.`uid`
			WHERE `is-default` $publish AND NOT `user`.`blocked` AND NOT `user`.`account_removed` AND `contact`.`self`
			$order LIMIT ?,?",
					$start, $count
				);
			}
		}

		if (DBA::isResult($profiles) && $total > 0) {
			return [
				'total'   => $total,
				'entries' => DBA::toArray($profiles),
			];

		} else {
			return [
				'total'   => $total,
				'entries' => [],
			];
		}
	}
}

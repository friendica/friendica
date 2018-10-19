<?php
/**
 * @file src/Model/Contact.php
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Profile;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\PortableContact;
use Friendica\Protocol\Salmon;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/text.php';

/**
 * @brief functions for interacting with a contact
 */
class Contact extends BaseObject
{
	/**
	 * @name page/profile types
	 *
	 * PAGE_NORMAL is a typical personal profile account
	 * PAGE_SOAPBOX automatically approves all friend requests as Contact::SHARING, (readonly)
	 * PAGE_COMMUNITY automatically approves all friend requests as Contact::SHARING, but with
	 *      write access to wall and comments (no email and not included in page owner's ACL lists)
	 * PAGE_FREELOVE automatically approves all friend requests as full friends (Contact::FRIEND).
	 *
	 * @{
	 */
	const PAGE_NORMAL    = 0;
	const PAGE_SOAPBOX   = 1;
	const PAGE_COMMUNITY = 2;
	const PAGE_FREELOVE  = 3;
	const PAGE_BLOG      = 4;
	const PAGE_PRVGROUP  = 5;
	/**
	 * @}
	 */

	/**
	 * @name account types
	 *
	 * ACCOUNT_TYPE_PERSON - the account belongs to a person
	 *	Associated page types: PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE
	 *
	 * ACCOUNT_TYPE_ORGANISATION - the account belongs to an organisation
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * ACCOUNT_TYPE_NEWS - the account is a news reflector
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * ACCOUNT_TYPE_COMMUNITY - the account is community forum
	 *	Associated page types: PAGE_COMMUNITY, PAGE_PRVGROUP
	 *
	 * ACCOUNT_TYPE_RELAY - the account is a relay
	 *      This will only be assigned to contacts, not to user accounts
	 * @{
	 */
	const ACCOUNT_TYPE_PERSON =       0;
	const ACCOUNT_TYPE_ORGANISATION = 1;
	const ACCOUNT_TYPE_NEWS =         2;
	const ACCOUNT_TYPE_COMMUNITY =    3;
	const ACCOUNT_TYPE_RELAY =        4;
	/**
	 * @}
	 */

	/**
	 * @name Contact_is
	 *
	 * Relationship types
	 * @{
	 */
	const FOLLOWER = 1;
	const SHARING  = 2;
	const FRIEND   = 3;
	/**
	 * @}
	 */

	/**
	 * @brief Returns the contact id for the user and the public contact id for a given contact id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return array with public and user's contact id
	 */
	private static function getPublicAndUserContacID($cid, $uid)
	{
		if (empty($uid) || empty($cid)) {
			return [];
		}

		$contact = DBA::selectFirst('contact', ['id', 'uid', 'url'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return [];
		}

		// We quit when the user id don't match the user id of the provided contact
		if (($contact['uid'] != $uid) && ($contact['uid'] != 0)) {
			return [];
		}

		if ($contact['uid'] != 0) {
			$pcid = Contact::getIdForURL($contact['url'], 0, true, ['url' => $contact['url']]);
			if (empty($pcid)) {
				return [];
			}
			$ucid = $contact['id'];
		} else {
			$pcid = $contact['id'];
			$ucid = Contact::getIdForURL($contact['url'], $uid, true);
		}

		return ['public' => $pcid, 'user' => $ucid];
	}

	/**
	 * @brief Block contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the contact blocked or unblocked?
	 */
	public static function setBlockedForUser($cid, $uid, $blocked)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['blocked' => $blocked], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['blocked' => $blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * @brief Returns "block" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 */
	public static function isBlockedByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_blocked = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['blocked'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_blocked = $public_contact['blocked'];
			}
		}

		$user_blocked = $public_blocked;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['blocked'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_blocked = $user_contact['blocked'];
			}
		}

		if ($user_blocked != $public_blocked) {
			DBA::update('user-contact', ['blocked' => $user_blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_blocked;
	}

	/**
	 * @brief Ignore contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $ignored Is the contact ignored or unignored?
	 */
	public static function setIgnoredForUser($cid, $uid, $ignored)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['readonly' => $ignored], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['ignored' => $ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * @brief Returns "ignore" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id ignored for the given user?
	 */
	public static function isIgnoredByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_ignored = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['ignored'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_ignored = $public_contact['ignored'];
			}
		}

		$user_ignored = $public_ignored;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['readonly'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_ignored = $user_contact['readonly'];
			}
		}

		if ($user_ignored != $public_ignored) {
			DBA::update('user-contact', ['ignored' => $user_ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_ignored;
	}

	/**
	 * @brief Set "collapsed" for contact id and user id
	 *
	 * @param int     $cid       Either public contact id or user's contact id
	 * @param int     $uid       User ID
	 * @param boolean $collapsed are the contact's posts collapsed or uncollapsed?
	 */
	public static function setCollapsedForUser($cid, $uid, $collapsed)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		DBA::update('user-contact', ['collapsed' => $collapsed], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * @brief Returns "collapsed" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 */
	public static function isCollapsedByUser($cid, $uid)
	{
		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$collapsed = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['collapsed'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$collapsed = $public_contact['collapsed'];
			}
		}

		return $collapsed;
	}

	/**
	 * @brief Returns a list of contacts belonging in a group
	 *
	 * @param int $gid
	 * @return array
	 */
	public static function getByGroupId($gid)
	{
		$return = [];

		if (intval($gid)) {
			$stmt = DBA::p('SELECT `group_member`.`contact-id`, `contact`.*
				FROM `contact`
				INNER JOIN `group_member`
					ON `contact`.`id` = `group_member`.`contact-id`
				WHERE `gid` = ?
				AND `contact`.`uid` = ?
				AND NOT `contact`.`self`
				AND NOT `contact`.`blocked`
				AND NOT `contact`.`pending`
				ORDER BY `contact`.`name` ASC',
				$gid,
				local_user()
			);

			if (DBA::isResult($stmt)) {
				$return = DBA::toArray($stmt);
			}
		}

		return $return;
	}

	/**
	 * @brief Returns the count of OStatus contacts in a group
	 *
	 * @param int $gid
	 * @return int
	 */
	public static function getOStatusCountByGroupId($gid)
	{
		$return = 0;
		if (intval($gid)) {
			$contacts = DBA::fetchFirst('SELECT COUNT(*) AS `count`
				FROM `contact`
				INNER JOIN `group_member`
					ON `contact`.`id` = `group_member`.`contact-id`
				WHERE `gid` = ?
				AND `contact`.`uid` = ?
				AND `contact`.`network` = ?
				AND `contact`.`notify` != ""',
				$gid,
				local_user(),
				Protocol::OSTATUS
			);
			$return = $contacts['count'];
		}

		return $return;
	}

	/**
	 * Creates the self-contact for the provided user id
	 *
	 * @param int $uid
	 * @return bool Operation success
	 */
	public static function createSelfFromUserId($uid)
	{
		// Only create the entry if it doesn't exist yet
		if (DBA::exists('contact', ['uid' => $uid, 'self' => true])) {
			return true;
		}

		$user = DBA::selectFirst('user', ['uid', 'username', 'nickname'], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return false;
		}

		$return = DBA::insert('contact', [
			'uid'         => $user['uid'],
			'created'     => DateTimeFormat::utcNow(),
			'self'        => 1,
			'name'        => $user['username'],
			'nick'        => $user['nickname'],
			'photo'       => System::baseUrl() . '/photo/profile/' . $user['uid'] . '.jpg',
			'thumb'       => System::baseUrl() . '/photo/avatar/'  . $user['uid'] . '.jpg',
			'micro'       => System::baseUrl() . '/photo/micro/'   . $user['uid'] . '.jpg',
			'blocked'     => 0,
			'pending'     => 0,
			'url'         => System::baseUrl() . '/profile/' . $user['nickname'],
			'nurl'        => normalise_link(System::baseUrl() . '/profile/' . $user['nickname']),
			'addr'        => $user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3),
			'request'     => System::baseUrl() . '/dfrn_request/' . $user['nickname'],
			'notify'      => System::baseUrl() . '/dfrn_notify/'  . $user['nickname'],
			'poll'        => System::baseUrl() . '/dfrn_poll/'    . $user['nickname'],
			'confirm'     => System::baseUrl() . '/dfrn_confirm/' . $user['nickname'],
			'poco'        => System::baseUrl() . '/poco/'         . $user['nickname'],
			'name-date'   => DateTimeFormat::utcNow(),
			'uri-date'    => DateTimeFormat::utcNow(),
			'avatar-date' => DateTimeFormat::utcNow(),
			'closeness'   => 0
		]);

		return $return;
	}

	/**
	 * Updates the self-contact for the provided user id
	 *
	 * @param int $uid
	 * @param boolean $update_avatar Force the avatar update
	 */
	public static function updateSelfFromUserID($uid, $update_avatar = false)
	{
		$fields = ['id', 'name', 'nick', 'location', 'about', 'keywords', 'gender', 'avatar',
			'xmpp', 'contact-type', 'forum', 'prv', 'avatar-date', 'nurl'];
		$self = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($self)) {
			return;
		}

		$fields = ['nickname', 'page-flags', 'account-type'];
		$user = DBA::selectFirst('user', $fields, ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			return;
		}

		$fields = ['name', 'photo', 'thumb', 'about', 'address', 'locality', 'region',
			'country-name', 'gender', 'pub_keywords', 'xmpp'];
		$profile = DBA::selectFirst('profile', $fields, ['uid' => $uid, 'is-default' => true]);
		if (!DBA::isResult($profile)) {
			return;
		}

		$fields = ['name' => $profile['name'], 'nick' => $user['nickname'],
			'avatar-date' => $self['avatar-date'], 'location' => Profile::formatLocation($profile),
			'about' => $profile['about'], 'keywords' => $profile['pub_keywords'],
			'gender' => $profile['gender'], 'avatar' => $profile['photo'],
			'contact-type' => $user['account-type'], 'xmpp' => $profile['xmpp']];

		$avatar = DBA::selectFirst('photo', ['resource-id', 'type'], ['uid' => $uid, 'profile' => true]);
		if (DBA::isResult($avatar)) {
			if ($update_avatar) {
				$fields['avatar-date'] = DateTimeFormat::utcNow();
			}

			// Creating the path to the avatar, beginning with the file suffix
			$types = Image::supportedTypes();
			if (isset($types[$avatar['type']])) {
				$file_suffix = $types[$avatar['type']];
			} else {
				$file_suffix = 'jpg';
			}

			// We are adding a timestamp value so that other systems won't use cached content
			$timestamp = strtotime($fields['avatar-date']);

			$prefix = System::baseUrl() . '/photo/' .$avatar['resource-id'] . '-';
			$suffix = '.' . $file_suffix . '?ts=' . $timestamp;

			$fields['photo'] = $prefix . '4' . $suffix;
			$fields['thumb'] = $prefix . '5' . $suffix;
			$fields['micro'] = $prefix . '6' . $suffix;
		} else {
			// We hadn't found a photo entry, so we use the default avatar
			$fields['photo'] = System::baseUrl() . '/images/person-175.jpg';
			$fields['thumb'] = System::baseUrl() . '/images/person-80.jpg';
			$fields['micro'] = System::baseUrl() . '/images/person-48.jpg';
		}

		$fields['forum'] = $user['page-flags'] == self::PAGE_COMMUNITY;
		$fields['prv'] = $user['page-flags'] == self::PAGE_PRVGROUP;

		// it seems as if ported accounts can have wrong values, so we make sure that now everything is fine.
		$fields['url'] = System::baseUrl() . '/profile/' . $user['nickname'];
		$fields['nurl'] = normalise_link($fields['url']);
		$fields['addr'] = $user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);
		$fields['request'] = System::baseUrl() . '/dfrn_request/' . $user['nickname'];
		$fields['notify'] = System::baseUrl() . '/dfrn_notify/'  . $user['nickname'];
		$fields['poll'] = System::baseUrl() . '/dfrn_poll/'    . $user['nickname'];
		$fields['confirm'] = System::baseUrl() . '/dfrn_confirm/' . $user['nickname'];
		$fields['poco'] = System::baseUrl() . '/poco/'         . $user['nickname'];

		$update = false;

		foreach ($fields as $field => $content) {
			if (isset($self[$field]) && $self[$field] != $content) {
				$update = true;
			}
		}

		if ($update) {
			$fields['name-date'] = DateTimeFormat::utcNow();
			DBA::update('contact', $fields, ['id' => $self['id']]);

			// Update the public contact as well
			DBA::update('contact', $fields, ['uid' => 0, 'nurl' => $self['nurl']]);

			// Update the profile
			$fields = ['photo' => System::baseUrl() . '/photo/profile/' .$uid . '.jpg',
				'thumb' => System::baseUrl() . '/photo/avatar/' . $uid .'.jpg'];
			DBA::update('profile', $fields, ['uid' => $uid, 'is-default' => true]);
		}
	}

	/**
	 * @brief Marks a contact for removal
	 *
	 * @param int $id contact id
	 * @return null
	 */
	public static function remove($id)
	{
		// We want just to make sure that we don't delete our "self" contact
		$contact = DBA::selectFirst('contact', ['uid'], ['id' => $id, 'self' => false]);
		if (!DBA::isResult($contact) || !intval($contact['uid'])) {
			return;
		}

		// Archive the contact
		DBA::update('contact', ['archive' => true, 'network' => Protocol::PHANTOM, 'deleted' => true], ['id' => $id]);

		// Delete it in the background
		Worker::add(PRIORITY_LOW, 'RemoveContact', $id);
	}

	/**
	 * @brief Sends an unfriend message. Does not remove the contact
	 *
	 * @param array   $user     User unfriending
	 * @param array   $contact  Contact unfriended
	 * @param boolean $dissolve Remove the contact on the remote side
	 * @return void
	 */
	public static function terminateFriendship(array $user, array $contact, $dissolve = false)
	{
		if (($contact['network'] == Protocol::DFRN) && $dissolve) {
			DFRN::deliver($user, $contact, 'placeholder', true);
		} elseif (in_array($contact['network'], [Protocol::OSTATUS, Protocol::DFRN])) {
			// create an unfollow slap
			$item = [];
			$item['verb'] = NAMESPACE_OSTATUS . "/unfollow";
			$item['follow'] = $contact["url"];
			$item['body'] = '';
			$item['title'] = '';
			$item['guid'] = '';
			$item['tag'] = '';
			$item['attach'] = '';
			$slap = OStatus::salmon($item, $user);

			if (!empty($contact['notify'])) {
				Salmon::slapper($user, $contact['notify'], $slap);
			}
		} elseif ($contact['network'] == Protocol::DIASPORA) {
			Diaspora::sendUnshare($user, $contact);
		} elseif ($contact['network'] == Protocol::ACTIVITYPUB) {
			ActivityPub\Transmitter::sendContactUndo($contact['url'], $user['uid']);

			if ($dissolve) {
				ActivityPub\Transmitter::sendContactReject($contact['url'], $contact['hub-verify'], $user['uid']);
			}
		}
	}

	/**
	 * @brief Marks a contact for archival after a communication issue delay
	 *
	 * Contact has refused to recognise us as a friend. We will start a countdown.
	 * If they still don't recognise us in 32 days, the relationship is over,
	 * and we won't waste any more time trying to communicate with them.
	 * This provides for the possibility that their database is temporarily messed
	 * up or some other transient event and that there's a possibility we could recover from it.
	 *
	 * @param array $contact contact to mark for archival
	 * @return null
	 */
	public static function markForArchival(array $contact)
	{
		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'archive', 'self', 'term-date'];
			$contact = DBA::selectFirst('contact', [], ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		} elseif (!isset($contact['url'])) {
			logger('Empty contact: ' . json_encode($contact) . ' - ' . System::callstack(20), LOGGER_DEBUG);
		}

		// Contact already archived or "self" contact? => nothing to do
		if ($contact['archive'] || $contact['self']) {
			return;
		}

		if ($contact['term-date'] <= NULL_DATE) {
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['`nurl` = ? AND `term-date` <= ? AND NOT `self`', normalise_link($contact['url']), NULL_DATE]);
		} else {
			/* @todo
			 * We really should send a notification to the owner after 2-3 weeks
			 * so they won't be surprised when the contact vanishes and can take
			 * remedial action if this was a serious mistake or glitch
			 */

			/// @todo Check for contact vitality via probing
			$archival_days = Config::get('system', 'archival_days', 32);

			$expiry = $contact['term-date'] . ' + ' . $archival_days . ' days ';
			if (DateTimeFormat::utcNow() > DateTimeFormat::utc($expiry)) {
				/* Relationship is really truly dead. archive them rather than
				 * delete, though if the owner tries to unarchive them we'll start
				 * the whole process over again.
				 */
				DBA::update('contact', ['archive' => 1], ['id' => $contact['id']]);
				DBA::update('contact', ['archive' => 1], ['nurl' => normalise_link($contact['url']), 'self' => false]);
			}
		}
	}

	/**
	 * @brief Cancels the archival countdown
	 *
	 * @see Contact::markForArchival()
	 *
	 * @param array $contact contact to be unmarked for archival
	 * @return null
	 */
	public static function unmarkForArchival(array $contact)
	{
		$condition = ['`id` = ? AND (`term-date` > ? OR `archive`)', $contact['id'], NULL_DATE];
		$exists = DBA::exists('contact', $condition);

		// We don't need to update, we never marked this contact for archival
		if (!$exists) {
			return;
		}

		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'batch'];
			$contact = DBA::selectFirst('contact', [], ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		}

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = ['term-date' => NULL_DATE, 'archive' => false];
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['nurl' => normalise_link($contact['url'])]);

		if (!empty($contact['batch'])) {
			$condition = ['batch' => $contact['batch'], 'contact-type' => self::ACCOUNT_TYPE_RELAY];
			DBA::update('contact', $fields, $condition);
		}
	}

	/**
	 * @brief Get contact data for a given profile link
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 * It caches its result for the same script execution to prevent duplicate calls
	 *
	 * @param string $url     The profile link
	 * @param int    $uid     User id
	 * @param array  $default If not data was found take this data as default value
	 *
	 * @return array Contact data
	 */
	public static function getDetailsByURL($url, $uid = -1, array $default = [])
	{
		static $cache = [];

		if ($url == '') {
			return $default;
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		if (isset($cache[$url][$uid])) {
			return $cache[$url][$uid];
		}

		$ssl_url = str_replace('http://', 'https://', $url);

		// Fetch contact data from the contact table for the given user
		$s = DBA::p("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`
		FROM `contact` WHERE `nurl` = ? AND `uid` = ?", normalise_link($url), $uid);
		$r = DBA::toArray($s);

		// Fetch contact data from the contact table for the given user, checking with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
				`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = ?", normalise_link($url), $url, $ssl_url, $uid);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
			FROM `contact` WHERE `nurl` = ? AND `uid` = 0", normalise_link($url));
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically) - checked with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = 0", normalise_link($url), $url, $ssl_url);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the gcontact table
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
			`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, 0 AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`
			FROM `gcontact` WHERE `nurl` = ?", normalise_link($url));
			$r = DBA::toArray($s);
		}

		if (DBA::isResult($r)) {
			// If there is more than one entry we filter out the connector networks
			if (count($r) > 1) {
				foreach ($r as $id => $result) {
					if ($result["network"] == Protocol::STATUSNET) {
						unset($r[$id]);
					}
				}
			}

			$profile = array_shift($r);

			// "bd" always contains the upcoming birthday of a contact.
			// "birthday" might contain the birthday including the year of birth.
			if ($profile["birthday"] > '0001-01-01') {
				$bd_timestamp = strtotime($profile["birthday"]);
				$month = date("m", $bd_timestamp);
				$day = date("d", $bd_timestamp);

				$current_timestamp = time();
				$current_year = date("Y", $current_timestamp);
				$current_month = date("m", $current_timestamp);
				$current_day = date("d", $current_timestamp);

				$profile["bd"] = $current_year . "-" . $month . "-" . $day;
				$current = $current_year . "-" . $current_month . "-" . $current_day;

				if ($profile["bd"] < $current) {
					$profile["bd"] = ( ++$current_year) . "-" . $month . "-" . $day;
				}
			} else {
				$profile["bd"] = '0001-01-01';
			}
		} else {
			$profile = $default;
		}

		if (empty($profile["photo"]) && isset($default["photo"])) {
			$profile["photo"] = $default["photo"];
		}

		if (empty($profile["name"]) && isset($default["name"])) {
			$profile["name"] = $default["name"];
		}

		if (empty($profile["network"]) && isset($default["network"])) {
			$profile["network"] = $default["network"];
		}

		if (empty($profile["thumb"]) && isset($profile["photo"])) {
			$profile["thumb"] = $profile["photo"];
		}

		if (empty($profile["micro"]) && isset($profile["thumb"])) {
			$profile["micro"] = $profile["thumb"];
		}

		if ((empty($profile["addr"]) || empty($profile["name"])) && (defaults($profile, "gid", 0) != 0)
			&& in_array($profile["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])
		) {
			Worker::add(PRIORITY_LOW, "UpdateGContact", $profile["gid"]);
		}

		// Show contact details of Diaspora contacts only if connected
		if ((defaults($profile, "cid", 0) == 0) && (defaults($profile, "network", "") == Protocol::DIASPORA)) {
			$profile["location"] = "";
			$profile["about"] = "";
			$profile["gender"] = "";
			$profile["birthday"] = '0001-01-01';
		}

		$cache[$url][$uid] = $profile;

		return $profile;
	}

	/**
	 * @brief Get contact data for a given address
	 *
	 * The function looks at several places (contact table and gcontact table) for the contact
	 *
	 * @param string $addr The profile link
	 * @param int    $uid  User id
	 *
	 * @return array Contact data
	 */
	public static function getDetailsByAddr($addr, $uid = -1)
	{
		static $cache = [];

		if ($addr == '') {
			return [];
		}

		if ($uid == -1) {
			$uid = local_user();
		}

		// Fetch contact data from the contact table for the given user
		$r = q("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`
			FROM `contact` WHERE `addr` = '%s' AND `uid` = %d AND NOT `deleted`",
			DBA::escape($addr),
			intval($uid)
		);
		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBA::isResult($r)) {
			$r = q("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
				`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
				FROM `contact` WHERE `addr` = '%s' AND `uid` = 0 AND NOT `deleted`",
				DBA::escape($addr)
			);
		}

		// Fetch the data from the gcontact table
		if (!DBA::isResult($r)) {
			$r = q("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
				`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, `community` AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`
				FROM `gcontact` WHERE `addr` = '%s'",
				DBA::escape($addr)
			);
		}

		if (!DBA::isResult($r)) {
			$data = Probe::uri($addr);

			$profile = self::getDetailsByURL($data['url'], $uid);
		} else {
			$profile = $r[0];
		}

		return $profile;
	}

	/**
	 * @brief Returns the data array for the photo menu of a given contact
	 *
	 * @param array $contact contact
	 * @param int   $uid     optional, default 0
	 * @return array
	 */
	public static function photoMenu(array $contact, $uid = 0)
	{
		// @todo Unused, to be removed
		$a = get_app();

		$contact_url = '';
		$pm_url = '';
		$status_link = '';
		$photos_link = '';
		$posts_link = '';
		$contact_drop_link = '';
		$poke_link = '';

		if ($uid == 0) {
			$uid = local_user();
		}

		if (empty($contact['uid']) || ($contact['uid'] != $uid)) {
			if ($uid == 0) {
				$profile_link = self::magicLink($contact['url']);
				$menu = ['profile' => [L10n::t('View Profile'), $profile_link, true]];

				return $menu;
			}

			// Look for our own contact if the uid doesn't match and isn't public
			$contact_own = DBA::selectFirst('contact', [], ['nurl' => $contact['nurl'], 'network' => $contact['network'], 'uid' => $uid]);
			if (DBA::isResult($contact_own)) {
				return self::photoMenu($contact_own, $uid);
			}
		}

		$sparkle = false;
		if (($contact['network'] === Protocol::DFRN) && !$contact['self']) {
			$sparkle = true;
			$profile_link = System::baseUrl() . '/redir/' . $contact['id'];
		} else {
			$profile_link = $contact['url'];
		}

		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if ($sparkle) {
			$status_link = $profile_link . '?url=status';
			$photos_link = $profile_link . '?url=photos';
			$profile_link = $profile_link . '?url=profile';
		}

		if (in_array($contact['network'], [Protocol::DFRN, Protocol::DIASPORA]) && !$contact['self']) {
			$pm_url = System::baseUrl() . '/message/new/' . $contact['id'];
		}

		if (($contact['network'] == Protocol::DFRN) && !$contact['self']) {
			$poke_link = System::baseUrl() . '/poke/?f=&c=' . $contact['id'];
		}

		$contact_url = System::baseUrl() . '/contact/' . $contact['id'];

		$posts_link = System::baseUrl() . '/contact/' . $contact['id'] . '/conversations';

		if (!$contact['self']) {
			$contact_drop_link = System::baseUrl() . '/contact/' . $contact['id'] . '/drop?confirm=1';
		}

		/**
		 * Menu array:
		 * "name" => [ "Label", "link", (bool)Should the link opened in a new tab? ]
		 */
		if (empty($contact['uid'])) {
			$connlnk = 'follow/?url=' . $contact['url'];
			$menu = [
				'profile' => [L10n::t('View Profile'),   $profile_link, true],
				'network' => [L10n::t('Network Posts'),  $posts_link,   false],
				'edit'    => [L10n::t('View Contact'),   $contact_url,  false],
				'follow'  => [L10n::t('Connect/Follow'), $connlnk,      true],
			];
		} else {
			$menu = [
				'status'  => [L10n::t('View Status'),   $status_link,       true],
				'profile' => [L10n::t('View Profile'),  $profile_link,      true],
				'photos'  => [L10n::t('View Photos'),   $photos_link,       true],
				'network' => [L10n::t('Network Posts'), $posts_link,        false],
				'edit'    => [L10n::t('View Contact'),  $contact_url,       false],
				'drop'    => [L10n::t('Drop Contact'),  $contact_drop_link, false],
				'pm'      => [L10n::t('Send PM'),       $pm_url,            false],
				'poke'    => [L10n::t('Poke'),          $poke_link,         false],
			];
		}

		$args = ['contact' => $contact, 'menu' => &$menu];

		Addon::callHooks('contact_photo_menu', $args);

		$menucondensed = [];

		foreach ($menu as $menuname => $menuitem) {
			if ($menuitem[1] != '') {
				$menucondensed[$menuname] = $menuitem;
			}
		}

		return $menucondensed;
	}

	/**
	 * @brief Returns ungrouped contact count or list for user
	 *
	 * Returns either the total number of ungrouped contacts for the given user
	 * id or a paginated list of ungrouped contacts.
	 *
	 * @param int $uid   uid
	 * @param int $start optional, default 0
	 * @param int $count optional, default 0
	 *
	 * @return array
	 */
	public static function getUngroupedList($uid)
	{
		return q("SELECT *
			   FROM `contact`
			   WHERE `uid` = %d
			   AND NOT `self`
			   AND NOT `blocked`
			   AND NOT `pending`
			   AND `id` NOT IN (
			   	SELECT DISTINCT(`contact-id`)
			   	FROM `group_member`
			   	INNER JOIN `group` ON `group`.`id` = `group_member`.`gid`
			   	WHERE `group`.`uid` = %d
			   )", intval($uid), intval($uid));
	}

	/**
	 * @brief Fetch the contact id for a given URL and user
	 *
	 * First lookup in the contact table to find a record matching either `url`, `nurl`,
	 * `addr` or `alias`.
	 *
	 * If there's no record and we aren't looking for a public contact, we quit.
	 * If there's one, we check that it isn't time to update the picture else we
	 * directly return the found contact id.
	 *
	 * Second, we probe the provided $url whether it's http://server.tld/profile or
	 * nick@server.tld. We quit if we can't get any info back.
	 *
	 * Third, we create the contact record if it doesn't exist
	 *
	 * Fourth, we update the existing record with the new data (avatar, alias, nick)
	 * if there's any updates
	 *
	 * @param string  $url       Contact URL
	 * @param integer $uid       The user id for the contact (0 = public contact)
	 * @param boolean $no_update Don't update the contact
	 * @param array   $default   Default value for creating the contact when every else fails
	 * @param boolean $in_loop   Internally used variable to prevent an endless loop
	 *
	 * @return integer Contact ID
	 */
	public static function getIdForURL($url, $uid = 0, $no_update = false, $default = [], $in_loop = false)
	{
		logger("Get contact data for url " . $url . " and user " . $uid . " - " . System::callstack(), LOGGER_DEBUG);

		$contact_id = 0;

		if ($url == '') {
			return 0;
		}

		/// @todo Verify if we can't use Contact::getDetailsByUrl instead of the following
		// We first try the nurl (http://server.tld/nick), most common case
		$contact = DBA::selectFirst('contact', ['id', 'avatar', 'avatar-date'], ['nurl' => normalise_link($url), 'uid' => $uid, 'deleted' => false]);

		// Then the addr (nick@server.tld)
		if (!DBA::isResult($contact)) {
			$contact = DBA::selectFirst('contact', ['id', 'avatar', 'avatar-date'], ['addr' => $url, 'uid' => $uid, 'deleted' => false]);
		}

		// Then the alias (which could be anything)
		if (!DBA::isResult($contact)) {
			// The link could be provided as http although we stored it as https
			$ssl_url = str_replace('http://', 'https://', $url);
			$condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $url, normalise_link($url), $ssl_url, $uid];
			$contact = DBA::selectFirst('contact', ['id', 'avatar', 'avatar-date'], $condition);
		}

		if (DBA::isResult($contact)) {
			$contact_id = $contact["id"];

			// Update the contact every 7 days
			$update_contact = ($contact['avatar-date'] < DateTimeFormat::utc('now -7 days'));

			// We force the update if the avatar is empty
			if (!x($contact, 'avatar')) {
				$update_contact = true;
			}
			if (!$update_contact || $no_update) {
				return $contact_id;
			}
		} elseif ($uid != 0) {
			// Non-existing user-specific contact, exiting
			return 0;
		}

		// When we don't want to update, we look if some of our users already know this contact
		if ($no_update) {
			$fields = ['url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
				'photo', 'keywords', 'location', 'about', 'network',
				'priority', 'batch', 'request', 'confirm', 'poco'];
			$data = DBA::selectFirst('contact', $fields, ['nurl' => normalise_link($url)]);

			if (DBA::isResult($data)) {
				// For security reasons we don't fetch key data from our users
				$data["pubkey"] = '';
			}
		} else {
			$data = [];
		}

		if (empty($data)) {
			$data = Probe::uri($url, "", $uid);

			// Ensure that there is a gserver entry
			if (!empty($data['baseurl']) && ($data['network'] != Protocol::PHANTOM)) {
				PortableContact::checkServer($data['baseurl']);
			}
		}

		// Last try in gcontact for unsupported networks
		if (!in_array($data["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::PUMPIO, Protocol::MAIL, Protocol::FEED])) {
			if ($uid != 0) {
				return 0;
			}

			// Get data from the gcontact table
			$fields = ['name', 'nick', 'url', 'photo', 'addr', 'alias', 'network'];
			$contact = DBA::selectFirst('gcontact', $fields, ['nurl' => normalise_link($url)]);
			if (!DBA::isResult($contact)) {
				$contact = DBA::selectFirst('contact', $fields, ['nurl' => normalise_link($url)]);
			}

			if (!DBA::isResult($contact)) {
				$fields = ['url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
					'photo', 'keywords', 'location', 'about', 'network',
					'priority', 'batch', 'request', 'confirm', 'poco'];
				$contact = DBA::selectFirst('contact', $fields, ['addr' => $url]);
			}

			if (!DBA::isResult($contact)) {
				// The link could be provided as http although we stored it as https
				$ssl_url = str_replace('http://', 'https://', $url);
				$condition = ['alias' => [$url, normalise_link($url), $ssl_url]];
				$contact = DBA::selectFirst('contact', $fields, $condition);
			}

			if (!DBA::isResult($contact)) {
				$fields = ['url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
					'photo', 'network', 'priority', 'batch', 'request', 'confirm'];
				$condition = ['url' => [$url, normalise_link($url), $ssl_url]];
				$contact = DBA::selectFirst('fcontact', $fields, $condition);
			}

			if (!empty($default)) {
				$contact = $default;
			}

			if (!DBA::isResult($contact)) {
				return 0;
			} else {
				$data = array_merge($data, $contact);
			}
		}

		if (!$contact_id && ($data["alias"] != '') && ($data["alias"] != $url) && !$in_loop) {
			$contact_id = self::getIdForURL($data["alias"], $uid, true, $default, true);
		}

		$url = $data["url"];
		if (!$contact_id) {
			DBA::insert('contact', [
				'uid'       => $uid,
				'created'   => DateTimeFormat::utcNow(),
				'url'       => $data["url"],
				'nurl'      => normalise_link($data["url"]),
				'addr'      => $data["addr"],
				'alias'     => $data["alias"],
				'notify'    => $data["notify"],
				'poll'      => $data["poll"],
				'name'      => $data["name"],
				'nick'      => $data["nick"],
				'photo'     => $data["photo"],
				'keywords'  => $data["keywords"],
				'location'  => $data["location"],
				'about'     => $data["about"],
				'network'   => $data["network"],
				'pubkey'    => $data["pubkey"],
				'rel'       => self::SHARING,
				'priority'  => $data["priority"],
				'batch'     => $data["batch"],
				'request'   => $data["request"],
				'confirm'   => $data["confirm"],
				'poco'      => $data["poco"],
				'name-date' => DateTimeFormat::utcNow(),
				'uri-date'  => DateTimeFormat::utcNow(),
				'avatar-date' => DateTimeFormat::utcNow(),
				'writable'  => 1,
				'blocked'   => 0,
				'readonly'  => 0,
				'pending'   => 0]
			);

			$s = DBA::select('contact', ['id'], ['nurl' => normalise_link($data["url"]), 'uid' => $uid], ['order' => ['id'], 'limit' => 2]);
			$contacts = DBA::toArray($s);
			if (!DBA::isResult($contacts)) {
				return 0;
			}

			$contact_id = $contacts[0]["id"];

			// Update the newly created contact from data in the gcontact table
			$gcontact = DBA::selectFirst('gcontact', ['location', 'about', 'keywords', 'gender'], ['nurl' => normalise_link($data["url"])]);
			if (DBA::isResult($gcontact)) {
				// Only use the information when the probing hadn't fetched these values
				if ($data['keywords'] != '') {
					unset($gcontact['keywords']);
				}
				if ($data['location'] != '') {
					unset($gcontact['location']);
				}
				if ($data['about'] != '') {
					unset($gcontact['about']);
				}
				DBA::update('contact', $gcontact, ['id' => $contact_id]);
			}

			if (count($contacts) > 1 && $uid == 0 && $contact_id != 0 && $data["url"] != "") {
				DBA::delete('contact', ["`nurl` = ? AND `uid` = 0 AND `id` != ? AND NOT `self`",
					normalise_link($data["url"]), $contact_id]);
			}
		}

		self::updateAvatar($data["photo"], $uid, $contact_id);

		$fields = ['url', 'nurl', 'addr', 'alias', 'name', 'nick', 'keywords', 'location', 'about', 'avatar-date', 'pubkey'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $contact_id]);

		// This condition should always be true
		if (!DBA::isResult($contact)) {
			return $contact_id;
		}

		$updated = ['addr' => $data['addr'],
			'alias' => $data['alias'],
			'url' => $data['url'],
			'nurl' => normalise_link($data['url']),
			'name' => $data['name'],
			'nick' => $data['nick']];

		if ($data['keywords'] != '') {
			$updated['keywords'] = $data['keywords'];
		}
		if ($data['location'] != '') {
			$updated['location'] = $data['location'];
		}

		// Update the technical stuff as well - if filled
		if ($data['notify'] != '') {
			$updated['notify'] = $data['notify'];
		}
		if ($data['poll'] != '') {
			$updated['poll'] = $data['poll'];
		}
		if ($data['batch'] != '') {
			$updated['batch'] = $data['batch'];
		}
		if ($data['request'] != '') {
			$updated['request'] = $data['request'];
		}
		if ($data['confirm'] != '') {
			$updated['confirm'] = $data['confirm'];
		}
		if ($data['poco'] != '') {
			$updated['poco'] = $data['poco'];
		}

		// Only fill the pubkey if it had been empty before. We have to prevent identity theft.
		if (empty($contact['pubkey'])) {
			$updated['pubkey'] = $data['pubkey'];
		}

		if (($data["addr"] != $contact["addr"]) || ($data["alias"] != $contact["alias"])) {
			$updated['uri-date'] = DateTimeFormat::utcNow();
		}
		if (($data["name"] != $contact["name"]) || ($data["nick"] != $contact["nick"])) {
			$updated['name-date'] = DateTimeFormat::utcNow();
		}

		$updated['avatar-date'] = DateTimeFormat::utcNow();

		DBA::update('contact', $updated, ['id' => $contact_id], $contact);

		return $contact_id;
	}

	/**
	 * @brief Checks if the contact is blocked
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact blocked?
	 */
	public static function isBlocked($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$blocked = DBA::selectFirst('contact', ['blocked'], ['id' => $cid]);
		if (!DBA::isResult($blocked)) {
			return false;
		}
		return (bool) $blocked['blocked'];
	}

	/**
	 * @brief Checks if the contact is hidden
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact hidden?
	 */
	public static function isHidden($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$hidden = DBA::selectFirst('contact', ['hidden'], ['id' => $cid]);
		if (!DBA::isResult($hidden)) {
			return false;
		}
		return (bool) $hidden['hidden'];
	}

	/**
	 * @brief Returns posts from a given contact url
	 *
	 * @param string $contact_url Contact URL
	 *
	 * @return string posts in HTML
	 */
	public static function getPostsFromUrl($contact_url, $thread_mode = false, $update = 0)
	{
		$a = self::getApp();

		require_once 'include/conversation.php';

		$cid = Self::getIdForURL($contact_url);

		$contact = DBA::selectFirst('contact', ['contact-type', 'network'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (in_array($contact["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, ""])) {
			$sql = "(`item`.`uid` = 0 OR (`item`.`uid` = ? AND NOT `item`.`global`))";
		} else {
			$sql = "`item`.`uid` = ?";
		}

		$contact_field = ($contact["contact-type"] == self::ACCOUNT_TYPE_COMMUNITY ? 'owner-id' : 'author-id');

		if ($thread_mode) {
			$condition = ["`$contact_field` = ? AND `gravity` = ? AND " . $sql,
				$cid, GRAVITY_PARENT, local_user()];
		} else {
			$condition = ["`$contact_field` = ? AND `gravity` IN (?, ?) AND " . $sql,
				$cid, GRAVITY_PARENT, GRAVITY_COMMENT, local_user()];
		}

		$params = ['order' => ['created' => true],
			'limit' => [$a->pager['start'], $a->pager['itemspage']]];

		if ($thread_mode) {
			$r = Item::selectThreadForUser(local_user(), ['uri'], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, 'contacts', $update);
		} else {
			$r = Item::selectForUser(local_user(), [], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, 'contact-posts', false);
		}

		if (!$update) {
			$o .= alt_pager($a, count($items));
		}

		return $o;
	}

	/**
	 * @brief Returns the account type name
	 *
	 * The function can be called with either the user or the contact array
	 *
	 * @param array $contact contact or user array
	 * @return string
	 */
	public static function getAccountType(array $contact)
	{
		// There are several fields that indicate that the contact or user is a forum
		// "page-flags" is a field in the user table,
		// "forum" and "prv" are used in the contact table. They stand for self::PAGE_COMMUNITY and self::PAGE_PRVGROUP.
		// "community" is used in the gcontact table and is true if the contact is self::PAGE_COMMUNITY or self::PAGE_PRVGROUP.
		if ((isset($contact['page-flags']) && (intval($contact['page-flags']) == self::PAGE_COMMUNITY))
			|| (isset($contact['page-flags']) && (intval($contact['page-flags']) == self::PAGE_PRVGROUP))
			|| (isset($contact['forum']) && intval($contact['forum']))
			|| (isset($contact['prv']) && intval($contact['prv']))
			|| (isset($contact['community']) && intval($contact['community']))
		) {
			$type = self::ACCOUNT_TYPE_COMMUNITY;
		} else {
			$type = self::ACCOUNT_TYPE_PERSON;
		}

		// The "contact-type" (contact table) and "account-type" (user table) are more general then the chaos from above.
		if (isset($contact["contact-type"])) {
			$type = $contact["contact-type"];
		}

		if (isset($contact["account-type"])) {
			$type = $contact["account-type"];
		}

		switch ($type) {
			case self::ACCOUNT_TYPE_ORGANISATION:
				$account_type = L10n::t("Organisation");
				break;

			case self::ACCOUNT_TYPE_NEWS:
				$account_type = L10n::t('News');
				break;

			case self::ACCOUNT_TYPE_COMMUNITY:
				$account_type = L10n::t("Forum");
				break;

			default:
				$account_type = "";
				break;
		}

		return $account_type;
	}

	/**
	 * @brief Blocks a contact
	 *
	 * @param int $uid
	 * @return bool
	 */
	public static function block($uid)
	{
		$return = DBA::update('contact', ['blocked' => true], ['id' => $uid]);

		return $return;
	}

	/**
	 * @brief Unblocks a contact
	 *
	 * @param int $uid
	 * @return bool
	 */
	public static function unblock($uid)
	{
		$return = DBA::update('contact', ['blocked' => false], ['id' => $uid]);

		return $return;
	}

	/**
	 * @brief Updates the avatar links in a contact only if needed
	 *
	 * @param string $avatar Link to avatar picture
	 * @param int    $uid    User id of contact owner
	 * @param int    $cid    Contact id
	 * @param bool   $force  force picture update
	 *
	 * @return array Returns array of the different avatar sizes
	 */
	public static function updateAvatar($avatar, $uid, $cid, $force = false)
	{
		$contact = DBA::selectFirst('contact', ['avatar', 'photo', 'thumb', 'micro', 'nurl'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return false;
		} else {
			$data = [$contact["photo"], $contact["thumb"], $contact["micro"]];
		}

		if (($contact["avatar"] != $avatar) || $force) {
			$photos = Photo::importProfilePhoto($avatar, $uid, $cid, true);

			if ($photos) {
				DBA::update(
					'contact',
					['avatar' => $avatar, 'photo' => $photos[0], 'thumb' => $photos[1], 'micro' => $photos[2], 'avatar-date' => DateTimeFormat::utcNow()],
					['id' => $cid]
				);

				// Update the public contact (contact id = 0)
				if ($uid != 0) {
					$pcontact = DBA::selectFirst('contact', ['id'], ['nurl' => $contact['nurl'], 'uid' => 0]);
					if (DBA::isResult($pcontact)) {
						self::updateAvatar($avatar, 0, $pcontact['id'], $force);
					}
				}

				return $photos;
			}
		}

		return $data;
	}

	/**
	 * @param integer $id      contact id
	 * @param string  $network Optional network we are probing for
	 * @return boolean
	 */
	public static function updateFromProbe($id, $network = '')
	{
		/*
		  Warning: Never ever fetch the public key via Probe::uri and write it into the contacts.
		  This will reliably kill your communication with Friendica contacts.
		 */

		$fields = ['url', 'nurl', 'addr', 'alias', 'batch', 'notify', 'poll', 'poco', 'network'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$ret = Probe::uri($contact["url"], $network);

		// If Probe::uri fails the network code will be different
		if (($ret["network"] != $contact["network"]) && !in_array($ret["network"], [Protocol::ACTIVITYPUB, $network])) {
			return false;
		}

		$update = false;

		// make sure to not overwrite existing values with blank entries
		foreach ($ret as $key => $val) {
			if (isset($contact[$key]) && ($contact[$key] != "") && ($val == "")) {
				$ret[$key] = $contact[$key];
			}

			if (isset($contact[$key]) && ($ret[$key] != $contact[$key])) {
				$update = true;
			}
		}

		if (!$update) {
			return true;
		}

		DBA::update(
			'contact', [
				'url'     => $ret['url'],
				'nurl'    => normalise_link($ret['url']),
				'network' => $ret['network'],
				'addr'    => $ret['addr'],
				'alias'   => $ret['alias'],
				'batch'   => $ret['batch'],
				'notify'  => $ret['notify'],
				'poll'    => $ret['poll'],
				'poco'    => $ret['poco']
			],
			['id' => $id]
		);

		// Update the corresponding gcontact entry
		PortableContact::lastUpdated($ret["url"]);

		return true;
	}

	/**
	 * Takes a $uid and a url/handle and adds a new contact
	 * Currently if the contact is DFRN, interactive needs to be true, to redirect to the
	 * dfrn_request page.
	 *
	 * Otherwise this can be used to bulk add StatusNet contacts, Twitter contacts, etc.
	 *
	 * Returns an array
	 * $return['success'] boolean true if successful
	 * $return['message'] error text if success is false.
	 *
	 * @brief Takes a $uid and a url/handle and adds a new contact
	 * @param int    $uid
	 * @param string $url
	 * @param bool   $interactive
	 * @param string $network
	 * @return boolean|string
	 */
	public static function createFromProbe($uid, $url, $interactive = false, $network = '')
	{
		$result = ['cid' => -1, 'success' => false, 'message' => ''];

		$a = get_app();

		// remove ajax junk, e.g. Twitter
		$url = str_replace('/#!/', '/', $url);

		if (!Network::isUrlAllowed($url)) {
			$result['message'] = L10n::t('Disallowed profile URL.');
			return $result;
		}

		if (Network::isUrlBlocked($url)) {
			$result['message'] = L10n::t('Blocked domain');
			return $result;
		}

		if (!$url) {
			$result['message'] = L10n::t('Connect URL missing.');
			return $result;
		}

		$arr = ['url' => $url, 'contact' => []];

		Addon::callHooks('follow', $arr);

		if (empty($arr)) {
			$result['message'] = L10n::t('The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.');
			return $result;
		}

		if (x($arr['contact'], 'name')) {
			$ret = $arr['contact'];
		} else {
			$ret = Probe::uri($url, $network, $uid, false);
		}

		if (($network != '') && ($ret['network'] != $network)) {
			logger('Expected network ' . $network . ' does not match actual network ' . $ret['network']);
			return $result;
		}

		// check if we already have a contact
		// the poll url is more reliable than the profile url, as we may have
		// indirect links or webfinger links

		$condition = ['uid' => $uid, 'poll' => [$ret['poll'], normalise_link($ret['poll'])], 'network' => $ret['network'], 'pending' => false];
		$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		if (!DBA::isResult($contact)) {
			$condition = ['uid' => $uid, 'nurl' => normalise_link($url), 'network' => $ret['network'], 'pending' => false];
			$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		}

		if (($ret['network'] === Protocol::DFRN) && !DBA::isResult($contact)) {
			if ($interactive) {
				if (strlen($a->getURLPath())) {
					$myaddr = bin2hex(System::baseUrl() . '/profile/' . $a->user['nickname']);
				} else {
					$myaddr = bin2hex($a->user['nickname'] . '@' . $a->getHostName());
				}

				$a->internalRedirect($ret['request'] . "&addr=$myaddr");

				// NOTREACHED
			}
		} elseif (Config::get('system', 'dfrn_only') && ($ret['network'] != Protocol::DFRN)) {
			$result['message'] = L10n::t('This site is not configured to allow communications with other networks.') . EOL;
			$result['message'] != L10n::t('No compatible communication protocols or feeds were discovered.') . EOL;
			return $result;
		}

		// This extra param just confuses things, remove it
		if ($ret['network'] === Protocol::DIASPORA) {
			$ret['url'] = str_replace('?absolute=true', '', $ret['url']);
		}

		// do we have enough information?

		if (!((x($ret, 'name')) && (x($ret, 'poll')) && ((x($ret, 'url')) || (x($ret, 'addr'))))) {
			$result['message'] .= L10n::t('The profile address specified does not provide adequate information.') . EOL;
			if (!x($ret, 'poll')) {
				$result['message'] .= L10n::t('No compatible communication protocols or feeds were discovered.') . EOL;
			}
			if (!x($ret, 'name')) {
				$result['message'] .= L10n::t('An author or name was not found.') . EOL;
			}
			if (!x($ret, 'url')) {
				$result['message'] .= L10n::t('No browser URL could be matched to this address.') . EOL;
			}
			if (strpos($url, '@') !== false) {
				$result['message'] .= L10n::t('Unable to match @-style Identity Address with a known protocol or email contact.') . EOL;
				$result['message'] .= L10n::t('Use mailto: in front of address to force email check.') . EOL;
			}
			return $result;
		}

		if ($ret['network'] === Protocol::OSTATUS && Config::get('system', 'ostatus_disabled')) {
			$result['message'] .= L10n::t('The profile address specified belongs to a network which has been disabled on this site.') . EOL;
			$ret['notify'] = '';
		}

		if (!$ret['notify']) {
			$result['message'] .= L10n::t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL;
		}

		$writeable = ((($ret['network'] === Protocol::OSTATUS) && ($ret['notify'])) ? 1 : 0);

		$subhub = (($ret['network'] === Protocol::OSTATUS) ? true : false);

		$hidden = (($ret['network'] === Protocol::MAIL) ? 1 : 0);

		if (in_array($ret['network'], [Protocol::MAIL, Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			$writeable = 1;
		}

		if (DBA::isResult($contact)) {
			// update contact
			$new_relation = (($contact['rel'] == self::FOLLOWER) ? self::FRIEND : self::SHARING);

			$fields = ['rel' => $new_relation, 'subhub' => $subhub, 'readonly' => false];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		} else {
			$new_relation = (in_array($ret['network'], [Protocol::MAIL]) ? self::FRIEND : self::SHARING);

			// create contact record
			DBA::insert('contact', [
				'uid'     => $uid,
				'created' => DateTimeFormat::utcNow(),
				'url'     => $ret['url'],
				'nurl'    => normalise_link($ret['url']),
				'addr'    => $ret['addr'],
				'alias'   => $ret['alias'],
				'batch'   => $ret['batch'],
				'notify'  => $ret['notify'],
				'poll'    => $ret['poll'],
				'poco'    => $ret['poco'],
				'name'    => $ret['name'],
				'nick'    => $ret['nick'],
				'network' => $ret['network'],
				'pubkey'  => $ret['pubkey'],
				'rel'     => $new_relation,
				'priority'=> $ret['priority'],
				'writable'=> $writeable,
				'hidden'  => $hidden,
				'blocked' => 0,
				'readonly'=> 0,
				'pending' => 0,
				'subhub'  => $subhub
			]);
		}

		$contact = DBA::selectFirst('contact', [], ['url' => $ret['url'], 'network' => $ret['network'], 'uid' => $uid]);
		if (!DBA::isResult($contact)) {
			$result['message'] .= L10n::t('Unable to retrieve contact information.') . EOL;
			return $result;
		}

		$contact_id = $contact['id'];
		$result['cid'] = $contact_id;

		Group::addMember(User::getDefaultGroup($uid, $contact["network"]), $contact_id);

		// Update the avatar
		self::updateAvatar($ret['photo'], $uid, $contact_id);

		// pull feed and consume it, which should subscribe to the hub.

		Worker::add(PRIORITY_HIGH, "OnePoll", $contact_id, "force");

		$owner = User::getOwnerDataById($uid);

		if (DBA::isResult($owner)) {
			if (in_array($contact['network'], [Protocol::OSTATUS, Protocol::DFRN])) {
				// create a follow slap
				$item = [];
				$item['verb'] = ACTIVITY_FOLLOW;
				$item['follow'] = $contact["url"];
				$item['body'] = '';
				$item['title'] = '';
				$item['guid'] = '';
				$item['tag'] = '';
				$item['attach'] = '';

				$slap = OStatus::salmon($item, $owner);

				if (!empty($contact['notify'])) {
					Salmon::slapper($owner, $contact['notify'], $slap);
				}
			} elseif ($contact['network'] == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare($a->user, $contact);
				logger('share returns: ' . $ret);
			} elseif ($contact['network'] == Protocol::ACTIVITYPUB) {
				$ret = ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $uid);
				logger('Follow returns: ' . $ret);
			}
		}

		$result['success'] = true;
		return $result;
	}

	/**
	 * @brief Updated contact's SSL policy
	 *
	 * @param array  $contact Contact array
	 * @param string $new_policy New policy, valid: self,full
	 *
	 * @return array Contact array with updated values
	 */
	public static function updateSslPolicy(array $contact, $new_policy)
	{
		$ssl_changed = false;
		if ((intval($new_policy) == SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'], 'https:')) {
			$ssl_changed = true;
			$contact['url']     = 	str_replace('https:', 'http:', $contact['url']);
			$contact['request'] = 	str_replace('https:', 'http:', $contact['request']);
			$contact['notify']  = 	str_replace('https:', 'http:', $contact['notify']);
			$contact['poll']    = 	str_replace('https:', 'http:', $contact['poll']);
			$contact['confirm'] = 	str_replace('https:', 'http:', $contact['confirm']);
			$contact['poco']    = 	str_replace('https:', 'http:', $contact['poco']);
		}

		if ((intval($new_policy) == SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'], 'http:')) {
			$ssl_changed = true;
			$contact['url']     = 	str_replace('http:', 'https:', $contact['url']);
			$contact['request'] = 	str_replace('http:', 'https:', $contact['request']);
			$contact['notify']  = 	str_replace('http:', 'https:', $contact['notify']);
			$contact['poll']    = 	str_replace('http:', 'https:', $contact['poll']);
			$contact['confirm'] = 	str_replace('http:', 'https:', $contact['confirm']);
			$contact['poco']    = 	str_replace('http:', 'https:', $contact['poco']);
		}

		if ($ssl_changed) {
			$fields = ['url' => $contact['url'], 'request' => $contact['request'],
					'notify' => $contact['notify'], 'poll' => $contact['poll'],
					'confirm' => $contact['confirm'], 'poco' => $contact['poco']];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		}

		return $contact;
	}

	public static function addRelationship($importer, $contact, $datarray, $item = '', $sharing = false) {
		// Should always be set
		if (empty($datarray['author-id'])) {
			return;
		}

		$fields = ['url', 'name', 'nick', 'photo', 'network'];
		$pub_contact = DBA::selectFirst('contact', $fields, ['id' => $datarray['author-id']]);
		if (!DBA::isResult($pub_contact)) {
			// Should never happen
			return;
		}

		$url = defaults($datarray, 'author-link', $pub_contact['url']);
		$name = $pub_contact['name'];
		$photo = $pub_contact['photo'];
		$nick = $pub_contact['nick'];
		$network = $pub_contact['network'];

		if (is_array($contact)) {
			if (($contact['rel'] == self::SHARING)
				|| ($sharing && $contact['rel'] == self::FOLLOWER)) {
				DBA::update('contact', ['rel' => self::FRIEND, 'writable' => true],
						['id' => $contact['id'], 'uid' => $importer['uid']]);
			}

			if ($contact['network'] == Protocol::ACTIVITYPUB) {
				ActivityPub\Transmitter::sendContactAccept($contact['url'], $contact['hub-verify'], $importer['uid']);
			}

			// send email notification to owner?
		} else {
			if (DBA::exists('contact', ['nurl' => normalise_link($url), 'uid' => $importer['uid'], 'pending' => true])) {
				logger('ignoring duplicated connection request from pending contact ' . $url);
				return;
			}
			// create contact record
			q("INSERT INTO `contact` (`uid`, `created`, `url`, `nurl`, `name`, `nick`, `photo`, `network`, `rel`,
				`blocked`, `readonly`, `pending`, `writable`)
				VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, 0, 0, 1, 1)",
				intval($importer['uid']),
				DBA::escape(DateTimeFormat::utcNow()),
				DBA::escape($url),
				DBA::escape(normalise_link($url)),
				DBA::escape($name),
				DBA::escape($nick),
				DBA::escape($photo),
				DBA::escape($network),
				intval(self::FOLLOWER)
			);

			$contact_record = [
				'id' => DBA::lastInsertId(),
				'network' => $network,
				'name' => $name,
				'url' => $url,
				'photo' => $photo
			];

			Contact::updateAvatar($photo, $importer["uid"], $contact_record["id"], true);

			/// @TODO Encapsulate this into a function/method
			$fields = ['uid', 'username', 'email', 'page-flags', 'notify-flags', 'language'];
			$user = DBA::selectFirst('user', $fields, ['uid' => $importer['uid']]);
			if (DBA::isResult($user) && !in_array($user['page-flags'], [self::PAGE_SOAPBOX, self::PAGE_FREELOVE, self::PAGE_COMMUNITY])) {
				// create notification
				$hash = random_string();

				if (is_array($contact_record)) {
					DBA::insert('intro', ['uid' => $importer['uid'], 'contact-id' => $contact_record['id'],
								'blocked' => false, 'knowyou' => false,
								'hash' => $hash, 'datetime' => DateTimeFormat::utcNow()]);
				}

				Group::addMember(User::getDefaultGroup($importer['uid'], $contact_record["network"]), $contact_record['id']);

				if (($user['notify-flags'] & NOTIFY_INTRO) &&
					in_array($user['page-flags'], [self::PAGE_NORMAL])) {

					notification([
						'type'         => NOTIFY_INTRO,
						'notify_flags' => $user['notify-flags'],
						'language'     => $user['language'],
						'to_name'      => $user['username'],
						'to_email'     => $user['email'],
						'uid'          => $user['uid'],
						'link'         => System::baseUrl() . '/notifications/intro',
						'source_name'  => ((strlen(stripslashes($contact_record['name']))) ? stripslashes($contact_record['name']) : L10n::t('[Name Withheld]')),
						'source_link'  => $contact_record['url'],
						'source_photo' => $contact_record['photo'],
						'verb'         => ($sharing ? ACTIVITY_FRIEND : ACTIVITY_FOLLOW),
						'otype'        => 'intro'
					]);

				}
			} elseif (DBA::isResult($user) && in_array($user['page-flags'], [self::PAGE_SOAPBOX, self::PAGE_FREELOVE, self::PAGE_COMMUNITY])) {
				$condition = ['uid' => $importer['uid'], 'url' => $url, 'pending' => true];
				DBA::update('contact', ['pending' => false], $condition);

				$contact = DBA::selectFirst('contact', ['url', 'network', 'hub-verify'], ['id' => $contact_record['id']]);

				if ($contact['network'] == Protocol::ACTIVITYPUB) {
					ActivityPub\Transmitter::sendContactAccept($contact['url'], $contact['hub-verify'], $importer['uid']);
				}
			}
		}
	}

	public static function removeFollower($importer, $contact, array $datarray = [], $item = "")
	{
		if (($contact['rel'] == self::FRIEND) || ($contact['rel'] == self::SHARING)) {
			DBA::update('contact', ['rel' => self::SHARING], ['id' => $contact['id']]);
		} else {
			Contact::remove($contact['id']);
		}
	}

	public static function removeSharer($importer, $contact, array $datarray = [], $item = "")
	{
		if (($contact['rel'] == self::FRIEND) || ($contact['rel'] == self::FOLLOWER)) {
			DBA::update('contact', ['rel' => self::FOLLOWER], ['id' => $contact['id']]);
		} else {
			Contact::remove($contact['id']);
		}
	}

	/**
	 * @brief Create a birthday event.
	 *
	 * Update the year and the birthday.
	 */
	public static function updateBirthdays()
	{
		// This only handles foreign or alien networks where a birthday has been provided.
		// In-network birthdays are handled within local_delivery

		$r = q("SELECT * FROM `contact` WHERE `bd` != '' AND `bd` > '0001-01-01' AND SUBSTRING(`bd`, 1, 4) != `bdyear` ");
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				logger('update_contact_birthday: ' . $rr['bd']);

				$nextbd = DateTimeFormat::utcNow('Y') . substr($rr['bd'], 4);

				/*
				 * Add new birthday event for this person
				 *
				 * $bdtext is just a readable placeholder in case the event is shared
				 * with others. We will replace it during presentation to our $importer
				 * to contain a sparkle link and perhaps a photo.
				 */

				// Check for duplicates
				$condition = ['uid' => $rr['uid'], 'cid' => $rr['id'],
					'start' => DateTimeFormat::utc($nextbd), 'type' => 'birthday'];
				if (DBA::exists('event', $condition)) {
					continue;
				}

				$bdtext = L10n::t('%s\'s birthday', $rr['name']);
				$bdtext2 = L10n::t('Happy Birthday %s', ' [url=' . $rr['url'] . ']' . $rr['name'] . '[/url]');

				q("INSERT INTO `event` (`uid`,`cid`,`created`,`edited`,`start`,`finish`,`summary`,`desc`,`type`,`adjust`)
				VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' ) ", intval($rr['uid']), intval($rr['id']),
					DBA::escape(DateTimeFormat::utcNow()), DBA::escape(DateTimeFormat::utcNow()), DBA::escape(DateTimeFormat::utc($nextbd)),
					DBA::escape(DateTimeFormat::utc($nextbd . ' + 1 day ')), DBA::escape($bdtext), DBA::escape($bdtext2), DBA::escape('birthday'),
					intval(0)
				);

				// update bdyear
				q("UPDATE `contact` SET `bdyear` = '%s', `bd` = '%s' WHERE `uid` = %d AND `id` = %d", DBA::escape(substr($nextbd, 0, 4)),
					DBA::escape($nextbd), intval($rr['uid']), intval($rr['id'])
				);
			}
		}
	}

	/**
	 * Remove the unavailable contact ids from the provided list
	 *
	 * @param array $contact_ids Contact id list
	 */
	public static function pruneUnavailable(array &$contact_ids)
	{
		if (empty($contact_ids)) {
			return;
		}

		$str = DBA::escape(implode(',', $contact_ids));

		$stmt = DBA::p("SELECT `id` FROM `contact` WHERE `id` IN ( " . $str . ") AND `blocked` = 0 AND `pending` = 0 AND `archive` = 0");

		$return = [];
		while($contact = DBA::fetch($stmt)) {
			$return[] = $contact['id'];
		}

		DBA::close($stmt);

		$contact_ids = $return;
	}

	/**
	 * @brief Returns a magic link to authenticate remote visitors
	 *
	 * @todo check if the return is either a fully qualified URL or a relative path to Friendica basedir
	 *
	 * @param string $contact_url The address of the target contact profile
	 * @param string $url An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 */
	public static function magicLink($contact_url, $url = '')
	{
		$cid = self::getIdForURL($contact_url, 0, true);
		if (empty($cid)) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		return self::magicLinkbyId($cid, $url);
	}

	/**
	 * @brief Returns a magic link to authenticate remote visitors
	 *
	 * @param integer $cid The contact id of the target contact profile
	 * @param integer $url An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 */
	public static function magicLinkbyId($cid, $url = '')
	{
		$contact = DBA::selectFirst('contact', ['id', 'network', 'url', 'uid'], ['id' => $cid]);

		return self::magicLinkbyContact($contact, $url);
        }

	/**
	 * @brief Returns a magic link to authenticate remote visitors
	 *
	 * @param array $contact The contact array with "uid", "network" and "url"
	 * @param string $url An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 */
	public static function magicLinkbyContact($contact, $url = '')
	{
		if ($contact['network'] != Protocol::DFRN) {
			return $url ?: $contact['url']; // Equivalent to ($url != '') ? $url : $contact['url'];
		}

		// Only redirections to the same host do make sense
		if (($url != '') && (parse_url($url, PHP_URL_HOST) != parse_url($contact['url'], PHP_URL_HOST))) {
			return $url;
		}

		if ($contact['uid'] != 0) {
			return self::magicLink($contact['url'], $url);
		}

		$redirect = 'redir/' . $contact['id'];

		if ($url != '') {
			$redirect .= '?url=' . $url;
		}

		return $redirect;
	}
}

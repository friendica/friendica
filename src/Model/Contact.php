<?php
/**
 * @file src/Model/Contact.php
 */
namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Content\Pager;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Network\Probe;
use Friendica\Object\Image;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\PortableContact;
use Friendica\Protocol\Salmon;
use Friendica\Util\BaseURL;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * @brief functions for interacting with a contact
 */
class Contact extends BaseObject
{
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_NORMAL
	 */
	const PAGE_NORMAL    = User::PAGE_FLAGS_NORMAL;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_SOAPBOX
	 */
	const PAGE_SOAPBOX   = User::PAGE_FLAGS_SOAPBOX;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_COMMUNITY
	 */
	const PAGE_COMMUNITY = User::PAGE_FLAGS_COMMUNITY;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_FREELOVE
	 */
	const PAGE_FREELOVE  = User::PAGE_FLAGS_FREELOVE;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_BLOG
	 */
	const PAGE_BLOG      = User::PAGE_FLAGS_BLOG;
	/**
	 * @deprecated since version 2019.03
	 * @see User::PAGE_FLAGS_PRVGROUP
	 */
	const PAGE_PRVGROUP  = User::PAGE_FLAGS_PRVGROUP;
	/**
	 * @}
	 */

	/**
	 * Account types
	 *
	 * TYPE_UNKNOWN - the account has been imported from gcontact where this is the default type value
	 *
	 * TYPE_PERSON - the account belongs to a person
	 *	Associated page types: PAGE_NORMAL, PAGE_SOAPBOX, PAGE_FREELOVE
	 *
	 * TYPE_ORGANISATION - the account belongs to an organisation
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * TYPE_NEWS - the account is a news reflector
	 *	Associated page type: PAGE_SOAPBOX
	 *
	 * TYPE_COMMUNITY - the account is community forum
	 *	Associated page types: PAGE_COMMUNITY, PAGE_PRVGROUP
	 *
	 * TYPE_RELAY - the account is a relay
	 *      This will only be assigned to contacts, not to user accounts
	 * @{
	 */
	const TYPE_UNKNOWN =     -1;
	const TYPE_PERSON =       User::ACCOUNT_TYPE_PERSON;
	const TYPE_ORGANISATION = User::ACCOUNT_TYPE_ORGANISATION;
	const TYPE_NEWS =         User::ACCOUNT_TYPE_NEWS;
	const TYPE_COMMUNITY =    User::ACCOUNT_TYPE_COMMUNITY;
	const TYPE_RELAY =        User::ACCOUNT_TYPE_RELAY;
	/**
	 * @}
	 */

	/**
	 * Contact_is
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
	 * @param array $fields    Array of selected fields, empty for all
	 * @param array $condition Array of fields for condition
	 * @param array $params    Array of several parameters
	 * @return array
	 * @throws \Exception
	 */
	public static function select(array $fields = [], array $condition = [], array $params = [])
	{
		$statement = DBA::select('contact', $fields, $condition, $params);

		return DBA::toArray($statement);
	}

	/**
	 * @param integer $id     Contact ID
	 * @param array   $fields Array of selected fields, empty for all
	 * @return array|boolean Contact record if it exists, false otherwise
	 * @throws \Exception
	 */
	public static function getById($id, $fields = [])
	{
		return DBA::selectFirst('contact', $fields, ['id' => $id]);
	}

	/**
	 * @brief Tests if the given contact is a follower
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id a follower?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isFollower($cid, $uid)
	{
		if (self::isBlockedByUser($cid, $uid)) {
			return false;
		}

		$cdata = self::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata['user'])) {
			return false;
		}

		$condition = ['id' => $cdata['user'], 'rel' => [self::FOLLOWER, self::FRIEND]];
		return DBA::exists('contact', $condition);
	}

	/**
	 * @brief Get the basepath for a given contact link
	 * @todo  Add functionality to store this value in the contact table
	 *
	 * @param string $url The contact link
	 *
	 * @return string basepath
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getBasepath($url)
	{
		$data = Probe::uri($url);
		if (!empty($data['baseurl'])) {
			return $data['baseurl'];
		}

		// When we can't probe the server, we use some ugly function that does some pattern matching
		return PortableContact::detectServer($url);
	}

	/**
	 * Returns the public contact id of the given user id
	 *
	 * @param  integer $uid User ID
	 *
	 * @return integer|boolean Public contact id for given user id
	 * @throws Exception
	 */
	public static function getPublicIdByUserId($uid)
	{
		$self = DBA::selectFirst('contact', ['url'], ['self' => true, 'uid' => $uid]);
		if (!DBA::isResult($self)) {
			return false;
		}
		return self::getIdForURL($self['url'], 0, true);
	}

	/**
	 * @brief Returns the contact id for the user and the public contact id for a given contact id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return array with public and user's contact id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getPublicAndUserContacID($cid, $uid)
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
	 * Returns contact details for a given contact id in combination with a user id
	 *
	 * @param int $cid A contact ID
	 * @param int $uid The User ID
	 * @param array $fields The selected fields for the contact
	 *
	 * @return array The contact details
	 *
	 * @throws \Exception
	 */
	public static function getContactForUser($cid, $uid, array $fields = [])
	{
		$contact = DBA::selectFirst('contact', $fields, ['id' => $cid, 'uid' => $uid]);

		if (!DBA::isResult($contact)) {
			return [];
		} else {
			return $contact;
		}
	}

	/**
	 * @brief Block contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the contact blocked or unblocked?
	 * @throws \Exception
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

		if ($blocked) {
			// Blocked contact can't be in any group
			self::removeFromGroups($cid);
		}
	}

	/**
	 * @brief Returns "block" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws \Exception
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
	 * @throws \Exception
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
	 * @throws \Exception
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
	 * @throws \Exception
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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
	 * @throws \Exception
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
				AND NOT `contact`.`deleted`
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
	 * @throws \Exception
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
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
			'nurl'        => Strings::normaliseLink(System::baseUrl() . '/profile/' . $user['nickname']),
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
	 * @param int     $uid
	 * @param boolean $update_avatar Force the avatar update
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function updateSelfFromUserID($uid, $update_avatar = false)
	{
		$fields = ['id', 'name', 'nick', 'location', 'about', 'keywords', 'gender', 'avatar',
			'xmpp', 'contact-type', 'forum', 'prv', 'avatar-date', 'url', 'nurl',
			'photo', 'thumb', 'micro', 'addr', 'request', 'notify', 'poll', 'confirm', 'poco'];
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

		$avatar = Photo::selectFirst(['resource-id', 'type'], ['uid' => $uid, 'profile' => true]);
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
			$fields['photo'] = System::baseUrl() . '/images/person-300.jpg';
			$fields['thumb'] = System::baseUrl() . '/images/person-80.jpg';
			$fields['micro'] = System::baseUrl() . '/images/person-48.jpg';
		}

		$fields['forum'] = $user['page-flags'] == User::PAGE_FLAGS_COMMUNITY;
		$fields['prv'] = $user['page-flags'] == User::PAGE_FLAGS_PRVGROUP;

		// it seems as if ported accounts can have wrong values, so we make sure that now everything is fine.
		$fields['url'] = System::baseUrl() . '/profile/' . $user['nickname'];
		$fields['nurl'] = Strings::normaliseLink($fields['url']);
		$fields['addr'] = $user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);
		$fields['request'] = System::baseUrl() . '/dfrn_request/' . $user['nickname'];
		$fields['notify'] = System::baseUrl() . '/dfrn_notify/' . $user['nickname'];
		$fields['poll'] = System::baseUrl() . '/dfrn_poll/'. $user['nickname'];
		$fields['confirm'] = System::baseUrl() . '/dfrn_confirm/' . $user['nickname'];
		$fields['poco'] = System::baseUrl() . '/poco/' . $user['nickname'];

		$update = false;

		foreach ($fields as $field => $content) {
			if ($self[$field] != $content) {
				$update = true;
			}
		}

		if ($update) {
			if ($fields['name'] != $self['name']) {
				$fields['name-date'] = DateTimeFormat::utcNow();
			}
			$fields['updated'] = DateTimeFormat::utcNow();
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
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
		Worker::add(PRIORITY_MEDIUM, 'RemoveContact', $id);
	}

	/**
	 * @brief Sends an unfriend message. Does not remove the contact
	 *
	 * @param array   $user     User unfriending
	 * @param array   $contact  Contact unfriended
	 * @param boolean $dissolve Remove the contact on the remote side
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function terminateFriendship(array $user, array $contact, $dissolve = false)
	{
		if (empty($contact['network'])) {
			return;
		}

		$protocol = $contact['network'];
		if (($protocol == Protocol::DFRN) && !self::isLegacyDFRNContact($contact)) {
			$protocol = Protocol::ACTIVITYPUB;
		}

		if (($protocol == Protocol::DFRN) && $dissolve) {
			DFRN::deliver($user, $contact, 'placeholder', true);
		} elseif (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
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
		} elseif ($protocol == Protocol::DIASPORA) {
			Diaspora::sendUnshare($user, $contact);
		} elseif ($protocol == Protocol::ACTIVITYPUB) {
			ActivityPub\Transmitter::sendContactUndo($contact['url'], $contact['id'], $user['uid']);

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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function markForArchival(array $contact)
	{
		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'archive', 'self', 'term-date'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		} elseif (!isset($contact['url'])) {
			Logger::log('Empty contact: ' . json_encode($contact) . ' - ' . System::callstack(20), Logger::DEBUG);
		}

		Logger::log('Contact '.$contact['id'].' is marked for archival', Logger::DEBUG);

		// Contact already archived or "self" contact? => nothing to do
		if ($contact['archive'] || $contact['self']) {
			return;
		}

		if ($contact['term-date'] <= DBA::NULL_DATETIME) {
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			DBA::update('contact', ['term-date' => DateTimeFormat::utcNow()], ['`nurl` = ? AND `term-date` <= ? AND NOT `self`', Strings::normaliseLink($contact['url']), DBA::NULL_DATETIME]);
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
				DBA::update('contact', ['archive' => 1], ['nurl' => Strings::normaliseLink($contact['url']), 'self' => false]);
			}
		}
	}

	/**
	 * @brief Cancels the archival countdown
	 *
	 * @see   Contact::markForArchival()
	 *
	 * @param array $contact contact to be unmarked for archival
	 * @return null
	 * @throws \Exception
	 */
	public static function unmarkForArchival(array $contact)
	{
		$condition = ['`id` = ? AND (`term-date` > ? OR `archive`)', $contact['id'], DBA::NULL_DATETIME];
		$exists = DBA::exists('contact', $condition);

		// We don't need to update, we never marked this contact for archival
		if (!$exists) {
			return;
		}

		Logger::log('Contact '.$contact['id'].' is marked as vital again', Logger::DEBUG);

		if (!isset($contact['url']) && !empty($contact['id'])) {
			$fields = ['id', 'url', 'batch'];
			$contact = DBA::selectFirst('contact', $fields, ['id' => $contact['id']]);
			if (!DBA::isResult($contact)) {
				return;
			}
		}

		// It's a miracle. Our dead contact has inexplicably come back to life.
		$fields = ['term-date' => DBA::NULL_DATETIME, 'archive' => false];
		DBA::update('contact', $fields, ['id' => $contact['id']]);
		DBA::update('contact', $fields, ['nurl' => Strings::normaliseLink($contact['url'])]);

		if (!empty($contact['batch'])) {
			$condition = ['batch' => $contact['batch'], 'contact-type' => self::TYPE_RELAY];
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
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
		FROM `contact` WHERE `nurl` = ? AND `uid` = ?", Strings::normaliseLink($url), $uid);
		$r = DBA::toArray($s);

		// Fetch contact data from the contact table for the given user, checking with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, `id` AS `cid`, 0 AS `gid`, 0 AS `zid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
				`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, `self`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = ?", Strings::normaliseLink($url), $url, $ssl_url, $uid);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically)
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
			FROM `contact` WHERE `nurl` = ? AND `uid` = 0", Strings::normaliseLink($url));
			$r = DBA::toArray($s);
		}

		// Fetch the data from the contact table with "uid=0" (which is filled automatically) - checked with the alias
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT `id`, 0 AS `cid`, `id` AS `zid`, 0 AS `gid`, `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, `xmpp`,
			`keywords`, `gender`, `photo`, `thumb`, `micro`, `forum`, `prv`, (`forum` | `prv`) AS `community`, `contact-type`, `bd` AS `birthday`, 0 AS `self`
			FROM `contact` WHERE `alias` IN (?, ?, ?) AND `uid` = 0", Strings::normaliseLink($url), $url, $ssl_url);
			$r = DBA::toArray($s);
		}

		// Fetch the data from the gcontact table
		if (!DBA::isResult($r)) {
			$s = DBA::p("SELECT 0 AS `id`, 0 AS `cid`, `id` AS `gid`, 0 AS `zid`, 0 AS `uid`, `url`, `nurl`, `alias`, `network`, `name`, `nick`, `addr`, `location`, `about`, '' AS `xmpp`,
			`keywords`, `gender`, `photo`, `photo` AS `thumb`, `photo` AS `micro`, 0 AS `forum`, 0 AS `prv`, `community`, `contact-type`, `birthday`, 0 AS `self`
			FROM `gcontact` WHERE `nurl` = ?", Strings::normaliseLink($url));
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
			if ($profile["birthday"] > DBA::NULL_DATE) {
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
				$profile["bd"] = DBA::NULL_DATE;
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
			$profile["birthday"] = DBA::NULL_DATE;
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getDetailsByAddr($addr, $uid = -1)
	{
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function photoMenu(array $contact, $uid = 0)
	{
		$pm_url = '';
		$status_link = '';
		$photos_link = '';
		$contact_drop_link = '';

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
			$profile_link = System::baseUrl() . '/redir/' . $contact['id'] . '?url=' . $contact['url'];
		} else {
			$profile_link = $contact['url'];
		}

		if ($profile_link === 'mailbox') {
			$profile_link = '';
		}

		if ($sparkle) {
			$status_link = $profile_link . '?tab=status';
			$photos_link = str_replace('/profile/', '/photos/', $profile_link);
			$profile_link = $profile_link . '?tab=profile';
		}

		if (in_array($contact['network'], [Protocol::DFRN, Protocol::DIASPORA]) && !$contact['self']) {
			$pm_url = System::baseUrl() . '/message/new/' . $contact['id'];
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
			];
		}

		$args = ['contact' => $contact, 'menu' => &$menu];

		Hook::callAll('contact_photo_menu', $args);

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
	 * @param int $uid uid
	 * @return array
	 * @throws \Exception
	 */
	public static function getUngroupedList($uid)
	{
		return q("SELECT *
			   FROM `contact`
			   WHERE `uid` = %d
			   AND NOT `self`
			   AND NOT `deleted`
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
	 * Have a look at all contact tables for a given profile url.
	 * This function works as a replacement for probing the contact.
	 *
	 * @param string  $url Contact URL
	 * @param integer $cid Contact ID
	 *
	 * @return array Contact array in the "probe" structure
	*/
	private static function getProbeDataFromDatabase($url, $cid = null)
	{
		// The link could be provided as http although we stored it as https
		$ssl_url = str_replace('http://', 'https://', $url);

		$fields = ['id', 'uid', 'url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
			'photo', 'keywords', 'location', 'about', 'network',
			'priority', 'batch', 'request', 'confirm', 'poco'];

		if (!empty($cid)) {
			$data = DBA::selectFirst('contact', $fields, ['id' => $cid]);
			if (DBA::isResult($data)) {
				return $data;
			}
		}

		$data = DBA::selectFirst('contact', $fields, ['nurl' => Strings::normaliseLink($url)]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			// For security reasons we don't fetch key data from our users
			$data["pubkey"] = '';
			return $data;
		}

		$fields = ['url', 'addr', 'alias', 'notify', 'name', 'nick',
			'photo', 'keywords', 'location', 'about', 'network'];
		$data = DBA::selectFirst('gcontact', $fields, ['nurl' => Strings::normaliseLink($url)]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			$data["pubkey"] = '';
			$data["poll"] = '';
			$data["priority"] = 0;
			$data["batch"] = '';
			$data["request"] = '';
			$data["confirm"] = '';
			$data["poco"] = '';
			return $data;
		}

		$data = ActivityPub::probeProfile($url, false);
		if (!empty($data)) {
			return $data;
		}

		$fields = ['url', 'addr', 'alias', 'notify', 'poll', 'name', 'nick',
			'photo', 'network', 'priority', 'batch', 'request', 'confirm'];
		$data = DBA::selectFirst('fcontact', $fields, ['url' => $url]);

		if (!DBA::isResult($data)) {
			$condition = ['alias' => [$url, Strings::normaliseLink($url), $ssl_url]];
			$data = DBA::selectFirst('contact', $fields, $condition);
		}

		if (DBA::isResult($data)) {
			$data["pubkey"] = '';
			$data["keywords"] = '';
			$data["location"] = '';
			$data["about"] = '';
			$data["poco"] = '';
			return $data;
		}

		return [];
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getIdForURL($url, $uid = 0, $no_update = false, $default = [], $in_loop = false)
	{
		Logger::log("Get contact data for url " . $url . " and user " . $uid . " - " . System::callstack(), Logger::DEBUG);

		$contact_id = 0;

		if ($url == '') {
			return 0;
		}

		/// @todo Verify if we can't use Contact::getDetailsByUrl instead of the following
		// We first try the nurl (http://server.tld/nick), most common case
		$contact = DBA::selectFirst('contact', ['id', 'avatar', 'updated', 'network'], ['nurl' => Strings::normaliseLink($url), 'uid' => $uid, 'deleted' => false]);

		// Then the addr (nick@server.tld)
		if (!DBA::isResult($contact)) {
			$contact = DBA::selectFirst('contact', ['id', 'avatar', 'updated', 'network'], ['addr' => $url, 'uid' => $uid, 'deleted' => false]);
		}

		// Then the alias (which could be anything)
		if (!DBA::isResult($contact)) {
			// The link could be provided as http although we stored it as https
			$ssl_url = str_replace('http://', 'https://', $url);
			$condition = ['`alias` IN (?, ?, ?) AND `uid` = ? AND NOT `deleted`', $url, Strings::normaliseLink($url), $ssl_url, $uid];
			$contact = DBA::selectFirst('contact', ['id', 'avatar', 'updated', 'network'], $condition);
		}

		if (DBA::isResult($contact)) {
			$contact_id = $contact["id"];

			// Update the contact every 7 days
			$update_contact = ($contact['updated'] < DateTimeFormat::utc('now -7 days'));

			// We force the update if the avatar is empty
			if (empty($contact['avatar'])) {
				$update_contact = true;
			}

			// Update the contact in the background if needed but it is called by the frontend
			if ($update_contact && $no_update && in_array($contact['network'], Protocol::NATIVE_SUPPORT)) {
				Worker::add(PRIORITY_LOW, "UpdateContact", $contact_id);
			}

			if (!$update_contact || $no_update) {
				return $contact_id;
			}
		} elseif ($uid != 0) {
			// Non-existing user-specific contact, exiting
			return 0;
		}

		// When we don't want to update, we look if we know this contact in any way
		if ($no_update && empty($default)) {
			$data = self::getProbeDataFromDatabase($url, $contact_id);
			$background_update = true;
		} else {
			$data = [];
			$background_update = false;
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

			$contact = array_merge(self::getProbeDataFromDatabase($url, $contact_id), $default);
			if (empty($contact)) {
				return 0;
			}

			$data = array_merge($data, $contact);
		}

		if (empty($data)) {
			return 0;
		}

		if (!$contact_id && !empty($data['alias']) && ($data['alias'] != $url) && !$in_loop) {
			$contact_id = self::getIdForURL($data["alias"], $uid, true, $default, true);
		}

		if (!$contact_id) {
			$fields = [
				'uid'       => $uid,
				'created'   => DateTimeFormat::utcNow(),
				'url'       => $data['url'],
				'nurl'      => Strings::normaliseLink($data['url']),
				'addr'      => defaults($data, 'addr', ''),
				'alias'     => defaults($data, 'alias', ''),
				'notify'    => defaults($data, 'notify', ''),
				'poll'      => defaults($data, 'poll', ''),
				'name'      => defaults($data, 'name', ''),
				'nick'      => defaults($data, 'nick', ''),
				'photo'     => defaults($data, 'photo', ''),
				'keywords'  => defaults($data, 'keywords', ''),
				'location'  => defaults($data, 'location', ''),
				'about'     => defaults($data, 'about', ''),
				'network'   => $data['network'],
				'pubkey'    => defaults($data, 'pubkey', ''),
				'rel'       => self::SHARING,
				'priority'  => defaults($data, 'priority', 0),
				'batch'     => defaults($data, 'batch', ''),
				'request'   => defaults($data, 'request', ''),
				'confirm'   => defaults($data, 'confirm', ''),
				'poco'      => defaults($data, 'poco', ''),
				'name-date' => DateTimeFormat::utcNow(),
				'uri-date'  => DateTimeFormat::utcNow(),
				'avatar-date' => DateTimeFormat::utcNow(),
				'writable'  => 1,
				'blocked'   => 0,
				'readonly'  => 0,
				'pending'   => 0];

			$condition = ['nurl' => Strings::normaliseLink($data["url"]), 'uid' => $uid, 'deleted' => false];

			DBA::update('contact', $fields, $condition, true);

			$s = DBA::select('contact', ['id'], $condition, ['order' => ['id'], 'limit' => 2]);
			$contacts = DBA::toArray($s);
			if (!DBA::isResult($contacts)) {
				return 0;
			}

			$contact_id = $contacts[0]["id"];

			// Update in the background when we fetched the data solely from the database
			if ($background_update) {
				Worker::add(PRIORITY_LOW, "UpdateContact", $contact_id);
			}

			// Update the newly created contact from data in the gcontact table
			$gcontact = DBA::selectFirst('gcontact', ['location', 'about', 'keywords', 'gender'], ['nurl' => Strings::normaliseLink($data["url"])]);
			if (DBA::isResult($gcontact)) {
				// Only use the information when the probing hadn't fetched these values
				if (!empty($data['keywords'])) {
					unset($gcontact['keywords']);
				}
				if (!empty($data['location'])) {
					unset($gcontact['location']);
				}
				if (!empty($data['about'])) {
					unset($gcontact['about']);
				}
				DBA::update('contact', $gcontact, ['id' => $contact_id]);
			}

			if (count($contacts) > 1 && $uid == 0 && $contact_id != 0 && $data["url"] != "") {
				$condition = ["`nurl` = ? AND `uid` = ? AND `id` != ? AND NOT `self`",
					Strings::normaliseLink($data["url"]), 0, $contact_id];
				Logger::log('Deleting duplicate contact ' . json_encode($condition), Logger::DEBUG);
				DBA::delete('contact', $condition);
			}
		}

		if (!empty($data['photo'])) {
			self::updateAvatar($data['photo'], $uid, $contact_id);
		}

		$fields = ['url', 'nurl', 'addr', 'alias', 'name', 'nick', 'keywords', 'location', 'about', 'avatar-date', 'pubkey'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $contact_id]);

		// This condition should always be true
		if (!DBA::isResult($contact)) {
			return $contact_id;
		}

		$updated = ['addr' => $data['addr'],
			'alias' => defaults($data, 'alias', ''),
			'url' => $data['url'],
			'nurl' => Strings::normaliseLink($data['url']),
			'name' => $data['name'],
			'nick' => $data['nick']];

		if (!empty($data['keywords'])) {
			$updated['keywords'] = $data['keywords'];
		}
		if (!empty($data['location'])) {
			$updated['location'] = $data['location'];
		}

		// Update the technical stuff as well - if filled
		if (!empty($data['notify'])) {
			$updated['notify'] = $data['notify'];
		}
		if (!empty($data['poll'])) {
			$updated['poll'] = $data['poll'];
		}
		if (!empty($data['batch'])) {
			$updated['batch'] = $data['batch'];
		}
		if (!empty($data['request'])) {
			$updated['request'] = $data['request'];
		}
		if (!empty($data['confirm'])) {
			$updated['confirm'] = $data['confirm'];
		}
		if (!empty($data['poco'])) {
			$updated['poco'] = $data['poco'];
		}

		// Only fill the pubkey if it had been empty before. We have to prevent identity theft.
		if (empty($contact['pubkey'])) {
			$updated['pubkey'] = $data['pubkey'];
		}

		if (($data['addr'] != $contact['addr']) || (!empty($data['alias']) && ($data['alias'] != $contact['alias']))) {
			$updated['uri-date'] = DateTimeFormat::utcNow();
		}
		if (($data["name"] != $contact["name"]) || ($data["nick"] != $contact["nick"])) {
			$updated['name-date'] = DateTimeFormat::utcNow();
		}

		$updated['updated'] = DateTimeFormat::utcNow();

		DBA::update('contact', $updated, ['id' => $contact_id], $contact);

		return $contact_id;
	}

	/**
	 * @brief Checks if the contact is blocked
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact blocked?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function isBlocked($cid)
	{
		if ($cid == 0) {
			return false;
		}

		$blocked = DBA::selectFirst('contact', ['blocked', 'url'], ['id' => $cid]);
		if (!DBA::isResult($blocked)) {
			return false;
		}

		if (Network::isUrlBlocked($blocked['url'])) {
			return true;
		}

		return (bool) $blocked['blocked'];
	}

	/**
	 * @brief Checks if the contact is hidden
	 *
	 * @param int $cid contact id
	 *
	 * @return boolean Is the contact hidden?
	 * @throws \Exception
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
	 * @param bool   $thread_mode
	 * @param int    $update
	 * @return string posts in HTML
	 * @throws \Exception
	 */
	public static function getPostsFromUrl($contact_url, $thread_mode = false, $update = 0)
	{
		$a = self::getApp();

		$cid = self::getIdForURL($contact_url);

		$contact = DBA::selectFirst('contact', ['contact-type', 'network'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return '';
		}

		if (in_array($contact["network"], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, ""])) {
			$sql = "(`item`.`uid` = 0 OR (`item`.`uid` = ? AND NOT `item`.`global`))";
		} else {
			$sql = "`item`.`uid` = ?";
		}

		$contact_field = ($contact["contact-type"] == self::TYPE_COMMUNITY ? 'owner-id' : 'author-id');

		if ($thread_mode) {
			$condition = ["`$contact_field` = ? AND `gravity` = ? AND " . $sql,
				$cid, GRAVITY_PARENT, local_user()];
		} else {
			$condition = ["`$contact_field` = ? AND `gravity` IN (?, ?) AND " . $sql,
				$cid, GRAVITY_PARENT, GRAVITY_COMMENT, local_user()];
		}

		$pager = new Pager($a->query_string);

		$params = ['order' => ['created' => true],
			'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		if ($thread_mode) {
			$r = Item::selectThreadForUser(local_user(), ['uri'], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, $pager, 'contacts', $update, false, 'commented', local_user());
		} else {
			$r = Item::selectForUser(local_user(), [], $condition, $params);

			$items = Item::inArray($r);

			$o = conversation($a, $items, $pager, 'contact-posts', false);
		}

		if (!$update) {
			$o .= $pager->renderMinimal(count($items));
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
		// "forum" and "prv" are used in the contact table. They stand for User::PAGE_FLAGS_COMMUNITY and User::PAGE_FLAGS_PRVGROUP.
		// "community" is used in the gcontact table and is true if the contact is User::PAGE_FLAGS_COMMUNITY or User::PAGE_FLAGS_PRVGROUP.
		if ((isset($contact['page-flags']) && (intval($contact['page-flags']) == User::PAGE_FLAGS_COMMUNITY))
			|| (isset($contact['page-flags']) && (intval($contact['page-flags']) == User::PAGE_FLAGS_PRVGROUP))
			|| (isset($contact['forum']) && intval($contact['forum']))
			|| (isset($contact['prv']) && intval($contact['prv']))
			|| (isset($contact['community']) && intval($contact['community']))
		) {
			$type = self::TYPE_COMMUNITY;
		} else {
			$type = self::TYPE_PERSON;
		}

		// The "contact-type" (contact table) and "account-type" (user table) are more general then the chaos from above.
		if (isset($contact["contact-type"])) {
			$type = $contact["contact-type"];
		}

		if (isset($contact["account-type"])) {
			$type = $contact["account-type"];
		}

		switch ($type) {
			case self::TYPE_ORGANISATION:
				$account_type = L10n::t("Organisation");
				break;

			case self::TYPE_NEWS:
				$account_type = L10n::t('News');
				break;

			case self::TYPE_COMMUNITY:
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
	 * @param int $cid
	 * @return bool
	 * @throws \Exception
	 */
	public static function block($cid, $reason = null)
	{
		$return = DBA::update('contact', ['blocked' => true, 'block_reason' => $reason], ['id' => $cid]);

		return $return;
	}

	/**
	 * @brief Unblocks a contact
	 *
	 * @param int $cid
	 * @return bool
	 * @throws \Exception
	 */
	public static function unblock($cid)
	{
		$return = DBA::update('contact', ['blocked' => false, 'block_reason' => null], ['id' => $cid]);

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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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
	 * @param boolean $force   Optional forcing of network probing (otherwise we use the cached data)
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbe($id, $network = '', $force = false)
	{
		/*
		  Warning: Never ever fetch the public key via Probe::uri and write it into the contacts.
		  This will reliably kill your communication with Friendica contacts.
		 */

		$fields = ['avatar', 'uid', 'name', 'nick', 'url', 'addr', 'batch', 'notify',
			'poll', 'request', 'confirm', 'poco', 'network', 'alias'];
		$contact = DBA::selectFirst('contact', $fields, ['id' => $id]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$uid = $contact['uid'];
		unset($contact['uid']);

		$contact['photo'] = $contact['avatar'];
		unset($contact['avatar']);

		$ret = Probe::uri($contact['url'], $network, $uid, !$force);

		// If Probe::uri fails the network code will be different (mostly "feed" or "unkn")
		if (in_array($ret['network'], [Protocol::FEED, Protocol::PHANTOM]) && ($ret['network'] != $contact['network'])) {
			return false;
		}

		if (!in_array($ret['network'], Protocol::NATIVE_SUPPORT)) {
			return false;
		}

		$update = false;

		// make sure to not overwrite existing values with blank entries
		foreach ($ret as $key => $val) {
			if (!isset($contact[$key])) {
				unset($ret[$key]);
			} elseif (($contact[$key] != '') && ($val == '')) {
				$ret[$key] = $contact[$key];
			} elseif ($ret[$key] != $contact[$key]) {
				$update = true;
			}
		}

		if (!$update) {
			return true;
		}

		$ret['nurl'] = Strings::normaliseLink($ret['url']);
		$ret['updated'] = DateTimeFormat::utcNow();

		self::updateAvatar($ret['photo'], $uid, $id, true);

		unset($ret['photo']);
		DBA::update('contact', $ret, ['id' => $id]);

		// Update the corresponding gcontact entry
		PortableContact::lastUpdated($ret["url"]);

		return true;
	}

	/**
	 * Detects if a given contact array belongs to a legacy DFRN connection
	 *
	 * @param array $contact
	 * @return boolean
	 */
	public static function isLegacyDFRNContact($contact)
	{
		// Newer Friendica contacts are connected via AP, then these fields aren't set
		return !empty($contact['dfrn-id']) || !empty($contact['issued-id']);
	}

	/**
	 * Detects the communication protocol for a given contact url.
	 * This is used to detect Friendica contacts that we can communicate via AP.
	 *
	 * @param string $url contact url
	 * @param string $network Network of that contact
	 * @return string with protocol
	 */
	public static function getProtocol($url, $network)
	{
		if ($network != Protocol::DFRN) {
			return $network;
		}

		$apcontact = APContact::getByURL($url);
		if (!empty($apcontact) && !empty($apcontact['generator'])) {
			return Protocol::ACTIVITYPUB;
		} else {
			return $network;
		}
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
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createFromProbe($uid, $url, $interactive = false, $network = '')
	{
		$result = ['cid' => -1, 'success' => false, 'message' => ''];

		$a = \get_app();

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

		Hook::callAll('follow', $arr);

		if (empty($arr)) {
			$result['message'] = L10n::t('The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.');
			return $result;
		}

		if (!empty($arr['contact']['name'])) {
			$ret = $arr['contact'];
		} else {
			$ret = Probe::uri($url, $network, $uid, false);
		}

		if (($network != '') && ($ret['network'] != $network)) {
			Logger::log('Expected network ' . $network . ' does not match actual network ' . $ret['network']);
			return $result;
		}

		// check if we already have a contact
		// the poll url is more reliable than the profile url, as we may have
		// indirect links or webfinger links

		$condition = ['uid' => $uid, 'poll' => [$ret['poll'], Strings::normaliseLink($ret['poll'])], 'network' => $ret['network'], 'pending' => false];
		$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		if (!DBA::isResult($contact)) {
			$condition = ['uid' => $uid, 'nurl' => Strings::normaliseLink($url), 'network' => $ret['network'], 'pending' => false];
			$contact = DBA::selectFirst('contact', ['id', 'rel'], $condition);
		}

		$protocol = self::getProtocol($url, $ret['network']);

		if (($protocol === Protocol::DFRN) && !DBA::isResult($contact)) {
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
			$result['message'] .= L10n::t('No compatible communication protocols or feeds were discovered.') . EOL;
			return $result;
		}

		// This extra param just confuses things, remove it
		if ($protocol === Protocol::DIASPORA) {
			$ret['url'] = str_replace('?absolute=true', '', $ret['url']);
		}

		// do we have enough information?
		if (empty($ret['name']) || empty($ret['poll']) || (empty($ret['url']) && empty($ret['addr']))) {
			$result['message'] .= L10n::t('The profile address specified does not provide adequate information.') . EOL;
			if (empty($ret['poll'])) {
				$result['message'] .= L10n::t('No compatible communication protocols or feeds were discovered.') . EOL;
			}
			if (empty($ret['name'])) {
				$result['message'] .= L10n::t('An author or name was not found.') . EOL;
			}
			if (empty($ret['url'])) {
				$result['message'] .= L10n::t('No browser URL could be matched to this address.') . EOL;
			}
			if (strpos($url, '@') !== false) {
				$result['message'] .= L10n::t('Unable to match @-style Identity Address with a known protocol or email contact.') . EOL;
				$result['message'] .= L10n::t('Use mailto: in front of address to force email check.') . EOL;
			}
			return $result;
		}

		if ($protocol === Protocol::OSTATUS && Config::get('system', 'ostatus_disabled')) {
			$result['message'] .= L10n::t('The profile address specified belongs to a network which has been disabled on this site.') . EOL;
			$ret['notify'] = '';
		}

		if (!$ret['notify']) {
			$result['message'] .= L10n::t('Limited profile. This person will be unable to receive direct/personal notifications from you.') . EOL;
		}

		$writeable = ((($protocol === Protocol::OSTATUS) && ($ret['notify'])) ? 1 : 0);

		$subhub = (($protocol === Protocol::OSTATUS) ? true : false);

		$hidden = (($protocol === Protocol::MAIL) ? 1 : 0);

		$pending = in_array($protocol, [Protocol::ACTIVITYPUB]);

		if (in_array($protocol, [Protocol::MAIL, Protocol::DIASPORA, Protocol::ACTIVITYPUB])) {
			$writeable = 1;
		}

		if (DBA::isResult($contact)) {
			// update contact
			$new_relation = (($contact['rel'] == self::FOLLOWER) ? self::FRIEND : self::SHARING);

			$fields = ['rel' => $new_relation, 'subhub' => $subhub, 'readonly' => false];
			DBA::update('contact', $fields, ['id' => $contact['id']]);
		} else {
			$new_relation = (in_array($protocol, [Protocol::MAIL]) ? self::FRIEND : self::SHARING);

			// create contact record
			DBA::insert('contact', [
				'uid'     => $uid,
				'created' => DateTimeFormat::utcNow(),
				'url'     => $ret['url'],
				'nurl'    => Strings::normaliseLink($ret['url']),
				'addr'    => $ret['addr'],
				'alias'   => $ret['alias'],
				'batch'   => $ret['batch'],
				'notify'  => $ret['notify'],
				'poll'    => $ret['poll'],
				'poco'    => $ret['poco'],
				'name'    => $ret['name'],
				'nick'    => $ret['nick'],
				'network' => $ret['network'],
				'protocol' => $protocol,
				'pubkey'  => $ret['pubkey'],
				'rel'     => $new_relation,
				'priority'=> $ret['priority'],
				'writable'=> $writeable,
				'hidden'  => $hidden,
				'blocked' => 0,
				'readonly'=> 0,
				'pending' => $pending,
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
			if (in_array($protocol, [Protocol::OSTATUS, Protocol::DFRN])) {
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
			} elseif ($protocol == Protocol::DIASPORA) {
				$ret = Diaspora::sendShare($a->user, $contact);
				Logger::log('share returns: ' . $ret);
			} elseif ($protocol == Protocol::ACTIVITYPUB) {
				$activity_id = ActivityPub\Transmitter::activityIDFromContact($contact_id);
				if (empty($activity_id)) {
					// This really should never happen
					return false;
				}

				$ret = ActivityPub\Transmitter::sendActivity('Follow', $contact['url'], $uid, $activity_id);
				Logger::log('Follow returns: ' . $ret);
			}
		}

		$result['success'] = true;
		return $result;
	}

	/**
	 * @brief Updated contact's SSL policy
	 *
	 * @param array  $contact    Contact array
	 * @param string $new_policy New policy, valid: self,full
	 *
	 * @return array Contact array with updated values
	 * @throws \Exception
	 */
	public static function updateSslPolicy(array $contact, $new_policy)
	{
		$ssl_changed = false;
		if ((intval($new_policy) == BaseURL::SSL_POLICY_SELFSIGN || $new_policy === 'self') && strstr($contact['url'], 'https:')) {
			$ssl_changed = true;
			$contact['url']     = 	str_replace('https:', 'http:', $contact['url']);
			$contact['request'] = 	str_replace('https:', 'http:', $contact['request']);
			$contact['notify']  = 	str_replace('https:', 'http:', $contact['notify']);
			$contact['poll']    = 	str_replace('https:', 'http:', $contact['poll']);
			$contact['confirm'] = 	str_replace('https:', 'http:', $contact['confirm']);
			$contact['poco']    = 	str_replace('https:', 'http:', $contact['poco']);
		}

		if ((intval($new_policy) == BaseURL::SSL_POLICY_FULL || $new_policy === 'full') && strstr($contact['url'], 'http:')) {
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

	/**
	 * @param array  $importer Owner (local user) data
	 * @param array  $contact  Existing owner-specific contact data we want to expand the relationship with. Optional.
	 * @param array  $datarray An item-like array with at least the 'author-id' and 'author-url' keys for the contact. Mandatory.
	 * @param bool   $sharing  True: Contact is now sharing with Owner; False: Contact is now following Owner (default)
	 * @param string $note     Introduction additional message
	 * @return bool|null True: follow request is accepted; False: relationship is rejected; Null: relationship is pending
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function addRelationship(array $importer, array $contact, array $datarray, $sharing = false, $note = '')
	{
		// Should always be set
		if (empty($datarray['author-id'])) {
			return false;
		}

		$fields = ['url', 'name', 'nick', 'photo', 'network', 'blocked'];
		$pub_contact = DBA::selectFirst('contact', $fields, ['id' => $datarray['author-id']]);
		if (!DBA::isResult($pub_contact)) {
			// Should never happen
			return false;
		}

		// Contact is blocked at node-level
		if (self::isBlocked($datarray['author-id'])) {
			return false;
		}

		$url = defaults($datarray, 'author-link', $pub_contact['url']);
		$name = $pub_contact['name'];
		$photo = $pub_contact['photo'];
		$nick = $pub_contact['nick'];
		$network = $pub_contact['network'];

		if (!empty($contact)) {
            // Contact is blocked at user-level
		    if (self::isBlockedByUser($contact['id'], $importer['id'])) {
		        return false;
            }

			// Make sure that the existing contact isn't archived
			self::unmarkForArchival($contact);

			if (($contact['rel'] == self::SHARING)
				|| ($sharing && $contact['rel'] == self::FOLLOWER)) {
				DBA::update('contact', ['rel' => self::FRIEND, 'writable' => true, 'pending' => false],
						['id' => $contact['id'], 'uid' => $importer['uid']]);
			}

			return true;
		} else {
			// send email notification to owner?
			if (DBA::exists('contact', ['nurl' => Strings::normaliseLink($url), 'uid' => $importer['uid'], 'pending' => true])) {
				Logger::log('ignoring duplicated connection request from pending contact ' . $url);
				return null;
			}

			// create contact record
			DBA::insert('contact', [
				'uid'      => $importer['uid'],
				'created'  => DateTimeFormat::utcNow(),
				'url'      => $url,
				'nurl'     => Strings::normaliseLink($url),
				'name'     => $name,
				'nick'     => $nick,
				'photo'    => $photo,
				'network'  => $network,
				'rel'      => self::FOLLOWER,
				'blocked'  => 0,
				'readonly' => 0,
				'pending'  => 1,
				'writable' => 1,
			]);

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
			if (DBA::isResult($user) && !in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				// create notification
				$hash = Strings::getRandomHex();

				if (is_array($contact_record)) {
					DBA::insert('intro', ['uid' => $importer['uid'], 'contact-id' => $contact_record['id'],
								'blocked' => false, 'knowyou' => false, 'note' => $note,
								'hash' => $hash, 'datetime' => DateTimeFormat::utcNow()]);
				}

				Group::addMember(User::getDefaultGroup($importer['uid'], $contact_record["network"]), $contact_record['id']);

				if (($user['notify-flags'] & NOTIFY_INTRO) &&
					in_array($user['page-flags'], [User::PAGE_FLAGS_NORMAL])) {

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
			} elseif (DBA::isResult($user) && in_array($user['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_FREELOVE, User::PAGE_FLAGS_COMMUNITY])) {
				$condition = ['uid' => $importer['uid'], 'url' => $url, 'pending' => true];
				DBA::update('contact', ['pending' => false], $condition);

				return true;
			}
		}

		return null;
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
		$condition = [
			'`bd` != ""
			AND `bd` > "0001-01-01"
			AND SUBSTRING(`bd`, 1, 4) != `bdyear`
			AND (`contact`.`rel` = ? OR `contact`.`rel` = ?)
			AND NOT `contact`.`pending`
			AND NOT `contact`.`hidden`
			AND NOT `contact`.`blocked`
			AND NOT `contact`.`archive`
			AND NOT `contact`.`deleted`',
			Contact::SHARING,
			Contact::FRIEND
		];

		$contacts = DBA::select('contact', ['id', 'uid', 'name', 'url', 'bd'], $condition);

		while ($contact = DBA::fetch($contacts)) {
			Logger::log('update_contact_birthday: ' . $contact['bd']);

			$nextbd = DateTimeFormat::utcNow('Y') . substr($contact['bd'], 4);

			if (Event::createBirthday($contact, $nextbd)) {
				// update bdyear
				DBA::update(
					'contact',
					['bdyear' => substr($nextbd, 0, 4), 'bd' => $nextbd],
					['id' => $contact['id']]
				);
			}
		}
	}

	/**
	 * Remove the unavailable contact ids from the provided list
	 *
	 * @param array $contact_ids Contact id list
	 * @throws \Exception
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
	 * @todo  check if the return is either a fully qualified URL or a relative path to Friendica basedir
	 *
	 * @param string $contact_url The address of the target contact profile
	 * @param string $url         An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLink($contact_url, $url = '')
	{
		if (!local_user() && !remote_user()) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		$data = self::getProbeDataFromDatabase($contact_url);
		if (empty($data)) {
			return $url ?: $contact_url; // Equivalent to: ($url != '') ? $url : $contact_url;
		}

		return self::magicLinkByContact($data, $contact_url);
	}

	/**
	 * @brief Returns a magic link to authenticate remote visitors
	 *
	 * @param integer $cid The contact id of the target contact profile
	 * @param string  $url An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkbyId($cid, $url = '')
	{
		$contact = DBA::selectFirst('contact', ['id', 'network', 'url', 'uid'], ['id' => $cid]);

		return self::magicLinkByContact($contact, $url);
	}

	/**
	 * @brief Returns a magic link to authenticate remote visitors
	 *
	 * @param array  $contact The contact array with "uid", "network" and "url"
	 * @param string $url     An url that we will be redirected to after the authentication
	 *
	 * @return string with "redir" link
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function magicLinkByContact($contact, $url = '')
	{
		if ((!local_user() && !remote_user()) || ($contact['network'] != Protocol::DFRN)) {
			return $url ?: $contact['url']; // Equivalent to ($url != '') ? $url : $contact['url'];
		}

		// Only redirections to the same host do make sense
		if (($url != '') && (parse_url($url, PHP_URL_HOST) != parse_url($contact['url'], PHP_URL_HOST))) {
			return $url;
		}

		if (!empty($contact['uid'])) {
			return self::magicLink($contact['url'], $url);
		}

		if (empty($contact['id'])) {
			return $url ?: $contact['url'];
		}

		$redirect = 'redir/' . $contact['id'];

		if ($url != '') {
			$redirect .= '?url=' . $url;
		}

		return $redirect;
	}

	/**
	 * Remove a contact from all groups
	 *
	 * @param integer $contact_id
	 *
	 * @return boolean Success
	 */
	public static function removeFromGroups($contact_id)
	{
		return DBA::delete('group_member', ['contact-id' => $contact_id]);
	}

	/**
	 * Is the contact a forum?
	 *
	 * @param integer $contactid ID of the contact
	 *
	 * @return boolean "true" if it is a forum
	 */
	public static function isForum($contactid)
	{
		$fields = ['forum', 'prv'];
		$condition = ['id' => $contactid];
		$contact = DBA::selectFirst('contact', $fields, $condition);
		if (!DBA::isResult($contact)) {
			return false;
		}

		// Is it a forum?
		return ($contact['forum'] || $contact['prv']);
	}
}

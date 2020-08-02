<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Protocol\ActivityPub;

use Friendica\Content\Feature;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Profile;
use Friendica\Model\Photo;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Images;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\XML;

require_once 'include/api.php';
require_once 'mod/share.php';

/**
 * ActivityPub Transmitter Protocol class
 *
 * To-Do:
 * @todo Undo Announce
 */
class Transmitter
{
	/**
	 * Collects a list of contacts of the given owner
	 *
	 * @param array     $owner  Owner array
	 * @param int|array $rel    The relevant value(s) contact.rel should match
	 * @param string    $module The name of the relevant AP endpoint module (followers|following)
	 * @param integer   $page   Page number
	 *
	 * @return array of owners
	 * @throws \Exception
	 */
	public static function getContacts($owner, $rel, $module, $page = null)
	{
		$parameters = [
			'rel' => $rel,
			'uid' => $owner['uid'],
			'self' => false,
			'deleted' => false,
			'hidden' => false,
			'archive' => false,
			'pending' => false
		];
		$condition = DBA::buildCondition($parameters);

		$sql = "SELECT COUNT(*) as `count`
			FROM `contact`
			JOIN `apcontact` ON `apcontact`.`url` = `contact`.`url`
			" . $condition;

		$contacts = DBA::fetchFirst($sql, ...$parameters);

		$modulePath = '/' . $module . '/';

		$data = ['@context' => ActivityPub::CONTEXT];
		$data['id'] = DI::baseUrl() . $modulePath . $owner['nickname'];
		$data['type'] = 'OrderedCollection';
		$data['totalItems'] = $contacts['count'];

		// When we hide our friends we will only show the pure number but don't allow more.
		$profile = Profile::getByUID($owner['uid']);
		if (!empty($profile['hide-friends'])) {
			return $data;
		}

		if (empty($page)) {
			$data['first'] = DI::baseUrl() . $modulePath . $owner['nickname'] . '?page=1';
		} else {
			$data['type'] = 'OrderedCollectionPage';
			$list = [];

			$sql = "SELECT `contact`.`url`
				FROM `contact`
				JOIN `apcontact` ON `apcontact`.`url` = `contact`.`url`
				" . $condition . "
				LIMIT ?, ?";

			$parameters[] = ($page - 1) * 100;
			$parameters[] = 100;

			$contacts = DBA::p($sql, ...$parameters);
			while ($contact = DBA::fetch($contacts)) {
				$list[] = $contact['url'];
			}
			DBA::close($contacts);

			if (!empty($list)) {
				$data['next'] = DI::baseUrl() . $modulePath . $owner['nickname'] . '?page=' . ($page + 1);
			}

			$data['partOf'] = DI::baseUrl() . $modulePath . $owner['nickname'];

			$data['orderedItems'] = $list;
		}

		return $data;
	}

	/**
	 * Public posts for the given owner
	 *
	 * @param array   $owner Owner array
	 * @param integer $page  Page numbe
	 *
	 * @return array of posts
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getOutbox($owner, $page = null)
	{
		$public_contact = Contact::getIdForURL($owner['url'], 0, false);

		$condition = ['uid' => 0, 'contact-id' => $public_contact, 'author-id' => $public_contact,
			'private' => [Item::PUBLIC, Item::UNLISTED], 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT],
			'deleted' => false, 'visible' => true, 'moderated' => false];
		$count = DBA::count('item', $condition);

		$data = ['@context' => ActivityPub::CONTEXT];
		$data['id'] = DI::baseUrl() . '/outbox/' . $owner['nickname'];
		$data['type'] = 'OrderedCollection';
		$data['totalItems'] = $count;

		if (empty($page)) {
			$data['first'] = DI::baseUrl() . '/outbox/' . $owner['nickname'] . '?page=1';
		} else {
			$data['type'] = 'OrderedCollectionPage';
			$list = [];

			$condition['parent-network'] = Protocol::NATIVE_SUPPORT;

			$items = Item::select(['id'], $condition, ['limit' => [($page - 1) * 20, 20], 'order' => ['created' => true]]);
			while ($item = Item::fetch($items)) {
				$activity = self::createActivityFromItem($item['id'], true);
				$activity['type'] = $activity['type'] == 'Update' ? 'Create' : $activity['type'];

				// Only list "Create" activity objects here, no reshares
				if (!empty($activity['object']) && ($activity['type'] == 'Create')) {
					$list[] = $activity['object'];
				}
			}

			if (!empty($list)) {
				$data['next'] = DI::baseUrl() . '/outbox/' . $owner['nickname'] . '?page=' . ($page + 1);
			}

			$data['partOf'] = DI::baseUrl() . '/outbox/' . $owner['nickname'];

			$data['orderedItems'] = $list;
		}

		return $data;
	}

	/**
	 * Return the service array containing information the used software and it's url
	 *
	 * @return array with service data
	 */
	private static function getService()
	{
		return ['type' => 'Service',
			'name' =>  FRIENDICA_PLATFORM . " '" . FRIENDICA_CODENAME . "' " . FRIENDICA_VERSION . '-' . DB_UPDATE_VERSION,
			'url' => DI::baseUrl()->get()];
	}

	/**
	 * Return the ActivityPub profile of the given user
	 *
	 * @param integer $uid User ID
	 * @return array with profile data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getProfile($uid)
	{
		$condition = ['uid' => $uid, 'blocked' => false, 'account_expired' => false,
			'account_removed' => false, 'verified' => true];
		$fields = ['guid', 'nickname', 'pubkey', 'account-type', 'page-flags'];
		$user = DBA::selectFirst('user', $fields, $condition);
		if (!DBA::isResult($user)) {
			return [];
		}

		$fields = ['locality', 'region', 'country-name'];
		$profile = DBA::selectFirst('profile', $fields, ['uid' => $uid]);
		if (!DBA::isResult($profile)) {
			return [];
		}

		$fields = ['name', 'url', 'location', 'about', 'avatar', 'photo'];
		$contact = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($contact)) {
			return [];
		}

		$data = ['@context' => ActivityPub::CONTEXT];
		$data['id'] = $contact['url'];
		$data['diaspora:guid'] = $user['guid'];
		$data['type'] = ActivityPub::ACCOUNT_TYPES[$user['account-type']];
		$data['following'] = DI::baseUrl() . '/following/' . $user['nickname'];
		$data['followers'] = DI::baseUrl() . '/followers/' . $user['nickname'];
		$data['inbox'] = DI::baseUrl() . '/inbox/' . $user['nickname'];
		$data['outbox'] = DI::baseUrl() . '/outbox/' . $user['nickname'];
		$data['preferredUsername'] = $user['nickname'];
		$data['name'] = $contact['name'];
		$data['vcard:hasAddress'] = ['@type' => 'vcard:Home', 'vcard:country-name' => $profile['country-name'],
			'vcard:region' => $profile['region'], 'vcard:locality' => $profile['locality']];
		$data['summary'] = BBCode::convert($contact['about'], false);
		$data['url'] = $contact['url'];
		$data['manuallyApprovesFollowers'] = in_array($user['page-flags'], [User::PAGE_FLAGS_NORMAL, User::PAGE_FLAGS_PRVGROUP]);
		$data['publicKey'] = ['id' => $contact['url'] . '#main-key',
			'owner' => $contact['url'],
			'publicKeyPem' => $user['pubkey']];
		$data['endpoints'] = ['sharedInbox' => DI::baseUrl() . '/inbox'];
		$data['icon'] = ['type' => 'Image',
			'url' => $contact['photo']];

		$data['generator'] = self::getService();

		// tags: https://kitty.town/@inmysocks/100656097926961126.json
		return $data;
	}

	/**
	 * @param string $username
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getDeletedUser($username)
	{
		return [
			'@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/profile/' . $username,
			'type' => 'Tombstone',
			'published' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'updated' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'deleted' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
		];
	}

	/**
	 * Returns an array with permissions of a given item array
	 *
	 * @param array $item
	 *
	 * @return array with permissions
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchPermissionBlockFromConversation($item)
	{
		if (empty($item['thr-parent'])) {
			return [];
		}

		$condition = ['item-uri' => $item['thr-parent'], 'protocol' => Conversation::PARCEL_ACTIVITYPUB];
		$conversation = DBA::selectFirst('conversation', ['source'], $condition);
		if (!DBA::isResult($conversation)) {
			return [];
		}

		$activity = json_decode($conversation['source'], true);

		$actor = JsonLD::fetchElement($activity, 'actor', 'id');
		$profile = APContact::getByURL($actor);

		$item_profile = APContact::getByURL($item['author-link']);
		$exclude[] = $item['author-link'];

		if ($item['gravity'] == GRAVITY_PARENT) {
			$exclude[] = $item['owner-link'];
		}

		$permissions['to'][] = $actor;

		foreach (['to', 'cc', 'bto', 'bcc'] as $element) {
			if (empty($activity[$element])) {
				continue;
			}
			if (is_string($activity[$element])) {
				$activity[$element] = [$activity[$element]];
			}

			foreach ($activity[$element] as $receiver) {
				if (empty($receiver)) {
					continue;
				}

				if (!empty($profile['followers']) && $receiver == $profile['followers'] && !empty($item_profile['followers'])) {
					$permissions[$element][] = $item_profile['followers'];
				} elseif (!in_array($receiver, $exclude)) {
					$permissions[$element][] = $receiver;
				}
			}
		}
		return $permissions;
	}

	/**
	 * Creates an array of permissions from an item thread
	 *
	 * @param array   $item       Item array
	 * @param boolean $blindcopy  addressing via "bcc" or "cc"?
	 * @param integer $last_id    Last item id for adding receivers
	 * @param boolean $forum_mode "true" means that we are sending content to a forum
	 *
	 * @return array with permission data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function createPermissionBlockForItem($item, $blindcopy, $last_id = 0, $forum_mode = false)
	{
		if ($last_id == 0) {
			$last_id = $item['id'];
		}

		$always_bcc = false;

		// Check if we should always deliver our stuff via BCC
		if (!empty($item['uid'])) {
			$profile = Profile::getByUID($item['uid']);
			if (!empty($profile)) {
				$always_bcc = $profile['hide-friends'];
			}
		}

		if (DI::config()->get('system', 'ap_always_bcc')) {
			$always_bcc = true;
		}

		if (self::isAnnounce($item) || DI::config()->get('debug', 'total_ap_delivery')) {
			// Will be activated in a later step
			$networks = Protocol::FEDERATED;
		} else {
			// For now only send to these contacts:
			$networks = [Protocol::ACTIVITYPUB, Protocol::OSTATUS];
		}

		$data = ['to' => [], 'cc' => [], 'bcc' => []];

		if ($item['gravity'] == GRAVITY_PARENT) {
			$actor_profile = APContact::getByURL($item['owner-link']);
		} else {
			$actor_profile = APContact::getByURL($item['author-link']);
		}

		$terms = Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION]);

		if ($item['private'] != Item::PRIVATE) {
			// Directly mention the original author upon a quoted reshare.
			// Else just ensure that the original author receives the reshare.
			$announce = self::getAnnounceArray($item);
			if (!empty($announce['comment'])) {
				$data['to'][] = $announce['actor']['url'];
			} elseif (!empty($announce)) {
				$data['cc'][] = $announce['actor']['url'];
			}

			$data = array_merge($data, self::fetchPermissionBlockFromConversation($item));

			// Check if the item is completely public or unlisted
			if ($item['private'] == Item::PUBLIC) {
				$data['to'][] = ActivityPub::PUBLIC_COLLECTION;
			} else {
				$data['cc'][] = ActivityPub::PUBLIC_COLLECTION;
			}

			foreach ($terms as $term) {
				$profile = APContact::getByURL($term['url'], false);
				if (!empty($profile)) {
					$data['to'][] = $profile['url'];
				}
			}
		} else {
			$receiver_list = Item::enumeratePermissions($item, true);

			foreach ($terms as $term) {
				$cid = Contact::getIdForURL($term['url'], $item['uid']);
				if (!empty($cid) && in_array($cid, $receiver_list)) {
					$contact = DBA::selectFirst('contact', ['url', 'network', 'protocol'], ['id' => $cid]);
					if (!DBA::isResult($contact) || (!in_array($contact['network'], $networks) && ($contact['protocol'] != Protocol::ACTIVITYPUB))) {
						continue;
					}

					if (!empty($profile = APContact::getByURL($contact['url'], false))) {
						$data['to'][] = $profile['url'];
					}
				}
			}

			foreach ($receiver_list as $receiver) {
				$contact = DBA::selectFirst('contact', ['url', 'hidden', 'network', 'protocol'], ['id' => $receiver]);
				if (!DBA::isResult($contact) || (!in_array($contact['network'], $networks) && ($contact['protocol'] != Protocol::ACTIVITYPUB))) {
					continue;
				}

				if (!empty($profile = APContact::getByURL($contact['url'], false))) {
					if ($contact['hidden'] || $always_bcc) {
						$data['bcc'][] = $profile['url'];
					} else {
						$data['cc'][] = $profile['url'];
					}
				}
			}
		}

		if (!empty($item['parent'])) {
			$parents = Item::select(['id', 'author-link', 'owner-link', 'gravity', 'uri'], ['parent' => $item['parent']]);
			while ($parent = Item::fetch($parents)) {
				if ($parent['gravity'] == GRAVITY_PARENT) {
					$profile = APContact::getByURL($parent['owner-link'], false);
					if (!empty($profile)) {
						if ($item['gravity'] != GRAVITY_PARENT) {
							// Comments to forums are directed to the forum
							// But comments to forums aren't directed to the followers collection
							if ($profile['type'] == 'Group') {
								$data['to'][] = $profile['url'];
							} else {
								$data['cc'][] = $profile['url'];
								if (($item['private'] != Item::PRIVATE) && !empty($actor_profile['followers'])) {
									$data['cc'][] = $actor_profile['followers'];
								}
							}
						} else {
							// Public thread parent post always are directed to the followers
							if (($item['private'] != Item::PRIVATE) && !$forum_mode) {
								$data['cc'][] = $actor_profile['followers'];
							}
						}
					}
				}

				// Don't include data from future posts
				if ($parent['id'] >= $last_id) {
					continue;
				}

				$profile = APContact::getByURL($parent['author-link'], false);
				if (!empty($profile)) {
					if (($profile['type'] == 'Group') || ($parent['uri'] == $item['thr-parent'])) {
						$data['to'][] = $profile['url'];
					} else {
						$data['cc'][] = $profile['url'];
					}
				}
			}
			DBA::close($parents);
		}

		$data['to'] = array_unique($data['to']);
		$data['cc'] = array_unique($data['cc']);
		$data['bcc'] = array_unique($data['bcc']);

		if (($key = array_search($item['author-link'], $data['to'])) !== false) {
			unset($data['to'][$key]);
		}

		if (($key = array_search($item['author-link'], $data['cc'])) !== false) {
			unset($data['cc'][$key]);
		}

		if (($key = array_search($item['author-link'], $data['bcc'])) !== false) {
			unset($data['bcc'][$key]);
		}

		foreach ($data['to'] as $to) {
			if (($key = array_search($to, $data['cc'])) !== false) {
				unset($data['cc'][$key]);
			}

			if (($key = array_search($to, $data['bcc'])) !== false) {
				unset($data['bcc'][$key]);
			}
		}

		foreach ($data['cc'] as $cc) {
			if (($key = array_search($cc, $data['bcc'])) !== false) {
				unset($data['bcc'][$key]);
			}
		}

		$receivers = ['to' => array_values($data['to']), 'cc' => array_values($data['cc']), 'bcc' => array_values($data['bcc'])];

		if (!$blindcopy) {
			unset($receivers['bcc']);
		}

		return $receivers;
	}

	/**
	 * Check if an inbox is archived
	 *
	 * @param string $url Inbox url
	 *
	 * @return boolean "true" if inbox is archived
	 */
	private static function archivedInbox($url)
	{
		return DBA::exists('inbox-status', ['url' => $url, 'archive' => true]);
	}

	/**
	 * Fetches a list of inboxes of followers of a given user
	 *
	 * @param integer $uid      User ID
	 * @param boolean $personal fetch personal inboxes
	 *
	 * @return array of follower inboxes
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchTargetInboxesforUser($uid, $personal = false)
	{
		$inboxes = [];

		if (DI::config()->get('debug', 'total_ap_delivery')) {
			// Will be activated in a later step
			$networks = Protocol::FEDERATED;
		} else {
			// For now only send to these contacts:
			$networks = [Protocol::ACTIVITYPUB, Protocol::OSTATUS];
		}

		$condition = ['uid' => $uid, 'archive' => false, 'pending' => false];

		if (!empty($uid)) {
			$condition['rel'] = [Contact::FOLLOWER, Contact::FRIEND];
		}

		$contacts = DBA::select('contact', ['url', 'network', 'protocol'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if (Contact::isLocal($contact['url'])) {
				continue;
			}

			if (!in_array($contact['network'], $networks) && ($contact['protocol'] != Protocol::ACTIVITYPUB)) {
				continue;
			}

			if (Network::isUrlBlocked($contact['url'])) {
				continue;
			}

			$profile = APContact::getByURL($contact['url'], false);
			if (!empty($profile)) {
				if (empty($profile['sharedinbox']) || $personal) {
					$target = $profile['inbox'];
				} else {
					$target = $profile['sharedinbox'];
				}
				if (!self::archivedInbox($target)) {
					$inboxes[$target] = $target;
				}
			}
		}
		DBA::close($contacts);

		return $inboxes;
	}

	/**
	 * Fetches an array of inboxes for the given item and user
	 *
	 * @param array   $item       Item array
	 * @param integer $uid        User ID
	 * @param boolean $personal   fetch personal inboxes
	 * @param integer $last_id    Last item id for adding receivers
	 * @param boolean $forum_mode "true" means that we are sending content to a forum
	 * @return array with inboxes
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchTargetInboxes($item, $uid, $personal = false, $last_id = 0, $forum_mode = false)
	{
		$permissions = self::createPermissionBlockForItem($item, true, $last_id, $forum_mode);
		if (empty($permissions)) {
			return [];
		}

		$inboxes = [];

		if ($item['gravity'] == GRAVITY_ACTIVITY) {
			$item_profile = APContact::getByURL($item['author-link'], false);
		} else {
			$item_profile = APContact::getByURL($item['owner-link'], false);
		}

		foreach (['to', 'cc', 'bto', 'bcc'] as $element) {
			if (empty($permissions[$element])) {
				continue;
			}

			$blindcopy = in_array($element, ['bto', 'bcc']);

			foreach ($permissions[$element] as $receiver) {
				if (empty($receiver) || Network::isUrlBlocked($receiver)) {
					continue;
				}

				if ($item_profile && $receiver == $item_profile['followers']) {
					$inboxes = array_merge($inboxes, self::fetchTargetInboxesforUser($uid, $personal));
				} else {
					if (Contact::isLocal($receiver)) {
						continue;
					}

					$profile = APContact::getByURL($receiver, false);
					if (!empty($profile)) {
						if (empty($profile['sharedinbox']) || $personal || $blindcopy) {
							$target = $profile['inbox'];
						} else {
							$target = $profile['sharedinbox'];
						}
						if (!self::archivedInbox($target)) {
							$inboxes[$target] = $target;
						}
					}
				}
			}
		}

		return $inboxes;
	}

	/**
	 * Creates an array in the structure of the item table for a given mail id
	 *
	 * @param integer $mail_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function ItemArrayFromMail($mail_id)
	{
		$mail = DBA::selectFirst('mail', [], ['id' => $mail_id]);
		if (!DBA::isResult($mail)) {
			return [];
		}

		$mail['uri-id'] = ItemURI::insert(['uri' => $mail['uri'], 'guid' => $mail['guid']]);

		$reply = DBA::selectFirst('mail', ['uri'], ['parent-uri' => $mail['parent-uri'], 'reply' => false]);

		// Making the post more compatible for Mastodon by:
		// - Making it a note and not an article (no title)
		// - Moving the title into the "summary" field that is used as a "content warning"
		$mail['body'] = '[abstract]' . $mail['title'] . "[/abstract]\n" . $mail['body'];
		$mail['title'] = '';

		$mail['author-link'] = $mail['owner-link'] = $mail['from-url'];
		$mail['allow_cid'] = '<'.$mail['contact-id'].'>';
		$mail['allow_gid'] = '';
		$mail['deny_cid'] = '';
		$mail['deny_gid'] = '';
		$mail['private'] = true;
		$mail['deleted'] = false;
		$mail['edited'] = $mail['created'];
		$mail['plink'] = $mail['uri'];
		$mail['thr-parent'] = $reply['uri'];
		$mail['gravity'] = ($mail['reply'] ? GRAVITY_COMMENT: GRAVITY_PARENT);

		$mail['event-type'] = '';
		$mail['attach'] = '';

		$mail['parent'] = 0;

		return $mail;
	}

	/**
	 * Creates an activity array for a given mail id
	 *
	 * @param integer $mail_id
	 * @param boolean $object_mode Is the activity item is used inside another object?
	 *
	 * @return array of activity
	 * @throws \Exception
	 */
	public static function createActivityFromMail($mail_id, $object_mode = false)
	{
		$mail = self::ItemArrayFromMail($mail_id);
		$object = self::createNote($mail);

		if (!$object_mode) {
			$data = ['@context' => ActivityPub::CONTEXT];
		} else {
			$data = [];
		}

		$data['id'] = $mail['uri'] . '/Create';
		$data['type'] = 'Create';
		$data['actor'] = $mail['author-link'];
		$data['published'] = DateTimeFormat::utc($mail['created'] . '+00:00', DateTimeFormat::ATOM);
		$data['instrument'] = self::getService();
		$data = array_merge($data, self::createPermissionBlockForItem($mail, true));

		if (empty($data['to']) && !empty($data['cc'])) {
			$data['to'] = $data['cc'];
		}

		if (empty($data['to']) && !empty($data['bcc'])) {
			$data['to'] = $data['bcc'];
		}

		unset($data['cc']);
		unset($data['bcc']);

		$object['to'] = $data['to'];
		$object['tag'] = [['type' => 'Mention', 'href' => $object['to'][0], 'name' => '']];

		unset($object['cc']);
		unset($object['bcc']);

		$data['directMessage'] = true;

		$data['object'] = $object;

		$owner = User::getOwnerDataById($mail['uid']);

		if (!$object_mode && !empty($owner)) {
			return LDSignature::sign($data, $owner);
		} else {
			return $data;
		}
	}

	/**
	 * Returns the activity type of a given item
	 *
	 * @param array $item
	 *
	 * @return string with activity type
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function getTypeOfItem($item)
	{
		$reshared = false;

		// Only check for a reshare, if it is a real reshare and no quoted reshare
		if (strpos($item['body'], "[share") === 0) {
			$announce = self::getAnnounceArray($item);
			$reshared = !empty($announce);
		}

		if ($reshared) {
			$type = 'Announce';
		} elseif ($item['verb'] == Activity::POST) {
			if ($item['created'] == $item['edited']) {
				$type = 'Create';
			} else {
				$type = 'Update';
			}
		} elseif ($item['verb'] == Activity::LIKE) {
			$type = 'Like';
		} elseif ($item['verb'] == Activity::DISLIKE) {
			$type = 'Dislike';
		} elseif ($item['verb'] == Activity::ATTEND) {
			$type = 'Accept';
		} elseif ($item['verb'] == Activity::ATTENDNO) {
			$type = 'Reject';
		} elseif ($item['verb'] == Activity::ATTENDMAYBE) {
			$type = 'TentativeAccept';
		} elseif ($item['verb'] == Activity::FOLLOW) {
			$type = 'Follow';
		} elseif ($item['verb'] == Activity::TAG) {
			$type = 'Add';
		} else {
			$type = '';
		}

		return $type;
	}

	/**
	 * Creates the activity or fetches it from the cache
	 *
	 * @param integer $item_id
	 * @param boolean $force Force new cache entry
	 *
	 * @return array with the activity
	 * @throws \Exception
	 */
	public static function createCachedActivityFromItem($item_id, $force = false)
	{
		$cachekey = 'APDelivery:createActivity:' . $item_id;

		if (!$force) {
			$data = DI::cache()->get($cachekey);
			if (!is_null($data)) {
				return $data;
			}
		}

		$data = self::createActivityFromItem($item_id);

		DI::cache()->set($cachekey, $data, Duration::QUARTER_HOUR);
		return $data;
	}

	/**
	 * Creates an activity array for a given item id
	 *
	 * @param integer $item_id
	 * @param boolean $object_mode Is the activity item is used inside another object?
	 *
	 * @return array of activity
	 * @throws \Exception
	 */
	public static function createActivityFromItem($item_id, $object_mode = false)
	{
		$item = Item::selectFirst([], ['id' => $item_id, 'parent-network' => Protocol::NATIVE_SUPPORT]);

		if (!DBA::isResult($item)) {
			return false;
		}

		if ($item['wall'] && ($item['uri'] == $item['parent-uri']) && !$object_mode) {
			$owner = User::getOwnerDataById($item['uid']);
			if (($owner['account-type'] == User::ACCOUNT_TYPE_COMMUNITY) && ($item['author-link'] != $owner['url'])) {
				$type = 'Announce';

				// Disguise forum posts as reshares. Will later be converted to a real announce
				$item['body'] = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'],
					$item['plink'], $item['created'], $item['guid']) . $item['body'] . '[/share]';
			}
		}

		if (empty($type)) {
			$condition = ['item-uri' => $item['uri'], 'protocol' => Conversation::PARCEL_ACTIVITYPUB];
			$conversation = DBA::selectFirst('conversation', ['source'], $condition);
			if (DBA::isResult($conversation)) {
				$data = json_decode($conversation['source'], true);
				if (!empty($data['type'])) {
					if (in_array($data['type'], ['Create', 'Update'])) {
						if ($object_mode) {
							unset($data['@context']);
							unset($data['signature']);
						}
						return $data;
					} elseif (in_array('as:' . $data['type'], Receiver::CONTENT_TYPES)) {
						if (!empty($data['@context'])) {
							$context = $data['@context'];
							unset($data['@context']);
						}
						unset($data['actor']);
						$object = $data;
					}
				}
			}

			$type = self::getTypeOfItem($item);
		}

		if (!$object_mode) {
			$data = ['@context' => $context ?? ActivityPub::CONTEXT];

			if ($item['deleted'] && ($item['gravity'] == GRAVITY_ACTIVITY)) {
				$type = 'Undo';
			} elseif ($item['deleted']) {
				$type = 'Delete';
			}
		} else {
			$data = [];
		}

		$data['id'] = $item['uri'] . '/' . $type;
		$data['type'] = $type;

		if (Item::isForumPost($item) && ($type != 'Announce')) {
			$data['actor'] = $item['author-link'];
		} else {
			$data['actor'] = $item['owner-link'];
		}

		$data['published'] = DateTimeFormat::utc($item['created'] . '+00:00', DateTimeFormat::ATOM);

		$data['instrument'] = self::getService();

		$data = array_merge($data, self::createPermissionBlockForItem($item, false));

		if (in_array($data['type'], ['Create', 'Update', 'Delete'])) {
			$data['object'] = $object ?? self::createNote($item);
		} elseif ($data['type'] == 'Add') {
			$data = self::createAddTag($item, $data);
		} elseif ($data['type'] == 'Announce') {
			$data = self::createAnnounce($item, $data);
		} elseif ($data['type'] == 'Follow') {
			$data['object'] = $item['parent-uri'];
		} elseif ($data['type'] == 'Undo') {
			$data['object'] = self::createActivityFromItem($item_id, true);
		} else {
			$data['diaspora:guid'] = $item['guid'];
			if (!empty($item['signed_text'])) {
				$data['diaspora:like'] = $item['signed_text'];
			}
			$data['object'] = $item['thr-parent'];
		}

		if (!empty($item['contact-uid'])) {
			$uid = $item['contact-uid'];
		} else {
			$uid = $item['uid'];
		}

		$owner = User::getOwnerDataById($uid);

		if (!$object_mode && !empty($owner)) {
			return LDSignature::sign($data, $owner);
		} else {
			return $data;
		}

		/// @todo Create "conversation" entry
	}

	/**
	 * Creates a location entry for a given item array
	 *
	 * @param array $item
	 *
	 * @return array with location array
	 */
	private static function createLocation($item)
	{
		$location = ['type' => 'Place'];

		if (!empty($item['location'])) {
			$location['name'] = $item['location'];
		}

		$coord = [];

		if (empty($item['coord'])) {
			$coord = Map::getCoordinates($item['location']);
		} else {
			$coords = explode(' ', $item['coord']);
			if (count($coords) == 2) {
				$coord = ['lat' => $coords[0], 'lon' => $coords[1]];
			}
		}

		if (!empty($coord['lat']) && !empty($coord['lon'])) {
			$location['latitude'] = $coord['lat'];
			$location['longitude'] = $coord['lon'];
		}

		return $location;
	}

	/**
	 * Returns a tag array for a given item array
	 *
	 * @param array $item
	 *
	 * @return array of tags
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function createTagList($item)
	{
		$tags = [];

		$terms = Tag::getByURIId($item['uri-id'], [Tag::HASHTAG, Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION]);
		foreach ($terms as $term) {
			if ($term['type'] == Tag::HASHTAG) {
				$url = DI::baseUrl() . '/search?tag=' . urlencode($term['name']);
				$tags[] = ['type' => 'Hashtag', 'href' => $url, 'name' => '#' . $term['name']];
			} else {
				$contact = Contact::getByURL($term['url'], false, ['addr']);
				if (!empty($contact['addr'])) {
					$mention = '@' . $contact['addr'];
				} else {
					$mention = '@' . $term['url'];
				}

				$tags[] = ['type' => 'Mention', 'href' => $term['url'], 'name' => $mention];
			}
		}

		$announce = self::getAnnounceArray($item);
		// Mention the original author upon commented reshares
		if (!empty($announce['comment'])) {
			$tags[] = ['type' => 'Mention', 'href' => $announce['actor']['url'], 'name' => '@' . $announce['actor']['addr']];
		}

		return $tags;
	}

	/**
	 * Adds attachment data to the JSON document
	 *
	 * @param array  $item Data of the item that is to be posted
	 * @param string $type Object type
	 *
	 * @return array with attachment data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function createAttachmentList($item, $type)
	{
		$attachments = [];

		// Currently deactivated, since it creates side effects on Mastodon and Pleroma.
		// It will be reactivated, once this cleared.
		/*
		$attach_data = BBCode::getAttachmentData($item['body']);
		if (!empty($attach_data['url'])) {
			$attachment = ['type' => 'Page',
				'mediaType' => 'text/html',
				'url' => $attach_data['url']];

			if (!empty($attach_data['title'])) {
				$attachment['name'] = $attach_data['title'];
			}

			if (!empty($attach_data['description'])) {
				$attachment['summary'] = $attach_data['description'];
			}

			if (!empty($attach_data['image'])) {
				$imgdata = Images::getInfoFromURLCached($attach_data['image']);
				if ($imgdata) {
					$attachment['icon'] = ['type' => 'Image',
						'mediaType' => $imgdata['mime'],
						'width' => $imgdata[0],
						'height' => $imgdata[1],
						'url' => $attach_data['image']];
				}
			}

			$attachments[] = $attachment;
		}
		*/
		$arr = explode('[/attach],', $item['attach']);
		if (count($arr)) {
			foreach ($arr as $r) {
				$matches = false;
				$cnt = preg_match('|\[attach\]href=\"(.*?)\" length=\"(.*?)\" type=\"(.*?)\" title=\"(.*?)\"|', $r, $matches);
				if ($cnt) {
					$attributes = ['type' => 'Document',
							'mediaType' => $matches[3],
							'url' => $matches[1],
							'name' => null];

					if (trim($matches[4]) != '') {
						$attributes['name'] = trim($matches[4]);
					}

					$attachments[] = $attributes;
				}
			}
		}

		if ($type != 'Note') {
			return $attachments;
		}

		// Simplify image codes
		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $item['body']);

		// Grab all pictures without alternative descriptions and create attachments out of them
		if (preg_match_all("/\[img\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures)) {
			foreach ($pictures[1] as $picture) {
				$imgdata = Images::getInfoFromURLCached($picture);
				if ($imgdata) {
					$attachments[] = ['type' => 'Document',
						'mediaType' => $imgdata['mime'],
						'url' => $picture,
						'name' => null];
				}
			}
		}

		// Grab all pictures with alternative description and create attachments out of them
		if (preg_match_all("/\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]/Usi", $body, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				$imgdata = Images::getInfoFromURLCached($picture[1]);
				if ($imgdata) {
					$attachments[] = ['type' => 'Document',
						'mediaType' => $imgdata['mime'],
						'url' => $picture[1],
						'name' => $picture[2]];
				}
			}
		}

		return $attachments;
	}

	/**
	 * Callback function to replace a Friendica style mention in a mention that is used on AP
	 *
	 * @param array $match Matching values for the callback
	 * @return string Replaced mention
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function mentionCallback($match)
	{
		if (empty($match[1])) {
			return '';
		}

		$data = Contact::getByURL($match[1], false, ['url', 'nick']);
		if (empty($data['nick'])) {
			return $match[0];
		}

		return '@[url=' . $data['url'] . ']' . $data['nick'] . '[/url]';
	}

	/**
	 * Remove image elements since they are added as attachment
	 *
	 * @param string $body
	 *
	 * @return string with removed images
	 */
	private static function removePictures($body)
	{
		// Simplify image codes
		$body = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $body);
		$body = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", '[img]$1[/img]', $body);

		// Now remove local links
		$body = preg_replace_callback(
			'/\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]/Usi',
			function ($match) {
				// We remove the link when it is a link to a local photo page
				if (Photo::isLocalPage($match[1])) {
					return '';
				}
				// otherwise we just return the link
				return '[url]' . $match[1] . '[/url]';
			},
			$body
		);

		// Remove all pictures
		$body = preg_replace("/\[img\]([^\[\]]*)\[\/img\]/Usi", '', $body);

		return $body;
	}

	/**
	 * Fetches the "context" value for a givem item array from the "conversation" table
	 *
	 * @param array $item
	 *
	 * @return string with context url
	 * @throws \Exception
	 */
	private static function fetchContextURLForItem($item)
	{
		$conversation = DBA::selectFirst('conversation', ['conversation-href', 'conversation-uri'], ['item-uri' => $item['parent-uri']]);
		if (DBA::isResult($conversation) && !empty($conversation['conversation-href'])) {
			$context_uri = $conversation['conversation-href'];
		} elseif (DBA::isResult($conversation) && !empty($conversation['conversation-uri'])) {
			$context_uri = $conversation['conversation-uri'];
		} else {
			$context_uri = $item['parent-uri'] . '#context';
		}
		return $context_uri;
	}

	/**
	 * Returns if the post contains sensitive content ("nsfw")
	 *
	 * @param integer $uri_id
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	private static function isSensitive($uri_id)
	{
		return DBA::exists('tag-view', ['uri-id' => $uri_id, 'name' => 'nsfw']);
	}

	/**
	 * Creates event data
	 *
	 * @param array $item
	 *
	 * @return array with the event data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function createEvent($item)
	{
		$event = [];
		$event['name'] = $item['event-summary'];
		$event['content'] = BBCode::convert($item['event-desc'], false, BBCode::ACTIVITYPUB);
		$event['startTime'] = DateTimeFormat::utc($item['event-start'] . '+00:00', DateTimeFormat::ATOM);

		if (!$item['event-nofinish']) {
			$event['endTime'] = DateTimeFormat::utc($item['event-finish'] . '+00:00', DateTimeFormat::ATOM);
		}

		if (!empty($item['event-location'])) {
			$item['location'] = $item['event-location'];
			$event['location'] = self::createLocation($item);
		}

		return $event;
	}

	/**
	 * Creates a note/article object array
	 *
	 * @param array $item
	 *
	 * @return array with the object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function createNote($item)
	{
		if (empty($item)) {
			return [];
		}

		if ($item['event-type'] == 'event') {
			$type = 'Event';
		} elseif (!empty($item['title'])) {
			$type = 'Article';
		} else {
			$type = 'Note';
		}

		if ($item['deleted']) {
			$type = 'Tombstone';
		}

		$data = [];
		$data['id'] = $item['uri'];
		$data['type'] = $type;

		if ($item['deleted']) {
			return $data;
		}

		$data['summary'] = BBCode::toPlaintext(BBCode::getAbstract($item['body'], Protocol::ACTIVITYPUB));

		if ($item['uri'] != $item['thr-parent']) {
			$data['inReplyTo'] = $item['thr-parent'];
		} else {
			$data['inReplyTo'] = null;
		}

		$data['diaspora:guid'] = $item['guid'];
		$data['published'] = DateTimeFormat::utc($item['created'] . '+00:00', DateTimeFormat::ATOM);

		if ($item['created'] != $item['edited']) {
			$data['updated'] = DateTimeFormat::utc($item['edited'] . '+00:00', DateTimeFormat::ATOM);
		}

		$data['url'] = $item['plink'];
		$data['attributedTo'] = $item['author-link'];
		$data['sensitive'] = self::isSensitive($item['uri-id']);
		$data['context'] = self::fetchContextURLForItem($item);

		if (!empty($item['title'])) {
			$data['name'] = BBCode::toPlaintext($item['title'], false);
		}

		$permission_block = self::createPermissionBlockForItem($item, false);

		$body = $item['body'];

		if (empty($item['uid']) || !Feature::isEnabled($item['uid'], 'explicit_mentions')) {
			$body = self::prependMentions($body, $item['uri-id']);
		}

		if ($type == 'Note') {
			$body = self::removePictures($body);
		} elseif (($type == 'Article') && empty($data['summary'])) {
			$data['summary'] = BBCode::toPlaintext(Plaintext::shorten(self::removePictures($body), 1000));
		}

		if ($type == 'Event') {
			$data = array_merge($data, self::createEvent($item));
		} else {
			$regexp = "/[@!]\[url\=([^\[\]]*)\].*?\[\/url\]/ism";
			$body = preg_replace_callback($regexp, ['self', 'mentionCallback'], $body);

			$data['content'] = BBCode::convert($body, false, BBCode::ACTIVITYPUB);
		}

		// The regular "content" field does contain a minimized HTML. This is done since systems like
		// Mastodon has got problems with - for example - embedded pictures.
		// The contentMap does contain the unmodified HTML.
		$language = self::getLanguage($item);
		if (!empty($language)) {
			$regexp = "/[@!]\[url\=([^\[\]]*)\].*?\[\/url\]/ism";
			$richbody = preg_replace_callback($regexp, ['self', 'mentionCallback'], $item['body']);
			$richbody = BBCode::removeAttachment($richbody);

			$data['contentMap'][$language] = BBCode::convert($richbody, false);
		}

		$data['source'] = ['content' => $item['body'], 'mediaType' => "text/bbcode"];

		if (!empty($item['signed_text']) && ($item['uri'] != $item['thr-parent'])) {
			$data['diaspora:comment'] = $item['signed_text'];
		}

		$data['attachment'] = self::createAttachmentList($item, $type);
		$data['tag'] = self::createTagList($item);

		if (empty($data['location']) && (!empty($item['coord']) || !empty($item['location']))) {
			$data['location'] = self::createLocation($item);
		}

		if (!empty($item['app'])) {
			$data['generator'] = ['type' => 'Application', 'name' => $item['app']];
		}

		$data = array_merge($data, $permission_block);

		return $data;
	}

	/**
	 * Fetches the language from the post, the user or the system.
	 *
	 * @param array $item
	 *
	 * @return string language string
	 */
	private static function getLanguage(array $item)
	{
		// Try to fetch the language from the post itself
		if (!empty($item['language'])) {
			$languages = array_keys(json_decode($item['language'], true));
			if (!empty($languages[0])) {
				return $languages[0];
			}
		}

		// Otherwise use the user's language
		if (!empty($item['uid'])) {
			$user = DBA::selectFirst('user', ['language'], ['uid' => $item['uid']]);
			if (!empty($user['language'])) {
				return $user['language'];
			}
		}

		// And finally just use the system language
		return DI::config()->get('system', 'language');
	}

	/**
	 * Creates an an "add tag" entry
	 *
	 * @param array $item
	 * @param array $data activity data
	 *
	 * @return array with activity data for adding tags
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function createAddTag($item, $data)
	{
		$object = XML::parseString($item['object']);
		$target = XML::parseString($item["target"]);

		$data['diaspora:guid'] = $item['guid'];
		$data['actor'] = $item['author-link'];
		$data['target'] = (string)$target->id;
		$data['summary'] = BBCode::toPlaintext($item['body']);
		$data['object'] = ['id' => (string)$object->id, 'type' => 'tag', 'name' => (string)$object->title, 'content' => (string)$object->content];

		return $data;
	}

	/**
	 * Creates an announce object entry
	 *
	 * @param array $item
	 * @param array $data activity data
	 *
	 * @return array with activity data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function createAnnounce($item, $data)
	{
		$orig_body = $item['body'];
		$announce = self::getAnnounceArray($item);
		if (empty($announce)) {
			$data['type'] = 'Create';
			$data['object'] = self::createNote($item);
			return $data;
		}

		if (empty($announce['comment'])) {
			// Pure announce, without a quote
			$data['type'] = 'Announce';
			$data['object'] = $announce['object']['uri'];
			return $data;
		}

		// Quote
		$data['type'] = 'Create';
		$item['body'] = $announce['comment'] . "\n" . $announce['object']['plink'];
		$data['object'] = self::createNote($item);

		/// @todo Finally descide how to implement this in AP. This is a possible way:
		$data['object']['attachment'][] = self::createNote($announce['object']);

		$data['object']['source']['content'] = $orig_body;
		return $data;
	}

	/**
	 * Return announce related data if the item is an annunce
	 *
	 * @param array $item
	 *
	 * @return array
	 */
	public static function getAnnounceArray($item)
	{
		$reshared = Item::getShareArray($item);
		if (empty($reshared['guid'])) {
			return [];
		}

		$reshared_item = Item::selectFirst([], ['guid' => $reshared['guid']]);
		if (!DBA::isResult($reshared_item)) {
			return [];
		}

		if (!in_array($reshared_item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN])) {
			return [];
		}

		$profile = APContact::getByURL($reshared_item['author-link'], false);
		if (empty($profile)) {
			return [];
		}

		return ['object' => $reshared_item, 'actor' => $profile, 'comment' => $reshared['comment']];
	}

	/**
	 * Checks if the provided item array is an announce
	 *
	 * @param array $item
	 *
	 * @return boolean
	 */
	public static function isAnnounce($item)
	{
		$announce = self::getAnnounceArray($item);
		if (empty($announce)) {
			return false;
		}

		return empty($announce['comment']);
	}

	/**
	 * Creates an activity id for a given contact id
	 *
	 * @param integer $cid Contact ID of target
	 *
	 * @return bool|string activity id
	 */
	public static function activityIDFromContact($cid)
	{
		$contact = DBA::selectFirst('contact', ['uid', 'id', 'created'], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return false;
		}

		$hash = hash('ripemd128', $contact['uid'].'-'.$contact['id'].'-'.$contact['created']);
		$uuid = substr($hash, 0, 8). '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);
		return DI::baseUrl() . '/activity/' . $uuid;
	}

	/**
	 * Transmits a contact suggestion to a given inbox
	 *
	 * @param integer $uid           User ID
	 * @param string  $inbox         Target inbox
	 * @param integer $suggestion_id Suggestion ID
	 *
	 * @return boolean was the transmission successful?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function sendContactSuggestion($uid, $inbox, $suggestion_id)
	{
		$owner = User::getOwnerDataById($uid);

		$suggestion = DI::fsuggest()->getById($suggestion_id);

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Announce',
			'actor' => $owner['url'],
			'object' => $suggestion->url,
			'content' => $suggestion->note,
			'instrument' => self::getService(),
			'to' => [ActivityPub::PUBLIC_COLLECTION],
			'cc' => []];

		$signed = LDSignature::sign($data, $owner);

		Logger::log('Deliver profile deletion for user ' . $uid . ' to ' . $inbox . ' via ActivityPub', Logger::DEBUG);
		return HTTPSignature::transmit($signed, $inbox, $uid);
	}

	/**
	 * Transmits a profile relocation to a given inbox
	 *
	 * @param integer $uid   User ID
	 * @param string  $inbox Target inbox
	 *
	 * @return boolean was the transmission successful?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function sendProfileRelocation($uid, $inbox)
	{
		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'dfrn:relocate',
			'actor' => $owner['url'],
			'object' => $owner['url'],
			'published' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'instrument' => self::getService(),
			'to' => [ActivityPub::PUBLIC_COLLECTION],
			'cc' => []];

		$signed = LDSignature::sign($data, $owner);

		Logger::log('Deliver profile relocation for user ' . $uid . ' to ' . $inbox . ' via ActivityPub', Logger::DEBUG);
		return HTTPSignature::transmit($signed, $inbox, $uid);
	}

	/**
	 * Transmits a profile deletion to a given inbox
	 *
	 * @param integer $uid   User ID
	 * @param string  $inbox Target inbox
	 *
	 * @return boolean was the transmission successful?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function sendProfileDeletion($uid, $inbox)
	{
		$owner = User::getOwnerDataById($uid);

		if (empty($owner)) {
			Logger::error('No owner data found, the deletion message cannot be processed.', ['user' => $uid]);
			return false;
		}

		if (empty($owner['uprvkey'])) {
			Logger::error('No private key for owner found, the deletion message cannot be processed.', ['user' => $uid]);
			return false;
		}

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Delete',
			'actor' => $owner['url'],
			'object' => $owner['url'],
			'published' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'instrument' => self::getService(),
			'to' => [ActivityPub::PUBLIC_COLLECTION],
			'cc' => []];

		$signed = LDSignature::sign($data, $owner);

		Logger::log('Deliver profile deletion for user ' . $uid . ' to ' . $inbox . ' via ActivityPub', Logger::DEBUG);
		return HTTPSignature::transmit($signed, $inbox, $uid);
	}

	/**
	 * Transmits a profile change to a given inbox
	 *
	 * @param integer $uid   User ID
	 * @param string  $inbox Target inbox
	 *
	 * @return boolean was the transmission successful?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendProfileUpdate($uid, $inbox)
	{
		$owner = User::getOwnerDataById($uid);
		$profile = APContact::getByURL($owner['url']);

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Update',
			'actor' => $owner['url'],
			'object' => self::getProfile($uid),
			'published' => DateTimeFormat::utcNow(DateTimeFormat::ATOM),
			'instrument' => self::getService(),
			'to' => [$profile['followers']],
			'cc' => []];

		$signed = LDSignature::sign($data, $owner);

		Logger::log('Deliver profile update for user ' . $uid . ' to ' . $inbox . ' via ActivityPub', Logger::DEBUG);
		return HTTPSignature::transmit($signed, $inbox, $uid);
	}

	/**
	 * Transmits a given activity to a target
	 *
	 * @param string  $activity Type name
	 * @param string  $target   Target profile
	 * @param integer $uid      User ID
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @throws \Exception
	 */
	public static function sendActivity($activity, $target, $uid, $id = '')
	{
		$profile = APContact::getByURL($target);
		if (empty($profile['inbox'])) {
			Logger::warning('No inbox found for target', ['target' => $target, 'profile' => $profile]);
			return;
		}

		$owner = User::getOwnerDataById($uid);

		if (empty($id)) {
			$id = DI::baseUrl() . '/activity/' . System::createGUID();
		}

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => $id,
			'type' => $activity,
			'actor' => $owner['url'],
			'object' => $profile['url'],
			'instrument' => self::getService(),
			'to' => [$profile['url']]];

		Logger::log('Sending activity ' . $activity . ' to ' . $target . ' for user ' . $uid, Logger::DEBUG);

		$signed = LDSignature::sign($data, $owner);
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * Transmits a "follow object" activity to a target
	 * This is a preparation for sending automated "follow" requests when receiving "Announce" messages
	 *
	 * @param string  $object Object URL
	 * @param string  $target Target profile
	 * @param integer $uid    User ID
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @throws \Exception
	 */
	public static function sendFollowObject($object, $target, $uid = 0)
	{
		$profile = APContact::getByURL($target);
		if (empty($profile['inbox'])) {
			Logger::warning('No inbox found for target', ['target' => $target, 'profile' => $profile]);
			return;
		}

		if (empty($uid)) {
			// Fetch the list of administrators
			$admin_mail = explode(',', str_replace(' ', '', DI::config()->get('config', 'admin_email')));

			// We need to use some user as a sender. It doesn't care who it will send. We will use an administrator account.
			$condition = ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false, 'email' => $admin_mail];
			$first_user = DBA::selectFirst('user', ['uid'], $condition);
			$uid = $first_user['uid'];
		}

		$condition = ['verb' => Activity::FOLLOW, 'uid' => 0, 'parent-uri' => $object,
			'author-id' => Contact::getPublicIdByUserId($uid)];
		if (Item::exists($condition)) {
			Logger::log('Follow for ' . $object . ' for user ' . $uid . ' does already exist.', Logger::DEBUG);
			return false;
		}

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Follow',
			'actor' => $owner['url'],
			'object' => $object,
			'instrument' => self::getService(),
			'to' => [$profile['url']]];

		Logger::log('Sending follow ' . $object . ' to ' . $target . ' for user ' . $uid, Logger::DEBUG);

		$signed = LDSignature::sign($data, $owner);
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * Transmit a message that the contact request had been accepted
	 *
	 * @param string  $target Target profile
	 * @param         $id
	 * @param integer $uid    User ID
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendContactAccept($target, $id, $uid)
	{
		$profile = APContact::getByURL($target);
		if (empty($profile['inbox'])) {
			Logger::warning('No inbox found for target', ['target' => $target, 'profile' => $profile]);
			return;
		}

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Accept',
			'actor' => $owner['url'],
			'object' => [
				'id' => (string)$id,
				'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']
			],
			'instrument' => self::getService(),
			'to' => [$profile['url']]];

		Logger::debug('Sending accept to ' . $target . ' for user ' . $uid . ' with id ' . $id);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * Reject a contact request or terminates the contact relation
	 *
	 * @param string  $target Target profile
	 * @param         $id
	 * @param integer $uid    User ID
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendContactReject($target, $id, $uid)
	{
		$profile = APContact::getByURL($target);
		if (empty($profile['inbox'])) {
			Logger::warning('No inbox found for target', ['target' => $target, 'profile' => $profile]);
			return;
		}

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => DI::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Reject',
			'actor' => $owner['url'],
			'object' => [
				'id' => (string)$id,
				'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']
			],
			'instrument' => self::getService(),
			'to' => [$profile['url']]];

		Logger::debug('Sending reject to ' . $target . ' for user ' . $uid . ' with id ' . $id);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * Transmits a message that we don't want to follow this contact anymore
	 *
	 * @param string  $target Target profile
	 * @param integer $uid    User ID
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @throws \Exception
	 */
	public static function sendContactUndo($target, $cid, $uid)
	{
		$profile = APContact::getByURL($target);
		if (empty($profile['inbox'])) {
			Logger::warning('No inbox found for target', ['target' => $target, 'profile' => $profile]);
			return;
		}

		$object_id = self::activityIDFromContact($cid);
		if (empty($object_id)) {
			return;
		}

		$id = DI::baseUrl() . '/activity/' . System::createGUID();

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => ActivityPub::CONTEXT,
			'id' => $id,
			'type' => 'Undo',
			'actor' => $owner['url'],
			'object' => ['id' => $object_id, 'type' => 'Follow',
				'actor' => $owner['url'],
				'object' => $profile['url']],
			'instrument' => self::getService(),
			'to' => [$profile['url']]];

		Logger::log('Sending undo to ' . $target . ' for user ' . $uid . ' with id ' . $id, Logger::DEBUG);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	private static function prependMentions($body, int $uriid)
	{
		$mentions = [];

		foreach (Tag::getByURIId($uriid, [Tag::IMPLICIT_MENTION]) as $tag) {
			$profile = Contact::getByURL($tag['url'], false, ['addr', 'contact-type', 'nick']);
			if (!empty($profile['addr'])
				&& $profile['contact-type'] != Contact::TYPE_COMMUNITY
				&& !strstr($body, $profile['addr'])
				&& !strstr($body, $tag['url'])
			) {
				$mentions[] = '@[url=' . $tag['url'] . ']' . $profile['nick'] . '[/url]';
			}
		}

		$mentions[] = $body;

		return implode(' ', $mentions);
	}
}

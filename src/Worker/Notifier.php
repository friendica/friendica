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

namespace Friendica\Worker;

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\PushSubscriber;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/*
 * The notifier is typically called with:
 *
 *		Worker::add(PRIORITY_HIGH, "Notifier", COMMAND, ITEM_ID);
 *
 * where COMMAND is one of the constants that are defined in Worker/Delivery.php
 * and ITEM_ID is the id of the item in the database that needs to be sent to others.
 */

class Notifier
{
	public static function execute(string $cmd, int $post_uriid, int $sender_uid = 0)
	{
		$a = DI::app();

		Logger::info('Invoked', ['cmd' => $cmd, 'target' => $post_uriid, 'sender_uid' => $sender_uid]);

		$target_id = $post_uriid;
		$top_level = false;
		$recipients = [];
		$url_recipients = [];

		$delivery_contacts_stmt = null;
		$target_item = [];
		$parent = [];
		$thr_parent = [];
		$items = [];
		$delivery_queue_count = 0;
		$ap_contacts = [];

		if ($cmd == Delivery::MAIL) {
			$message = DBA::selectFirst('mail', ['uid', 'contact-id'], ['id' => $target_id]);
			if (!DBA::isResult($message)) {
				return;
			}
			$uid = $message['uid'];
			$recipients[] = $message['contact-id'];

			$mail = ActivityPub\Transmitter::getItemArrayFromMail($target_id);
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($mail, $uid, true);
			foreach ($inboxes as $inbox => $receivers) {
				$ap_contacts = array_merge($ap_contacts, $receivers);
				Logger::info('Delivery via ActivityPub', ['cmd' => $cmd, 'target' => $target_id, 'inbox' => $inbox]);
				Worker::add(['priority' => Worker::PRIORITY_HIGH, 'created' => $a->getQueueValue('created'), 'dont_fork' => true],
					'APDelivery', $cmd, $target_id, $inbox, $uid, $receivers, $post_uriid);
			}
		} elseif ($cmd == Delivery::SUGGESTION) {
			$suggest = DI::fsuggest()->selectOneById($target_id);
			$uid = $suggest->uid;
			$recipients[] = $suggest->cid;
		} elseif ($cmd == Delivery::REMOVAL) {
			return self::notifySelfRemoval($target_id, $a->getQueueValue('priority'), $a->getQueueValue('created'));
		} elseif ($cmd == Delivery::RELOCATION) {
			$uid = $target_id;

			$condition = ['uid' => $target_id, 'self' => false, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
			$delivery_contacts_stmt = DBA::select('contact', ['id', 'url', 'addr', 'network', 'protocol', 'batch'], $condition);
		} else {
			$post = Post::selectFirst(['id'], ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
			if (!DBA::isResult($post)) {
				Logger::warning('Post not found', ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
				return;
			}
			$target_id = $post['id'];

			// find ancestors
			$condition = ['id' => $target_id, 'visible' => true];
			$target_item = Post::selectFirst(Item::DELIVER_FIELDLIST, $condition);

			if (!DBA::isResult($target_item) || !intval($target_item['parent'])) {
				Logger::info('No target item', ['cmd' => $cmd, 'target' => $target_id]);
				return;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				Logger::info('Only public users, quitting', ['target' => $target_id]);
				return;
			}

			$condition = ['parent' => $target_item['parent'], 'visible' => true];
			$params = ['order' => ['id']];
			$items_stmt = Post::select(Item::DELIVER_FIELDLIST, $condition, $params);
			if (!DBA::isResult($items_stmt)) {
				Logger::info('No item found', ['cmd' => $cmd, 'target' => $target_id]);
				return;
			}

			$items = Post::toArray($items_stmt);

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			$top_level = $target_item['gravity'] == Item::GRAVITY_PARENT;
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			Logger::info('Owner not found', ['cmd' => $cmd, 'target' => $target_id]);
			return;
		}

		// Should the post be transmitted to Diaspora?
		$diaspora_delivery = ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		// If this is a public conversation, notify the feed hub
		$public_message = true;

		$unlisted = false;

		// Do a PuSH
		$push_notify = false;

		// Deliver directly to a forum, don't PuSH
		$direct_forum_delivery = false;

		$followup = false;
		$recipients_followup = [];

		if (!empty($target_item) && !empty($items)) {
			$parent = $items[0];

			$fields = ['network', 'author-id', 'author-link', 'author-network', 'owner-id'];
			$condition = ['uri' => $target_item['thr-parent'], 'uid' => $target_item['uid']];
			$thr_parent = Post::selectFirst($fields, $condition);
			if (empty($thr_parent)) {
				$thr_parent = $parent;
			}

			Logger::info('Got post', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'network' => $target_item['network'], 'parent-network' => $parent['network'], 'thread-parent-network' => $thr_parent['network']]);

			if (!self::isRemovalActivity($cmd, $owner, Protocol::ACTIVITYPUB)) {
				$apdelivery = self::activityPubDelivery($cmd, $target_item, $parent, $thr_parent, $a->getQueueValue('priority'), $a->getQueueValue('created'), $owner);
				$ap_contacts = $apdelivery['contacts'];
				$delivery_queue_count += $apdelivery['count'];
			}

			// Only deliver threaded replies (comment to a comment) to Diaspora
			// when the original comment author does support the Diaspora protocol.
			if ($thr_parent['author-link'] && $target_item['parent-uri'] != $target_item['thr-parent']) {
				$diaspora_delivery = Diaspora::isSupportedByContactUrl($thr_parent['author-link']);
				if ($diaspora_delivery &&  empty($target_item['signed_text'])) {
					Logger::debug('Post has got no Diaspora signature, so there will be no Diaspora delivery', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id']]);
					$diaspora_delivery = false;
				}
				Logger::info('Threaded comment', ['diaspora_delivery' => (int)$diaspora_delivery]);
			}

			$unlisted = $target_item['private'] == Item::UNLISTED;

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.

			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = str_replace('www.','', DI::baseUrl()->getHostname());
			if (strpos($localhost,':')) {
				$localhost = substr($localhost,0,strpos($localhost,':'));
			}
			/**
			 *
			 * Be VERY CAREFUL if you make any changes to the following several lines. Seemingly innocuous changes
			 * have been known to cause runaway conditions which affected several servers, along with
			 * permissions issues.
			 *
			 */

			$relay_to_owner = false;

			if (!$top_level && ($parent['wall'] == 0) && (stristr($target_item['uri'],$localhost))) {
				$relay_to_owner = true;
			}

			// until the 'origin' flag has been in use for several months
			// we will just use it as a fallback test
			// later we will be able to use it as the primary test of whether or not to relay.

			if (!$target_item['origin']) {
				$relay_to_owner = false;
			}
			if ($parent['origin']) {
				$relay_to_owner = false;
			}

			// Special treatment for forum posts
			if (Item::isForumPost($target_item['uri-id'])) {
				$relay_to_owner = true;
				$direct_forum_delivery = true;
			}

			// Avoid that comments in a forum thread are sent to OStatus
			if (Item::isForumPost($parent['uri-id'])) {
				$direct_forum_delivery = true;
			}

			$exclusive_delivery = false;

			$exclusive_targets = Tag::getByURIId($parent['uri-id'], [Tag::EXCLUSIVE_MENTION]);
			if (!empty($exclusive_targets)) {
				$exclusive_delivery = true;
				Logger::info('Possible Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id']]);
				foreach ($exclusive_targets as $target) {
					if (Strings::compareLink($owner['url'], $target['url'])) {
						$exclusive_delivery = false;
						Logger::info('False Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'url' => $target['url']]);
					}
				}
			}

			if ($relay_to_owner) {
				// local followup to remote post
				$followup = true;
				$public_message = false; // not public
				$recipients = [$parent['contact-id']];
				$recipients_followup  = [$parent['contact-id']];

				Logger::info('Followup', ['target' => $target_id, 'guid' => $target_item['guid'], 'to' => $parent['contact-id']]);

				if (($target_item['private'] != Item::PRIVATE) &&
					(strlen($target_item['allow_cid'].$target_item['allow_gid'].
						$target_item['deny_cid'].$target_item['deny_gid']) == 0))
					$push_notify = true;

				if (($thr_parent && ($thr_parent['network'] == Protocol::OSTATUS)) || ($parent['network'] == Protocol::OSTATUS)) {
					$push_notify = true;

					if ($parent["network"] == Protocol::OSTATUS) {
						// Distribute the message to the DFRN contacts as if this wasn't a followup since OStatus can't relay comments
						// Currently it is work at progress
						$condition = ['uid' => $uid, 'network' => Protocol::DFRN, 'blocked' => false, 'pending' => false, 'archive' => false];
						$followup_contacts_stmt = DBA::select('contact', ['id'], $condition);
						while($followup_contact = DBA::fetch($followup_contacts_stmt)) {
							$recipients_followup[] = $followup_contact['id'];
						}
						DBA::close($followup_contacts_stmt);
					}
				}

				if ($direct_forum_delivery) {
					$push_notify = false;
				}

				Logger::info('Notify ' . $target_item["guid"] .' via PuSH: ' . ($push_notify ? "Yes":"No"));
			} elseif ($exclusive_delivery) {
				$followup = true;

				foreach ($exclusive_targets as $target) {
					$cid = Contact::getIdForURL($target['url'], $uid, false);
					if ($cid) {
						$recipients_followup[] = $cid;
						Logger::info('Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'url' => $target['url']]);
					}
				}
			} else {
				$followup = false;

				Logger::info('Distributing directly', ['target' => $target_id, 'guid' => $target_item['guid']]);

				// don't send deletions onward for other people's stuff

				if ($target_item['deleted'] && !intval($target_item['wall'])) {
					Logger::notice('Ignoring delete notification for non-wall item');
					return;
				}

				if (strlen($parent['allow_cid'])
					|| strlen($parent['allow_gid'])
					|| strlen($parent['deny_cid'])
					|| strlen($parent['deny_gid'])) {
					$public_message = false; // private recipients, not public
				}

				$aclFormatter = DI::aclFormatter();

				$allow_people = $aclFormatter->expand($parent['allow_cid']);
				$allow_groups = Group::expand($uid, $aclFormatter->expand($parent['allow_gid']),true);
				$deny_people  = $aclFormatter->expand($parent['deny_cid']);
				$deny_groups  = Group::expand($uid, $aclFormatter->expand($parent['deny_gid']));

				foreach ($items as $item) {
					$recipients[] = $item['contact-id'];
					// pull out additional tagged people to notify (if public message)
					if ($public_message && $item['inform']) {
						$people = explode(',',$item['inform']);
						foreach ($people as $person) {
							if (substr($person,0,4) === 'cid:') {
								$recipients[] = intval(substr($person,4));
							} else {
								$url_recipients[] = substr($person,4);
							}
						}
					}
				}

				if (count($url_recipients)) {
					Logger::notice('Deliver', ['target' => $target_id, 'guid' => $target_item['guid'], 'recipients' => $url_recipients]);
				}

				$recipients = array_unique(array_merge($recipients, $allow_people, $allow_groups));
				$deny = array_unique(array_merge($deny_people, $deny_groups));
				$recipients = array_diff($recipients, $deny);

				// If this is a public message and pubmail is set on the parent, include all your email contacts
				if (
					function_exists('imap_open')
					&& !DI::config()->get('system','imap_disabled')
					&& $public_message
					&& intval($target_item['pubmail'])
				) {
					$mail_contacts_stmt = DBA::select('contact', ['id'], ['uid' => $uid, 'network' => Protocol::MAIL]);
					while ($mail_contact = DBA::fetch($mail_contacts_stmt)) {
						$recipients[] = $mail_contact['id'];
					}
					DBA::close($mail_contacts_stmt);
				}
			}

			// If the thread parent is OStatus then do some magic to distribute the messages.
			// We have not only to look at the parent, since it could be a Friendica thread.
			if (($thr_parent && ($thr_parent['network'] == Protocol::OSTATUS)) || ($parent['network'] == Protocol::OSTATUS)) {
				$diaspora_delivery = false;

				Logger::info('Some parent is OStatus for ' . $target_item['guid'] . ' - Author: ' . $thr_parent['author-id'] . ' - Owner: ' . $thr_parent['owner-id']);

				// Send a salmon to the parent author
				$probed_contact = DBA::selectFirst('contact', ['url', 'notify'], ['id' => $thr_parent['author-id']]);
				if (DBA::isResult($probed_contact) && !empty($probed_contact['notify'])) {
					Logger::notice('Notify parent author', ['url' => $probed_contact['url'], 'notify' => $probed_contact['notify']]);
					$url_recipients[$probed_contact['notify']] = $probed_contact['notify'];
				}

				// Send a salmon to the parent owner
				$probed_contact = DBA::selectFirst('contact', ['url', 'notify'], ['id' => $thr_parent['owner-id']]);
				if (DBA::isResult($probed_contact) && !empty($probed_contact['notify'])) {
					Logger::notice('Notify parent owner', ['url' => $probed_contact['url'], 'notify' => $probed_contact['notify']]);
					$url_recipients[$probed_contact['notify']] = $probed_contact['notify'];
				}

				// Send a salmon notification to every person we mentioned in the post
				foreach (Tag::getByURIId($target_item['uri-id'], [Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION]) as $tag) {
					$probed_contact = Contact::getByURL($tag['url']);
					if (!empty($probed_contact['notify'])) {
						Logger::notice('Notify mentioned user', ['url' => $probed_contact['url'], 'notify' => $probed_contact['notify']]);
						$url_recipients[$probed_contact['notify']] = $probed_contact['notify'];
					}
				}

				// It only makes sense to distribute answers to OStatus messages to Friendica and OStatus - but not Diaspora
				$networks = [Protocol::DFRN];
			} elseif ($diaspora_delivery) {
				$networks = [Protocol::DFRN, Protocol::DIASPORA, Protocol::MAIL];
				if (($parent['network'] == Protocol::DIASPORA) || ($thr_parent['network'] == Protocol::DIASPORA)) {
					Logger::info('Add AP contacts', ['target' => $target_id, 'guid' => $target_item['guid']]);
					$networks[] = Protocol::ACTIVITYPUB;
				}
			} else {
				$networks = [Protocol::DFRN, Protocol::MAIL];
			}
		} else {
			$public_message = false;
		}

		if (empty($delivery_contacts_stmt)) {
			if ($followup) {
				$recipients = $recipients_followup;
			}
			$condition = ['id' => $recipients, 'self' => false, 'uid' => [0, $uid],
				'blocked' => false, 'pending' => false, 'archive' => false];
			if (!empty($networks)) {
				$condition['network'] = $networks;
			}
			$delivery_contacts_stmt = DBA::select('contact', ['id', 'addr', 'url', 'network', 'protocol', 'batch'], $condition);
		}

		$conversants = [];
		$batch_delivery = false;

		if ($public_message && !in_array($cmd, [Delivery::MAIL, Delivery::SUGGESTION]) && !$followup) {
			$participants = [];

			if ($diaspora_delivery && !$unlisted) {
				$batch_delivery = true;

				$participants = DBA::selectToArray('contact', ['batch', 'network', 'protocol', 'id', 'url', 'name'],
					["`network` = ? AND `batch` != '' AND `uid` = ? AND `rel` != ? AND NOT `blocked` AND NOT `pending` AND NOT `archive`", Protocol::DIASPORA, $owner['uid'], Contact::SHARING],
					['group_by' => ['batch', 'network', 'protocol']]);

				// Fetch the participation list
				// The function will ensure that there are no duplicates
				$participants = Diaspora::participantsForThread($target_item, $participants);
			}

			$condition = ['network' => Protocol::DFRN, 'uid' => $owner['uid'], 'blocked' => false,
				'pending' => false, 'archive' => false, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]];

			$contacts = DBA::selectToArray('contact', ['id', 'url', 'addr', 'name', 'network', 'protocol'], $condition);

			$conversants = array_merge($contacts, $participants);

			$delivery_queue_count += self::delivery($cmd, $post_uriid, $sender_uid, $target_item, $thr_parent, $owner, $batch_delivery, true, $conversants, $ap_contacts, []);

			$push_notify = true;
		}

		$contacts = DBA::toArray($delivery_contacts_stmt);
		$delivery_queue_count += self::delivery($cmd, $post_uriid, $sender_uid, $target_item, $thr_parent, $owner, $batch_delivery, false, $contacts, $ap_contacts, $conversants);

		$delivery_queue_count += self::deliverOStatus($target_id, $target_item, $owner, $url_recipients, $public_message, $push_notify);

		if (!empty($target_item)) {
			Logger::info('Calling hooks for ' . $cmd . ' ' . $target_id);

			Hook::fork($a->getQueueValue('priority'), 'notifier_normal', $target_item);

			Hook::callAll('notifier_end', $target_item);

			// Workaround for pure connector posts
			if ($cmd == Delivery::POST) {
				if ($delivery_queue_count == 0) {
					Post\DeliveryData::incrementQueueDone($target_item['uri-id']);
					$delivery_queue_count = 1;
				}

				Post\DeliveryData::incrementQueueCount($target_item['uri-id'], $delivery_queue_count);
			}
		}

		return;
	}

	/**
	 * Deliver the message to the contacts
	 *
	 * @param string $cmd
	 * @param int $post_uriid
	 * @param int $sender_uid
	 * @param array $target_item
	 * @param array $thr_parent
	 * @param array $owner
	 * @param bool $batch_delivery
	 * @param array $contacts
	 * @param array $ap_contacts
	 * @param array $conversants
	 *
	 * @return int Count of delivery queue
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	private static function delivery(string $cmd, int $post_uriid, int $sender_uid, array $target_item, array $thr_parent, array $owner, bool $batch_delivery, bool $in_batch, array $contacts, array $ap_contacts, array $conversants = []): int
	{
		$a = DI::app();
		$delivery_queue_count = 0;

		if (!empty($target_item['verb']) && ($target_item['verb'] == Activity::ANNOUNCE)) {
			Logger::notice('Announces are only delivery via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
			return 0;
		}

		foreach ($contacts as $contact) {
			// Direct delivery of local contacts
			if (!in_array($cmd, [Delivery::RELOCATION, Delivery::SUGGESTION, Delivery::DELETION, Delivery::MAIL]) && $target_uid = User::getIdForURL($contact['url'])) {
				if ($target_item['origin'] || ($target_item['network'] != Protocol::ACTIVITYPUB)) {
					if ($target_uid != $target_item['uid']) {
						$fields = ['protocol' => Conversation::PARCEL_LOCAL_DFRN, 'direction' => Conversation::PUSH, 'post-reason' => Item::PR_DIRECT];
						Item::storeForUserByUriId($target_item['uri-id'], $target_uid, $fields, $target_item['uid']);
						Logger::info('Delivered locally', ['cmd' => $cmd, 'id' => $target_item['id'], 'target' => $target_uid]);
					} else {
						Logger::info('No need to deliver to myself', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
					}
				} else {
					Logger::info('Remote item does not need to be delivered locally', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
				}
				continue;
			}

			// Deletions are always sent via DFRN as well.
			// This is done until we can perform deletions of foreign comments on our own threads via AP.
			if (($cmd != Delivery::DELETION) && in_array($contact['id'], $ap_contacts)) {
				Logger::info('Contact is already delivered via AP, so skip delivery via legacy DFRN/Diaspora', ['target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (!empty($contact['id']) && Contact::isArchived($contact['id'])) {
				Logger::info('Contact is archived, so skip delivery', ['target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (self::isRemovalActivity($cmd, $owner, $contact['network'])) {
				Logger::info('Contact does no supports account removal commands, so skip delivery', ['target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (self::skipActivityPubForDiaspora($contact, $target_item, $thr_parent)) {
				Logger::info('Contact is from Diaspora, but the replied author is from ActivityPub, so skip delivery via Diaspora', ['id' => $post_uriid, 'uid' => $sender_uid, 'url' => $contact['url']]);
				continue;
			}

			// Don't deliver to Diaspora if it already had been done as batch delivery
			if (!$in_batch && $batch_delivery && ($contact['network'] == Protocol::DIASPORA)) {
				Logger::info('Diaspora contact is already delivered via batch', ['id' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact]);
				continue;
			}

			// Don't deliver to folks who have already been delivered to
			if (in_array($contact['id'], $conversants)) {
				Logger::info('Already delivery', ['id' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact]);
				continue;
			}

			Logger::info('Delivery', ['batch' => $in_batch, 'target' => $post_uriid, 'uid' => $sender_uid, 'guid' => $target_item['guid'] ?? '', 'to' => $contact]);

			// Ensure that posts with our own protocol arrives before Diaspora posts arrive.
			// Situation is that sometimes Friendica servers receive Friendica posts over the Diaspora protocol first.
			// The conversion in Markdown reduces the formatting, so these posts should arrive after the Friendica posts.
			// This is only important for high and medium priority tasks and not for Low priority jobs like deletions.
			if (($contact['network'] == Protocol::DIASPORA) && in_array($a->getQueueValue('priority'), [Worker::PRIORITY_HIGH, Worker::PRIORITY_MEDIUM])) {
				$deliver_options = ['priority' => $a->getQueueValue('priority'), 'dont_fork' => true];
			} else {
				$deliver_options = ['priority' => $a->getQueueValue('priority'), 'created' => $a->getQueueValue('created'), 'dont_fork' => true];
			}

			if (Worker::add($deliver_options, 'Delivery', $cmd, $post_uriid, (int)$contact['id'], $sender_uid)) {
				$delivery_queue_count++;
			}
			Worker::coolDown();
		}
		return $delivery_queue_count;
	}

	/**
	 * Deliver the message via OStatus
	 *
	 * @param int $target_id
	 * @param array $target_item
	 * @param array $owner
	 * @param array $url_recipients
	 * @param bool $public_message
	 * @param bool $push_notify
	 *
	 * @return int Count of sent Salmon notifications
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	private static function deliverOStatus(int $target_id, array $target_item, array $owner, array $url_recipients, bool $public_message, bool $push_notify): int
	{
		$a = DI::app();
		$delivery_queue_count = 0;

		$url_recipients = array_filter($url_recipients);
		// send salmon slaps to mentioned remote tags (@foo@example.com) in OStatus posts
		// They are especially used for notifications to OStatus users that don't follow us.
		if (count($url_recipients) && ($public_message || $push_notify) && !empty($target_item)) {
			$slap = OStatus::salmon($target_item, $owner);
			foreach ($url_recipients as $url) {
				Logger::info('Salmon delivery', ['item' => $target_id, 'to' => $url]);

				$delivery_queue_count++;
				Salmon::slapper($owner, $url, $slap);
				Post\DeliveryData::incrementQueueDone($target_item['uri-id'], Post\DeliveryData::OSTATUS);
			}
		}

		// Notify PuSH subscribers (Used for OStatus distribution of regular posts)
		if ($push_notify) {
			Logger::info('Activating internal PuSH', ['uid' => $owner['uid']]);

			// Handling the pubsubhubbub requests
			PushSubscriber::publishFeed($owner['uid'], $a->getQueueValue('priority'));
		}
		return $delivery_queue_count;
	}

	/**
	 * Checks if the current delivery shouldn't be transported to Diaspora.
	 * This is done for posts from AP authors or posts that are comments to AP authors.
	 *
	 * @param array  $contact    Receiver of the post
	 * @param array  $item       The post
	 * @param array  $thr_parent The thread parent
	 *
	 * @return bool
	 */
	private static function skipActivityPubForDiaspora(array $contact, array $item, array $thr_parent): bool
	{
		// No skipping needs to be done when delivery isn't done to Diaspora
		if ($contact['network'] != Protocol::DIASPORA) {
			return false;
		}

		// Skip the delivery to Diaspora if the item is from an ActivityPub author
		if (!empty($item['author-network']) && ($item['author-network'] == Protocol::ACTIVITYPUB)) {
			return true;
		}

		// Skip the delivery to Diaspora if the thread parent is from an ActivityPub author
		if (!empty($thr_parent['author-network']) && ($thr_parent['author-network'] == Protocol::ACTIVITYPUB)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the current action is a deletion command of a account removal activity
	 * For Diaspora and ActivityPub we don't need to send single item deletion calls.
	 * These protocols do have a dedicated command for deleting a whole account.
	 *
	 * @param string $cmd     Notifier command
	 * @param array  $owner   Sender of the post
	 * @param string $network Receiver network
	 *
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function isRemovalActivity(string $cmd, array $owner, string $network): bool
	{
		return ($cmd == Delivery::DELETION) && $owner['account_removed'] && in_array($network, [Protocol::ACTIVITYPUB, Protocol::DIASPORA]);
	}

	/**
	 * @param int    $self_user_id
	 * @param int    $priority The priority the Notifier queue item was created with
	 * @param string $created  The date the Notifier queue item was created on
	 *
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function notifySelfRemoval(int $self_user_id, int $priority, string $created): bool
	{
		$owner = User::getOwnerDataById($self_user_id);
		if (empty($self_user_id) || empty($owner)) {
			return false;
		}

		$contacts_stmt = DBA::select('contact', [], ['self' => false, 'uid' => $self_user_id]);
		if (!DBA::isResult($contacts_stmt)) {
			return false;
		}

		while($contact = DBA::fetch($contacts_stmt)) {
			Contact::terminateFriendship($contact);
		}
		DBA::close($contacts_stmt);

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($self_user_id);
		foreach ($inboxes as $inbox => $receivers) {
			Logger::info('Account removal via ActivityPub', ['uid' => $self_user_id, 'inbox' => $inbox]);
			Worker::add(['priority' => Worker::PRIORITY_NEGLIGIBLE, 'created' => $created, 'dont_fork' => true],
				'APDelivery', Delivery::REMOVAL, 0, $inbox, $self_user_id, $receivers);
			Worker::coolDown();
		}

		return true;
	}

	/**
	 * @param string $cmd
	 * @param array  $target_item
	 * @param array  $parent
	 * @param array  $thr_parent
	 * @param int    $priority The priority the Notifier queue item was created with
	 * @param string $created  The date the Notifier queue item was created on
	 *
	 * @return array 'count' => The number of delivery tasks created, 'contacts' => their contact ids
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo Unused parameter $owner
	 */
	private static function activityPubDelivery($cmd, array $target_item, array $parent, array $thr_parent, int $priority, string $created, $owner): array
	{
		// Don't deliver via AP when the starting post isn't from a federated network
		if (!in_array($parent['network'], Protocol::FEDERATED)) {
			Logger::info('Parent network is no federated network, so no AP delivery', ['network' => $parent['network']]);
			return ['count' => 0, 'contacts' => []];
		}

		// Don't deliver via AP when the starting post is delivered via Diaspora
		if ($parent['network'] == Protocol::DIASPORA) {
			Logger::info('Parent network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		// Also don't deliver when the direct thread parent was delivered via Diaspora
		if ($thr_parent['network'] == Protocol::DIASPORA) {
			Logger::info('Thread parent network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		// Posts from Diaspora contacts are transmitted via Diaspora
		if ($target_item['network'] == Protocol::DIASPORA) {
			Logger::info('Post network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		$inboxes = [];
		$relay_inboxes = [];

		$uid = $target_item['contact-uid'] ?: $target_item['uid'];

		// Update the locally stored follower list when we deliver to a forum
		foreach (Tag::getByURIId($target_item['uri-id'], [Tag::MENTION, Tag::EXCLUSIVE_MENTION]) as $tag) {
			$target_contact = Contact::getByURL(Strings::normaliseLink($tag['url']), null, [], $uid);
			if ($target_contact && $target_contact['contact-type'] == Contact::TYPE_COMMUNITY && $target_contact['manually-approve']) {
				Group::updateMembersForForum($target_contact['id']);
			}
		}

		if ($target_item['origin']) {
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($target_item, $uid);

			if (in_array($target_item['private'], [Item::PUBLIC])) {
				$inboxes = ActivityPub\Transmitter::addRelayServerInboxesForItem($target_item['id'], $inboxes);
				$relay_inboxes = ActivityPub\Transmitter::addRelayServerInboxes();
			}

			Logger::info('Origin item will be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
		} elseif (!Post\Activity::exists($target_item['uri-id'])) {
			Logger::info('Remote item is no AP post. It will not be distributed.', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		} elseif ($parent['origin'] && (($target_item['gravity'] != Item::GRAVITY_ACTIVITY) || DI::config()->get('system', 'redistribute_activities'))) {
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($parent, $uid, false, $target_item['id']);

			if (in_array($target_item['private'], [Item::PUBLIC])) {
				$inboxes = ActivityPub\Transmitter::addRelayServerInboxesForItem($parent['id'], $inboxes);
			}

			Logger::info('Remote item will be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
		} else {
			Logger::info('Remote activity will not be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		}

		if (empty($inboxes) && empty($relay_inboxes)) {
			Logger::info('No inboxes found for item ' . $target_item['id'] . ' with URL ' . $target_item['uri'] . '. It will not be distributed.');
			return ['count' => 0, 'contacts' => []];
		}

		// Fill the item cache
		ActivityPub\Transmitter::createCachedActivityFromItem($target_item['id'], true);

		$delivery_queue_count = 0;
		$contacts = [];

		foreach ($inboxes as $inbox => $receivers) {
			$contacts = array_merge($contacts, $receivers);

			if ((count($receivers) == 1) && Network::isLocalLink($inbox)) {
				$contact = Contact::getById($receivers[0], ['url']);
				if (!in_array($cmd, [Delivery::RELOCATION, Delivery::SUGGESTION, Delivery::DELETION, Delivery::MAIL]) && ($target_uid = User::getIdForURL($contact['url']))) {
					if ($target_item['origin'] || ($target_item['network'] != Protocol::ACTIVITYPUB)) {
						if ($target_uid != $target_item['uid']) {
							$fields = ['protocol' => Conversation::PARCEL_LOCAL_DFRN, 'direction' => Conversation::PUSH, 'post-reason' => Item::PR_BCC];
							Item::storeForUserByUriId($target_item['uri-id'], $target_uid, $fields, $target_item['uid']);
							Logger::info('Delivered locally', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);
						} else {
							Logger::info('No need to deliver to myself', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
						}
					} else {
						Logger::info('Remote item does not need to be delivered locally', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
					}
					continue;
				}
			} elseif ((count($receivers) >= 1) && Network::isLocalLink($inbox)) {
				Logger::info('Is this a thing?', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
			}

			Logger::info('Delivery via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);

			if (DI::config()->get('system', 'bulk_delivery')) {
				$delivery_queue_count++;
				Post\Delivery::add($target_item['uri-id'], $uid, $inbox, $target_item['created'], $cmd, $receivers);
				Worker::add(Worker::PRIORITY_HIGH, 'APDelivery', '', 0, $inbox, 0);
			} else {
				if (Worker::add(['priority' => $priority, 'created' => $created, 'dont_fork' => true],
						'APDelivery', $cmd, $target_item['id'], $inbox, $uid, $receivers, $target_item['uri-id'])) {
					$delivery_queue_count++;
				}
			}
			Worker::coolDown();
		}

		// We deliver posts to relay servers slightly delayed to priorize the direct delivery
		foreach ($relay_inboxes as $inbox) {
			Logger::info('Delivery to relay servers via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);

			if (DI::config()->get('system', 'bulk_delivery')) {
				$delivery_queue_count++;
				Post\Delivery::add($target_item['uri-id'], $uid, $inbox, $target_item['created'], $cmd, []);
				Worker::add(Worker::PRIORITY_MEDIUM, 'APDelivery', '', 0, $inbox, 0);
			} else {
				if (Worker::add(['priority' => $priority, 'dont_fork' => true], 'APDelivery', $cmd, $target_item['id'], $inbox, $uid, [], $target_item['uri-id'])) {
					$delivery_queue_count++;
				}
			}
			Worker::coolDown();
		}

		return ['count' => $delivery_queue_count, 'contacts' => $contacts];
	}
}

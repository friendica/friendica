<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Exception;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Circle;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Transmitter;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Delivery;
use Friendica\Util\LDSignature;
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
		$appHelper = DI::appHelper();

		DI::logger()->info('Invoked', ['cmd' => $cmd, 'target' => $post_uriid, 'sender_uid' => $sender_uid]);

		$target_id  = $post_uriid;
		$recipients = [];

		$delivery_contacts_stmt = null;
		$target_item            = [];
		$parent                 = [];
		$thr_parent             = [];
		$items                  = [];
		$delivery_queue_count   = 0;
		$ap_contacts            = [];

		if ($cmd == Delivery::MAIL) {
			$message = DBA::selectFirst('mail', ['uid', 'contact-id'], ['id' => $target_id]);
			if (!DBA::isResult($message)) {
				return;
			}
			$uid          = $message['uid'];
			$recipients[] = $message['contact-id'];

			$inboxes = ActivityPub\Transmitter::fetchTargetInboxesFromMail($target_id);
			foreach ($inboxes as $inbox => $receivers) {
				$ap_contacts = array_merge($ap_contacts, $receivers);
				DI::logger()->info('Delivery via ActivityPub', ['cmd' => $cmd, 'target' => $target_id, 'inbox' => $inbox]);
				Worker::add(
					['priority' => Worker::PRIORITY_HIGH, 'created' => $appHelper->getQueueValue('created'), 'dont_fork' => true],
					'APDelivery',
					$cmd,
					$target_id,
					$inbox,
					$uid,
					$receivers,
					$post_uriid
				);
			}
		} elseif ($cmd == Delivery::SUGGESTION) {
			$suggest      = DI::fsuggest()->selectOneById($target_id);
			$uid          = $suggest->uid;
			$recipients[] = $suggest->cid;
		} elseif ($cmd == Delivery::REMOVAL) {
			return self::notifySelfRemoval($target_id, $appHelper->getQueueValue('priority'), $appHelper->getQueueValue('created'));
		} elseif ($cmd == Delivery::RELOCATION) {
			$uid = $target_id;

			$condition              = ['uid' => $target_id, 'self' => false, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
			$delivery_contacts_stmt = DBA::select('contact', ['id', 'uri-id', 'url', 'addr', 'network', 'protocol', 'baseurl', 'gsid', 'batch'], $condition);
		} else {
			$post = Post::selectFirst(['id'], ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
			if (!DBA::isResult($post)) {
				DI::logger()->warning('Post not found', ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
				return;
			}
			$target_id = $post['id'];

			// find ancestors
			$condition   = ['id' => $target_id, 'visible' => true];
			$target_item = Post::selectFirst(Item::DELIVER_FIELDLIST, $condition);
			$target_item = Post\Media::addHTMLAttachmentToItem($target_item);

			if (!DBA::isResult($target_item) || !intval($target_item['parent'])) {
				DI::logger()->info('No target item', ['cmd' => $cmd, 'target' => $target_id]);
				return;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				DI::logger()->info('Only public users, quitting', ['target' => $target_id]);
				return;
			}

			$condition  = ['parent' => $target_item['parent'], 'visible' => true];
			$params     = ['order' => ['id']];
			$items_stmt = Post::select(Item::DELIVER_FIELDLIST, $condition, $params);
			if (!DBA::isResult($items_stmt)) {
				DI::logger()->info('No item found', ['cmd' => $cmd, 'target' => $target_id]);
				return;
			}

			$items = Post::toArray($items_stmt);

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			DI::logger()->info('Owner not found', ['cmd' => $cmd, 'target' => $target_id]);
			return;
		}

		// Should the post be transmitted to Diaspora?
		$diaspora_delivery = ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		// If this is a public conversation, notify the feed hub
		$public_message = true;

		$unlisted = false;

		$only_ap_delivery = false;

		$followup            = false;
		$recipients_followup = [];

		if (!empty($target_item) && !empty($items)) {
			$parent = $items[0];

			$fields     = ['network', 'private', 'author-id', 'author-link', 'author-network', 'owner-id'];
			$condition  = ['uri' => $target_item['thr-parent'], 'uid' => $target_item['uid']];
			$thr_parent = Post::selectFirst($fields, $condition);
			if (empty($thr_parent)) {
				$thr_parent = $parent;
			}

			DI::logger()->info('Got post', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'network' => $target_item['network'], 'parent-network' => $parent['network'], 'thread-parent-network' => $thr_parent['network']]);

			// Restrict distribution to AP, when there are no permissions.
			if (!self::isRemovalActivity($cmd, $owner, Protocol::ACTIVITYPUB) && ($target_item['private'] == Item::PRIVATE) && empty($target_item['allow_cid']) && empty($target_item['allow_gid']) && empty($target_item['deny_cid']) && empty($target_item['deny_gid'])) {
				$only_ap_delivery  = true;
				$public_message    = false;
				$diaspora_delivery = false;
			}

			if (!$target_item['origin'] && $target_item['network'] == Protocol::ACTIVITYPUB) {
				$only_ap_delivery  = true;
				$diaspora_delivery = false;
				DI::logger()->debug('Remote post arrived via AP', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'network' => $target_item['network'], 'parent-network' => $parent['network'], 'thread-parent-network' => $thr_parent['network']]);
			}

			// Only deliver threaded replies (comment to a comment) to Diaspora
			// when the original comment author does support the Diaspora protocol.
			if ($thr_parent['author-link'] && $target_item['parent-uri'] != $target_item['thr-parent']) {
				$diaspora_delivery = Diaspora::isSupportedByContactUrl($thr_parent['author-link']);
				if ($diaspora_delivery && empty($target_item['signed_text'])) {
					DI::logger()->debug('Post has got no Diaspora signature, so there will be no Diaspora delivery', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id']]);
					$diaspora_delivery = false;
				}
				DI::logger()->info('Threaded comment', ['diaspora_delivery' => (int)$diaspora_delivery]);
			}

			$unlisted = $target_item['private'] == Item::UNLISTED;

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.

			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.
			$relay_to_owner = false;

			if (($target_item['gravity'] != Item::GRAVITY_PARENT) && !$parent['wall'] && $target_item['origin']) {
				$relay_to_owner = true;
			}

			if (!$target_item['origin'] || $parent['origin']) {
				$relay_to_owner = false;
			}

			// Special treatment for group posts
			if (Item::isGroupPost($target_item['uri-id'])) {
				$relay_to_owner = true;
			}

			$exclusive_delivery = false;

			$exclusive_targets = Tag::getByURIId($parent['uri-id'], [Tag::EXCLUSIVE_MENTION]);
			if (!empty($exclusive_targets)) {
				$exclusive_delivery = true;
				DI::logger()->info('Possible Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id']]);
				foreach ($exclusive_targets as $target) {
					if (Strings::compareLink($owner['url'], $target['url'])) {
						$exclusive_delivery = false;
						DI::logger()->info('False Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'url' => $target['url']]);
					}
				}
			}

			if ($relay_to_owner) {
				// local followup to remote post
				$followup            = true;
				$public_message      = false; // not public
				$recipients          = [$parent['contact-id']];
				$recipients_followup = [$parent['contact-id']];

				DI::logger()->info('Followup', ['target' => $target_id, 'guid' => $target_item['guid'], 'to' => $parent['contact-id']]);
			} elseif ($exclusive_delivery) {
				$followup = true;

				foreach ($exclusive_targets as $target) {
					$cid = Contact::getIdForURL($target['url'], $uid, false);
					if ($cid) {
						$recipients_followup[] = $cid;
						DI::logger()->info('Exclusively delivering', ['uid' => $target_item['uid'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'url' => $target['url']]);
					}
				}
			} else {
				$followup = false;

				DI::logger()->info('Distributing directly', ['target' => $target_id, 'guid' => $target_item['guid']]);

				// don't send deletions onward for other people's stuff

				if ($target_item['deleted'] && !intval($target_item['wall'])) {
					DI::logger()->notice('Ignoring delete notification for non-wall item');
					return;
				}

				if (
					strlen($parent['allow_cid'])
					|| strlen($parent['allow_gid'])
					|| strlen($parent['deny_cid'])
					|| strlen($parent['deny_gid'])
				) {
					$public_message = false; // private recipients, not public
				}

				$aclFormatter = DI::aclFormatter();

				$allow_people  = $aclFormatter->expand($parent['allow_cid']);
				$allow_circles = Circle::expand($uid, $aclFormatter->expand($parent['allow_gid']), true);
				$deny_people   = $aclFormatter->expand($parent['deny_cid']);
				$deny_circles  = Circle::expand($uid, $aclFormatter->expand($parent['deny_gid']));

				foreach ($items as $item) {
					$recipients[] = $item['contact-id'];
					// pull out additional tagged people to notify (if public message)
					if ($public_message && $item['inform']) {
						$people = explode(',', $item['inform']);
						foreach ($people as $person) {
							if (substr($person, 0, 4) === 'cid:') {
								$recipients[] = intval(substr($person, 4));
							}
						}
					}
				}

				$recipients = array_unique(array_merge($recipients, $allow_people, $allow_circles));
				$deny       = array_unique(array_merge($deny_people, $deny_circles));
				$recipients = array_diff($recipients, $deny);

				// If this is a public message and pubmail is set on the parent, include all your email contacts
				if (
					function_exists('imap_open')
					&& !DI::config()->get('system', 'imap_disabled')
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

			if ($diaspora_delivery) {
				$networks = [Protocol::DFRN, Protocol::DIASPORA, Protocol::MAIL];
				if (($parent['network'] == Protocol::DIASPORA) || ($thr_parent['network'] == Protocol::DIASPORA)) {
					DI::logger()->info('Add AP contacts', ['target' => $target_id, 'guid' => $target_item['guid']]);
					$networks[] = Protocol::ACTIVITYPUB;
				}
			} else {
				$networks = [Protocol::DFRN, Protocol::MAIL];
			}
		} else {
			$public_message = false;
		}

		if ($only_ap_delivery) {
			$recipients = [];
		} elseif ($followup) {
			$recipients = $recipients_followup;
		}

		$apdelivery  = self::activityPubDelivery($cmd, $target_item, $parent, $thr_parent, $appHelper->getQueueValue('priority'), $appHelper->getQueueValue('created'), $recipients);
		$ap_contacts = $apdelivery['contacts'];
		$delivery_queue_count += $apdelivery['count'];

		if (!$only_ap_delivery) {
			if (empty($delivery_contacts_stmt)) {
				$condition = ['id' => $recipients, 'self' => false, 'uid' => [0, $uid],
					'blocked'         => false, 'pending' => false, 'archive' => false];
				if (!empty($networks)) {
					$condition['network'] = $networks;
				}
				$delivery_contacts_stmt = DBA::select('contact', ['id', 'uri-id', 'addr', 'url', 'network', 'protocol', 'baseurl', 'gsid', 'batch'], $condition);
			}

			$conversants    = [];
			$batch_delivery = false;

			if ($public_message && !in_array($cmd, [Delivery::MAIL, Delivery::SUGGESTION]) && !$followup) {
				$participants = [];

				if ($diaspora_delivery && !$unlisted) {
					$batch_delivery = true;

					$participants = DBA::selectToArray(
						'contact',
						['batch', 'network', 'protocol', 'baseurl', 'gsid', 'id', 'url', 'name'],
						["`network` = ? AND `batch` != '' AND `uid` = ? AND `rel` != ? AND NOT `blocked` AND NOT `pending` AND NOT `archive`", Protocol::DIASPORA, $owner['uid'], Contact::SHARING],
						['group_by' => ['batch', 'network', 'protocol']]
					);

					// Fetch the participation list
					// The function will ensure that there are no duplicates
					$participants = Diaspora::participantsForThread($target_item, $participants);
				}

				$condition = [
					'network' => Protocol::DFRN,
					'uid'     => $owner['uid'],
					'self'    => false,
					'blocked' => false,
					'pending' => false,
					'archive' => false,
					'rel'     => [Contact::FOLLOWER, Contact::FRIEND]
				];

				$contacts = DBA::selectToArray('contact', ['id', 'uri-id', 'url', 'addr', 'name', 'network', 'protocol', 'baseurl', 'gsid'], $condition);

				$conversants = array_merge($contacts, $participants);

				$delivery_queue_count += self::delivery($cmd, $post_uriid, $sender_uid, $target_item, $parent, $thr_parent, $owner, $batch_delivery, true, $conversants, $ap_contacts, []);
			}

			$contacts = DBA::toArray($delivery_contacts_stmt);
			$delivery_queue_count += self::delivery($cmd, $post_uriid, $sender_uid, $target_item, $parent, $thr_parent, $owner, $batch_delivery, false, $contacts, $ap_contacts, $conversants);
		}

		if (!empty($target_item)) {
			DI::logger()->info('Calling hooks for ' . $cmd . ' ' . $target_id);

			Hook::fork($appHelper->getQueueValue('priority'), 'notifier_normal', $target_item);

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
	 * @param array $parent
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
	private static function delivery(string $cmd, int $post_uriid, int $sender_uid, array $target_item, array $parent, array $thr_parent, array $owner, bool $batch_delivery, bool $in_batch, array $contacts, array $ap_contacts, array $conversants = []): int
	{
		$appHelper            = DI::appHelper();
		$delivery_queue_count = 0;

		if (!empty($target_item['verb']) && ($target_item['verb'] == Activity::ANNOUNCE)) {
			DI::logger()->notice('Announces are only delivery via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
			return 0;
		}

		foreach ($contacts as $contact) {
			// Transmit via Diaspora if the thread had started as Diaspora post.
			// Also transmit via Diaspora if this is a direct answer to a Diaspora comment.
			if (($contact['network'] != Protocol::DIASPORA) && in_array(Protocol::DIASPORA, [$parent['network'] ?? '', $thr_parent['network'] ?? '', $target_item['network'] ?? ''])) {
				DI::logger()->info('Enforcing the Diaspora protocol', ['id' => $contact['id'], 'network' => $contact['network'], 'parent' => $parent['network'], 'thread-parent' => $thr_parent['network'], 'post' => $target_item['network']]);
				$contact['network'] = Protocol::DIASPORA;
			}

			// Direct delivery of local contacts
			if (!in_array($cmd, [Delivery::RELOCATION, Delivery::SUGGESTION, Delivery::MAIL]) && $target_uid = User::getIdForURL($contact['url'])) {
				if ($cmd == Delivery::DELETION) {
					DI::logger()->info('No need to deliver deletions internally', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
					continue;
				}
				if ($target_item['origin'] || ($target_item['network'] != Protocol::ACTIVITYPUB)) {
					if ($target_uid != $target_item['uid']) {
						$fields = ['protocol' => Conversation::PARCEL_LOCAL_DFRN, 'direction' => Conversation::PUSH, 'post-reason' => Item::PR_DIRECT];
						Item::storeForUserByUriId($target_item['uri-id'], $target_uid, $fields, $target_item['uid']);
						DI::logger()->info('Delivered locally', ['cmd' => $cmd, 'id' => $target_item['id'], 'target' => $target_uid]);
					} else {
						DI::logger()->info('No need to deliver to myself', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
					}
				} else {
					DI::logger()->info('Remote item does not need to be delivered locally', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
				}
				continue;
			}

			$cdata = Contact::getPublicAndUserContactID($contact['id'], $sender_uid);
			if (empty($cdata)) {
				DI::logger()->info('No contact entry found', ['id' => $contact['id'], 'uid' => $sender_uid]);
				continue;
			}
			if (in_array($cdata['public'] ?: $contact['id'], $ap_contacts)) {
				DI::logger()->info('The public contact is already delivered via AP, so skip delivery via legacy DFRN/Diaspora', ['batch' => $in_batch, 'target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			} elseif (in_array($cdata['user'] ?: $contact['id'], $ap_contacts)) {
				DI::logger()->info('The user contact is already delivered via AP, so skip delivery via legacy DFRN/Diaspora', ['batch' => $in_batch, 'target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (!empty($contact['id']) && Contact::isArchived($contact['id'])) {
				// We mark the contact here, since we could have only got here, when the "archived" value on this
				// specific contact hadn't been set.
				Contact::markForArchival($contact);
				DI::logger()->info('Contact is archived, so skip delivery', ['target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (self::isRemovalActivity($cmd, $owner, $contact['network'])) {
				DI::logger()->info('Contact does no supports account removal commands, so skip delivery', ['target' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact['url']]);
				continue;
			}

			if (self::skipActivityPubForDiaspora($contact, $target_item, $thr_parent)) {
				DI::logger()->info('Contact is from Diaspora, but the replied author is from ActivityPub, so skip delivery via Diaspora', ['id' => $post_uriid, 'uid' => $sender_uid, 'url' => $contact['url']]);
				continue;
			}

			// Don't deliver to Diaspora if it already had been done as batch delivery
			if (!$in_batch && $batch_delivery && ($contact['network'] == Protocol::DIASPORA)) {
				DI::logger()->info('Diaspora contact is already delivered via batch', ['id' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact]);
				continue;
			}

			// Don't deliver to folks who have already been delivered to
			if (in_array($contact['id'], $conversants)) {
				DI::logger()->info('Already delivery', ['id' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact]);
				continue;
			}

			if (empty($contact['gsid'])) {
				$reachable = GServer::reachable($contact);
			} elseif (!DI::config()->get('system', 'bulk_delivery')) {
				$reachable = GServer::isReachableById($contact['gsid']);
			} else {
				$reachable = !GServer::isDefunctById($contact['gsid']);
			}

			if (!$reachable) {
				DI::logger()->info('Server is not reachable', ['id' => $post_uriid, 'uid' => $sender_uid, 'contact' => $contact]);
				continue;
			}

			if (($contact['network'] == Protocol::ACTIVITYPUB) && !DI::dsprContact()->existsByUriId($contact['uri-id'])) {
				DI::logger()->info('The ActivityPub contact does not support Diaspora, so skip delivery via Diaspora', ['id' => $post_uriid, 'uid' => $sender_uid, 'url' => $contact['url']]);
				continue;
			}

			DI::logger()->info('Delivery', ['cmd' => $cmd, 'batch' => $in_batch, 'target' => $post_uriid, 'uid' => $sender_uid, 'guid' => $target_item['guid'] ?? '', 'to' => $contact]);

			// Ensure that posts with our own protocol arrives before Diaspora posts arrive.
			// Situation is that sometimes Friendica servers receive Friendica posts over the Diaspora protocol first.
			// The conversion in Markdown reduces the formatting, so these posts should arrive after the Friendica posts.
			// This is only important for high and medium priority tasks and not for Low priority jobs like deletions.
			if (($contact['network'] == Protocol::DIASPORA) && in_array($appHelper->getQueueValue('priority'), [Worker::PRIORITY_HIGH, Worker::PRIORITY_MEDIUM])) {
				$deliver_options = ['priority' => $appHelper->getQueueValue('priority'), 'dont_fork' => true];
			} else {
				$deliver_options = ['priority' => $appHelper->getQueueValue('priority'), 'created' => $appHelper->getQueueValue('created'), 'dont_fork' => true];
			}

			if (!empty($contact['gsid']) && DI::config()->get('system', 'bulk_delivery')) {
				$delivery_queue_count++;
				$deliveryQueueItem = DI::deliveryQueueItemFactory()->createFromDelivery($cmd, $post_uriid, new \DateTimeImmutable($target_item['created']), $contact['id'], $contact['gsid'], $sender_uid);
				DI::deliveryQueueItemRepo()->save($deliveryQueueItem);
				Worker::add(['priority' => Worker::PRIORITY_HIGH, 'dont_fork' => true], 'BulkDelivery', $contact['gsid']);
			} else {
				if (Worker::add($deliver_options, 'Delivery', $cmd, $post_uriid, (int)$contact['id'], $sender_uid)) {
					$delivery_queue_count++;
				}
			}

			Worker::coolDown();
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
		return ($cmd == Delivery::REMOVAL) && $owner['account_removed'] && in_array($network, [Protocol::ACTIVITYPUB, Protocol::DIASPORA]);
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

		while ($contact = DBA::fetch($contacts_stmt)) {
			Contact::terminateFriendship($contact);
		}
		DBA::close($contacts_stmt);

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($self_user_id);
		foreach ($inboxes as $inbox => $receivers) {
			DI::logger()->info('Account removal via ActivityPub', ['uid' => $self_user_id, 'inbox' => $inbox]);
			Worker::add(
				['priority' => Worker::PRIORITY_NEGLIGIBLE, 'created' => $created, 'dont_fork' => true],
				'APDelivery',
				Delivery::REMOVAL,
				0,
				$inbox,
				$self_user_id,
				$receivers
			);
			Worker::coolDown();
		}

		return true;
	}

	/**
	 * @param string $cmd
	 * @param array  $target_item
	 * @param array  $parent
	 * @param array  $thr_parent
	 * @param int    $priority   The priority the Notifier queue item was created with
	 * @param string $created    The date the Notifier queue item was created on
	 * @param array  $recipients Array of receivers
	 *
	 * @return array 'count' => The number of delivery tasks created, 'contacts' => their contact ids
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo Unused parameter $owner
	 */
	private static function activityPubDelivery($cmd, array $target_item, array $parent, array $thr_parent, int $priority, string $created, array $recipients): array
	{
		// Don't deliver via AP when the starting post isn't from a federated network
		if (!in_array($parent['network'] ?? '', Protocol::FEDERATED)) {
			DI::logger()->info('Parent network is no federated network, so no AP delivery', ['network' => $parent['network'] ?? '']);
			return ['count' => 0, 'contacts' => []];
		}

		// Don't deliver via AP when the starting post is delivered via Diaspora
		if ($parent['network'] == Protocol::DIASPORA) {
			DI::logger()->info('Parent network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		// Also don't deliver when the direct thread parent was delivered via Diaspora
		if ($thr_parent['network'] == Protocol::DIASPORA) {
			DI::logger()->info('Thread parent network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		// Posts from Diaspora contacts are transmitted via Diaspora
		if ($target_item['network'] == Protocol::DIASPORA) {
			DI::logger()->info('Post network is Diaspora, so no AP delivery');
			return ['count' => 0, 'contacts' => []];
		}

		$inboxes       = [];
		$relay_inboxes = [];

		$uid = $target_item['contact-uid'] ?: $target_item['uid'];

		// Update the locally stored follower list when we deliver to a group
		foreach (Tag::getByURIId($target_item['uri-id'], [Tag::MENTION, Tag::EXCLUSIVE_MENTION]) as $tag) {
			$target_contact = Contact::getByURL(Strings::normaliseLink($tag['url']), null, [], $uid);
			if ($target_contact && $target_contact['contact-type'] == Contact::TYPE_COMMUNITY && $target_contact['manually-approve']) {
				Circle::updateMembersForGroup($target_contact['id']);
			}
		}

		if ($target_item['origin']) {
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($target_item, $uid);

			if (in_array($target_item['private'], [Item::PUBLIC])) {
				$inboxes       = ActivityPub\Transmitter::addRelayServerInboxesForItem($target_item['id'], $inboxes);
				$relay_inboxes = ActivityPub\Transmitter::addRelayServerInboxes();
			}

			DI::logger()->info('Origin item will be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			$check_signature = false;
		} elseif (!$target_item['deleted'] && !Post\Activity::exists($target_item['uri-id'])) {
			DI::logger()->info('Remote activity not found. It will not be distributed.', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		} elseif ($parent['origin'] && ($target_item['private'] != Item::PRIVATE) && (($target_item['gravity'] != Item::GRAVITY_ACTIVITY) || DI::config()->get('system', 'redistribute_activities'))) {
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($parent, $uid);

			if (in_array($target_item['private'], [Item::PUBLIC])) {
				$inboxes = ActivityPub\Transmitter::addRelayServerInboxesForItem($parent['id'], $inboxes);
			}

			DI::logger()->info('Remote item will be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			$check_signature = ($target_item['gravity'] == Item::GRAVITY_ACTIVITY);
		} else {
			DI::logger()->info('Remote activity will not be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		}

		if ($target_item['private'] != Item::PRIVATE) {
			$inboxes = Transmitter::addInboxesForRecipients($recipients, $inboxes);
		}

		if (empty($inboxes) && empty($relay_inboxes)) {
			DI::logger()->info('No inboxes found for item ' . $target_item['id'] . ' with URL ' . $target_item['uri'] . '. It will not be distributed.');
			return ['count' => 0, 'contacts' => []];
		}

		// Fill the item cache
		$activity = ActivityPub\Transmitter::createCachedActivityFromItem($target_item['id'], true);
		if (empty($activity)) {
			DI::logger()->info('Item cache was not created. The post will not be distributed.', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		}

		if ($check_signature && !LDSignature::isSigned($activity)) {
			DI::logger()->info('Unsigned remote activity will not be distributed', ['id' => $target_item['id'], 'url' => $target_item['uri'], 'verb' => $target_item['verb']]);
			return ['count' => 0, 'contacts' => []];
		}

		$delivery_queue_count = 0;
		$contacts             = [];

		foreach ($inboxes as $inbox => $receivers) {
			$contacts = array_merge($contacts, $receivers);

			if ((count($receivers) == 1) && DI::baseUrl()->isLocalUrl($inbox)) {
				$contact = Contact::getById($receivers[0], ['url']);
				if (!in_array($cmd, [Delivery::RELOCATION, Delivery::SUGGESTION, Delivery::MAIL]) && ($target_uid = User::getIdForURL($contact['url']))) {
					if ($cmd == Delivery::DELETION) {
						DI::logger()->info('No need to deliver deletions internally', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
						continue;
					}
					if ($target_item['origin'] || ($target_item['network'] != Protocol::ACTIVITYPUB)) {
						if ($target_uid != $target_item['uid']) {
							$fields = ['protocol' => Conversation::PARCEL_LOCAL_DFRN, 'direction' => Conversation::PUSH, 'post-reason' => Item::PR_BCC];
							Item::storeForUserByUriId($target_item['uri-id'], $target_uid, $fields, $target_item['uid']);
							DI::logger()->info('Delivered locally', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);
						} else {
							DI::logger()->info('No need to deliver to myself', ['uid' => $target_uid, 'guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
						}
					} else {
						DI::logger()->info('Remote item does not need to be delivered locally', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
					}
					continue;
				}
			} elseif ((count($receivers) >= 1) && DI::baseUrl()->isLocalUrl($inbox)) {
				DI::logger()->info('Is this a thing?', ['guid' => $target_item['guid'], 'uri-id' => $target_item['uri-id'], 'uri' => $target_item['uri']]);
			}

			DI::logger()->info('Delivery via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);

			if (DI::config()->get('system', 'bulk_delivery')) {
				$delivery_queue_count++;
				Post\Delivery::add($target_item['uri-id'], $uid, $inbox, $target_item['created'], $cmd, $receivers);
				Worker::add([Worker::PRIORITY_HIGH, 'dont_fork' => true], 'APDelivery', '', 0, $inbox, 0);
			} else {
				if (Worker::add(
					['priority' => $priority, 'created' => $created, 'dont_fork' => true],
					'APDelivery',
					$cmd,
					$target_item['id'],
					$inbox,
					$uid,
					$receivers,
					$target_item['uri-id']
				)) {
					$delivery_queue_count++;
				}
			}
			Worker::coolDown();
		}

		// We deliver posts to relay servers slightly delayed to prioritize the direct delivery
		foreach ($relay_inboxes as $inbox) {
			DI::logger()->info('Delivery to relay servers via ActivityPub', ['cmd' => $cmd, 'id' => $target_item['id'], 'inbox' => $inbox]);

			if (DI::config()->get('system', 'bulk_delivery')) {
				$delivery_queue_count++;
				Post\Delivery::add($target_item['uri-id'], $uid, $inbox, $target_item['created'], $cmd, []);
				Worker::add([Worker::PRIORITY_MEDIUM, 'dont_fork' => true], 'APDelivery', '', 0, $inbox, 0);
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

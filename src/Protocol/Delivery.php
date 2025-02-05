<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\Contact\FriendSuggest\Collection\FriendSuggests;
use Friendica\Contact\FriendSuggest\Exception\FriendSuggestNotFoundException;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Util\Network;

class Delivery
{
	const MAIL          = 'mail';
	const SUGGESTION    = 'suggest';
	const RELOCATION    = 'relocate';
	const DELETION      = 'drop';
	const POST          = 'wall-new';
	const REMOVAL       = 'removeme';
	const PROFILEUPDATE = 'profileupdate';

	/**
	 * Deliver posts to other systems
	 *
	 * @param string $cmd
	 * @param integer $post_uriid
	 * @param integer $contact_id
	 * @param integer $sender_uid
	 * @return bool "false" on remote system error. "true" when delivery was successful or we shouldn't retry.
	 */
	public static function deliver(string $cmd, int $post_uriid, int $contact_id, int $sender_uid = 0): bool
	{
		DI::logger()->info('Invoked', ['cmd' => $cmd, 'target' => $post_uriid, 'sender_uid' => $sender_uid, 'contact' => $contact_id]);

		$top_level      = false;
		$followup       = false;
		$public_message = false;
		$parent         = false;
		$thr_parent     = false;

		$items = [];
		if ($cmd == self::MAIL) {
			$target_item = DBA::selectFirst('mail', [], ['id' => $post_uriid]);
			if (!DBA::isResult($target_item)) {
				return true;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::SUGGESTION) {
			try {
				$target_item = DI::fsuggest()->selectOneById($post_uriid)->toArray();
			} catch (FriendSuggestNotFoundException $e) {
				DI::logger()->info('Cannot find FriendSuggestion', ['id' => $post_uriid]);
				return true;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::RELOCATION) {
			$uid         = $post_uriid;
			$target_item = [];
		} else {
			$item = Post::selectFirst(['id', 'parent'], ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
			if (!DBA::isResult($item) || empty($item['parent'])) {
				DI::logger()->warning('Post not found', ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
				return true;
			}
			$target_id = intval($item['id']);
			$parent_id = intval($item['parent']);

			$condition = ['id' => [$target_id, $parent_id], 'visible' => true];
			$params    = ['order' => ['id']];
			$itemdata  = Post::select(Item::DELIVER_FIELDLIST, $condition, $params);

			while ($item = Post::fetch($itemdata)) {
				if ($item['verb'] == Activity::ANNOUNCE) {
					continue;
				}

				if ($item['id'] == $parent_id) {
					$parent = $item;
				}
				if ($item['id'] == $target_id) {
					$target_item = $item;
				}
				$items[] = $item;
			}
			DBA::close($itemdata);

			if (empty($target_item)) {
				DI::logger()->warning("No target item data. Quitting here.", ['id' => $target_id]);
				return true;
			}

			if (empty($parent)) {
				DI::logger()->warning('Parent ' . $parent_id . ' for item ' . $target_id . "wasn't found. Quitting here.");
				self::setFailedQueue($cmd, $target_item);
				return true;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				DI::logger()->info('Only public users for item ' . $target_id);
				self::setFailedQueue($cmd, $target_item);
				return true;
			}

			$condition  = ['uri' => $target_item['thr-parent'], 'uid' => $target_item['uid']];
			$thr_parent = Post::selectFirst(['network', 'object'], $condition);
			if (!DBA::isResult($thr_parent)) {
				// Shouldn't happen. But when this does, we just take the parent as thread parent.
				// That's totally okay for what we use this variable here.
				$thr_parent = $parent;
			}

			if (!empty($contact_id) && Contact::isArchived($contact_id)) {
				DI::logger()->info('Contact is archived', ['id' => $contact_id, 'cmd' => $cmd, 'item' => $target_item['id']]);
				self::setFailedQueue($cmd, $target_item);
				return true;
			}

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			$top_level = $target_item['gravity'] == Item::GRAVITY_PARENT;

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = DI::baseUrl()->getHost();
			if (strpos($localhost, ':')) {
				$localhost = substr($localhost, 0, strpos($localhost, ':'));
			}
			/**
			 *
			 * Be VERY CAREFUL if you make any changes to the following line. Seemingly innocuous changes
			 * have been known to cause runaway conditions which affected several servers, along with
			 * permissions issues.
			 *
			 */

			if (!$top_level && ($parent['wall'] == 0) && stristr($target_item['uri'], $localhost)) {
				DI::logger()->info('Followup ' . $target_item["guid"]);
				// local followup to remote post
				$followup = true;
			}

			if (empty($parent['allow_cid'])
				&& empty($parent['allow_gid'])
				&& empty($parent['deny_cid'])
				&& empty($parent['deny_gid'])
				&& ($parent['private'] != Item::PRIVATE)) {
				$public_message = true;
			}
		}

		if (empty($items)) {
			DI::logger()->warning('No delivery data', ['command' => $cmd, 'uri-id' => $post_uriid, 'cid' => $contact_id]);
		}

		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			self::setFailedQueue($cmd, $target_item);
			return true;
		}

		// We don't deliver our items to blocked, archived or pending contacts, and not to ourselves either
		$contact = DBA::selectFirst(
			'contact',
			[],
			['id' => $contact_id, 'archive' => false, 'blocked' => false, 'pending' => false, 'self' => false]
		);
		if (!DBA::isResult($contact)) {
			self::setFailedQueue($cmd, $target_item);
			return true;
		}

		if (Network::isUrlBlocked($contact['url'])) {
			self::setFailedQueue($cmd, $target_item);
			return true;
		}

		$protocol = GServer::getProtocol($contact['gsid'] ?? 0);

		// Transmit via Diaspora if the thread had started as Diaspora post.
		// Also transmit via Diaspora if this is a direct answer to a Diaspora comment.
		// This is done since the uri wouldn't match (Diaspora doesn't transmit it)
		// Also transmit relayed posts from Diaspora contacts via Diaspora.
		if (($contact['network'] != Protocol::DIASPORA) && in_array(Protocol::DIASPORA, [$parent['network'] ?? '', $thr_parent['network'] ?? '', $target_item['network'] ?? ''])) {
			DI::logger()->info('Enforcing the Diaspora protocol', ['id' => $contact['id'], 'network' => $contact['network'], 'parent' => $parent['network'], 'thread-parent' => $thr_parent['network'], 'post' => $target_item['network']]);
			$contact['network'] = Protocol::DIASPORA;
		}

		DI::logger()->notice('Delivering', ['cmd' => $cmd, 'uri-id' => $post_uriid, 'followup' => $followup, 'network' => $contact['network']]);

		switch ($contact['network']) {
			case Protocol::DFRN:
				$success = self::deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup, $protocol);
				break;

			case Protocol::DIASPORA:
				$success = self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
				break;

			case Protocol::MAIL:
				$success = self::deliverMail($cmd, $contact, $owner, $target_item, $thr_parent);
				break;

			default:
				$success = true;
				break;
		}

		return $success;
	}

	/**
	 * Increased the "failed" counter in the item delivery data
	 *
	 * @param string $cmd  Command
	 * @param array  $item Item array
	 *
	 * @return void
	 */
	private static function setFailedQueue(string $cmd, array $item)
	{
		if ($cmd != Delivery::POST) {
			return;
		}

		Post\DeliveryData::incrementQueueFailed($item['uri-id'] ?? $item['id']);
	}

	/**
	 * Deliver content via DFRN
	 *
	 * @param string   $cmd             Command
	 * @param array    $contact         Contact record of the receiver
	 * @param array    $owner           Owner record of the sender
	 * @param array    $items           Item record of the content and the parent
	 * @param array    $target_item     Item record of the content
	 * @param boolean  $public_message  Is the content public?
	 * @param boolean  $top_level       Is it a thread starter?
	 * @param boolean  $followup        Is it an answer to a remote post?
	 * @param int|null $server_protocol The protocol of the server
	 *
	 * @return bool "false" on remote system error. "true" when delivery was successful or we shouldn't retry.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverDFRN(string $cmd, array $contact, array $owner, array $items, array $target_item, bool $public_message, bool $top_level, bool $followup, int $server_protocol = null): bool
	{
		$target_item_id = $target_item['guid'] ?? '' ?: $target_item['id'] ?? null;

		// Transmit Diaspora reshares via Diaspora if the Friendica contact support Diaspora
		if (Diaspora::getReshareDetails($target_item) && Diaspora::isSupportedByContactUrl($contact['addr'])) {
			DI::logger()->info('Reshare will be transmitted via Diaspora', ['url' => $contact['url'], 'guid' => $target_item_id]);
			return self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
		}

		DI::logger()->info('Deliver ' . ($target_item_id ?? 'relocation') . ' via DFRN to ' . ($contact['addr'] ?? '' ?: $contact['url']));

		if ($cmd == self::MAIL) {
			$item         = $target_item;
			$item['body'] = Item::fixPrivatePhotos($item['body'], $owner['uid'], null, $item['contact-id']);
			$atom         = DFRN::mail($item, $owner);
		} elseif ($cmd == self::SUGGESTION) {
			$item = $target_item;
			$atom = DFRN::fsuggest($item, $owner);
			DI::fsuggest()->delete(new FriendSuggests([DI::fsuggest()->selectOneById($item['id'])]));
		} elseif ($cmd == self::RELOCATION) {
			$atom = DFRN::relocate($owner, $owner['uid']);
		} elseif ($followup) {
			$msgitems = [$target_item];
			$atom     = DFRN::entries($msgitems, $owner);
		} else {
			if ($target_item['deleted']) {
				$msgitems = [$target_item];
			} else {
				$msgitems = [];
				foreach ($items as $item) {
					// Only add the parent when we don't delete other items.
					if (($target_item['id'] == $item['id']) || ($cmd != self::DELETION)) {
						$item['entry:comment-allow'] = true;
						$item['entry:cid']           = ($top_level ? $contact['id'] : 0);
						$msgitems[]                  = $item;
					}
				}
			}
			$atom = DFRN::entries($msgitems, $owner);
		}

		DI::logger()->debug('Notifier entry', ['url' => $contact['url'], 'target_item_id' => ($target_item_id ?? 'relocation'), 'entry' => $atom]);

		$protocol = Post\DeliveryData::DFRN;

		// We don't have a relationship with contacts on a public post.
		// Se we transmit with the new method and via Diaspora as a fallback
		if (!empty($items) && (($items[0]['uid'] == 0) || ($contact['uid'] == 0))) {
			// Transmit in public if it's a relay post
			$public_dfrn = ($contact['contact-type'] == Contact::TYPE_RELAY);

			$deliver_status = DFRN::transmit($owner, $contact, $atom, $public_dfrn);

			// We never spool failed relay deliveries
			if ($public_dfrn) {
				DI::logger()->info('Relay delivery to ' . $contact['url'] . ' with guid ' . $target_item['guid'] . ' returns ' . $deliver_status);

				$success = ($deliver_status >= 200) && ($deliver_status <= 299);

				if ($cmd == Delivery::POST) {
					Post\DeliveryData::incrementQueueDone($target_item['uri-id'], $protocol);
					GServer::setProtocol($contact['gsid'] ?? 0, $protocol);
				} else {
					Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
				}
				return $success;
			}

			if ((($deliver_status < 200) || ($deliver_status > 299)) && (empty($server_protocol) || ($server_protocol == Post\DeliveryData::LEGACY_DFRN))) {
				// Transmit via Diaspora if not possible via Friendica
				return self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
			}
		} else {
			// DFRN payload over Diaspora transport layer
			$deliver_status = DFRN::transmit($owner, $contact, $atom);
		}

		DI::logger()->info('DFRN Delivery', ['cmd' => $cmd, 'url' => $contact['url'], 'guid' => $target_item_id, 'return' => $deliver_status]);

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Contact::unmarkForArchival($contact);

			GServer::setProtocol($contact['gsid'] ?? 0, $protocol);

			if ($cmd == Delivery::POST) {
				Post\DeliveryData::incrementQueueDone($target_item['uri-id'], $protocol);
			}
			$success = true;
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Contact::markForArchival($contact);

			DI::logger()->info('Delivery failed: defer message', ['id' => $target_item_id]);
			if (!Worker::defer() && $cmd == Delivery::POST) {
				Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
			}
			$success = false;
		}
		return $success;
	}

	/**
	 * Deliver content via Diaspora
	 *
	 * @param string  $cmd            Command
	 * @param array   $contact        Contact record of the receiver
	 * @param array   $owner          Owner record of the sender
	 * @param array   $items          Item record of the content and the parent
	 * @param array   $target_item    Item record of the content
	 * @param boolean $public_message Is the content public?
	 * @param boolean $top_level      Is it a thread starter?
	 * @param boolean $followup       Is it an answer to a remote post?
	 *
	 * @return bool "false" on remote system error. "true" when delivery was successful or we shouldn't retry.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverDiaspora(string $cmd, array $contact, array $owner, array $items, array $target_item, bool $public_message, bool $top_level, bool $followup): bool
	{
		// We don't treat group posts as "wall-to-wall" to be able to post them via Diaspora
		$walltowall = $top_level && ($owner['id'] != $items[0]['contact-id']) & ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		if ($public_message) {
			$loc = 'public batch ' . $contact['batch'];
		} else {
			$loc = $contact['addr'];
		}

		DI::logger()->notice('Deliver via Diaspora', ['target' => $target_item['id'], 'guid' => $target_item['guid'], 'to' => $loc]);

		if (!DI::config()->get('system', 'diaspora_enabled')) {
			return true;
		}

		if ($cmd == self::MAIL) {
			$deliver_status = Diaspora::sendMail($target_item, $owner, $contact);
			return ($deliver_status >= 200) && ($deliver_status <= 299);
		}

		if ($cmd == self::SUGGESTION) {
			return true;
		}

		if (!$contact['pubkey'] && !$public_message) {
			return true;
		}

		if ($cmd == self::RELOCATION) {
			$deliver_status = Diaspora::sendAccountMigration($owner, $contact, $owner['uid']);
		} elseif ($target_item['deleted'] && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
			// top-level retraction
			DI::logger()->notice('diaspora retract: ' . $loc);
			$deliver_status = Diaspora::sendRetraction($target_item, $owner, $contact, $public_message);
		} elseif ($followup) {
			// send comments and likes to owner to relay
			DI::logger()->notice('diaspora followup: ' . $loc);
			$deliver_status = Diaspora::sendFollowup($target_item, $owner, $contact, $public_message);
		} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			DI::logger()->notice('diaspora relay: ' . $loc);
			$deliver_status = Diaspora::sendRelay($target_item, $owner, $contact, $public_message);
		} elseif ($top_level && !$walltowall) {
			// currently no workable solution for sending walltowall
			DI::logger()->notice('diaspora status: ' . $loc);
			$deliver_status = Diaspora::sendStatus($target_item, $owner, $contact, $public_message);
		} else {
			DI::logger()->warning('Unknown mode', ['command' => $cmd, 'target' => $loc]);
			return true;
		}

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Contact::unmarkForArchival($contact);

			GServer::setProtocol($contact['gsid'] ?? 0, Post\DeliveryData::DIASPORA);

			if ($cmd == Delivery::POST) {
				Post\DeliveryData::incrementQueueDone($target_item['uri-id'], Post\DeliveryData::DIASPORA);
			}
			$success = true;
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Contact::markForArchival($contact);

			// When it is delivered to the public endpoint, we do mark the relay contact for archival as well
			if ($public_message) {
				Relay::markForArchival($contact);
			}

			if (empty($contact['contact-type']) || ($contact['contact-type'] != Contact::TYPE_RELAY)) {
				DI::logger()->info('Delivery failed: defer message', ['id' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
				// defer message for redelivery
				if (!Worker::defer() && $cmd == Delivery::POST) {
					Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
				}
			} elseif ($cmd == Delivery::POST) {
				Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
			}
			$success = false;
		}
		return $success;
	}

	/**
	 * Deliver content via mail
	 *
	 * @param string $cmd         Command
	 * @param array  $contact     Contact record of the receiver
	 * @param array  $owner       Owner record of the sender
	 * @param array  $target_item Item record of the content
	 * @param array  $thr_parent  Item record of the direct parent in the thread
	 *
	 * @return bool "false" on remote system error. "true" when delivery was successful or we shouldn't retry.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverMail(string $cmd, array $contact, array $owner, array $target_item, array $thr_parent): bool
	{
		if (DI::config()->get('system', 'imap_disabled')) {
			return true;
		}

		$addr = $contact['addr'];
		if (!strlen($addr)) {
			return true;
		}

		if ($cmd != self::POST) {
			return true;
		}

		if ($target_item['verb'] != Activity::POST) {
			return true;
		}

		if (!empty($thr_parent['object'])) {
			$data = json_decode($thr_parent['object'], true);
			if (!empty($data['reply_to'])) {
				$addr = $data['reply_to'][0]['mailbox'] . '@' . $data['reply_to'][0]['host'];
				DI::logger()->info('Use "reply-to" address of the thread parent', ['addr' => $addr]);
			} elseif (!empty($data['from'])) {
				$addr = $data['from'][0]['mailbox'] . '@' . $data['from'][0]['host'];
				DI::logger()->info('Use "from" address of the thread parent', ['addr' => $addr]);
			}
		}

		$local_user = DBA::selectFirst('user', [], ['uid' => $owner['uid']]);
		if (!DBA::isResult($local_user)) {
			return true;
		}

		DI::logger()->info('About to deliver via mail', ['guid' => $target_item['guid'], 'to' => $addr]);

		$reply_to = '';
		$mailacct = DBA::selectFirst('mailacct', ['reply_to'], ['uid' => $owner['uid']]);
		if (DBA::isResult($mailacct) && !empty($mailacct['reply_to'])) {
			$reply_to = $mailacct['reply_to'];
		}

		$subject = ($target_item['title'] ? Email::encodeHeader($target_item['title'], 'UTF-8') : DI::l10n()->t("\x28no subject\x29"));

		// only expose our real email address to true friends

		if (($contact['rel'] == Contact::FRIEND) && !$contact['blocked']) {
			if ($reply_to) {
				$headers = 'From: ' . Email::encodeHeader($local_user['username'], 'UTF-8') . ' <' . $reply_to . '>' . "\n";
				$headers .= 'Sender: ' . $local_user['email'] . "\n";
			} else {
				$headers = 'From: ' . Email::encodeHeader($local_user['username'], 'UTF-8') . ' <' . $local_user['email'] . '>' . "\n";
			}
		} else {
			$sender  = DI::config()->get('config', 'sender_email', 'noreply@' . DI::baseUrl()->getHost());
			$headers = 'From: '. Email::encodeHeader($local_user['username'], 'UTF-8') . ' <' . $sender . '>' . "\n";
		}

		$headers .= 'Message-Id: <' . Email::iri2msgid($target_item['uri']) . '>' . "\n";

		if ($target_item['uri'] !== $target_item['parent-uri']) {
			$headers .= 'References: <' . Email::iri2msgid($target_item['parent-uri']) . '>';

			// Export more references on deeper nested threads
			if (($target_item['thr-parent'] != '') && ($target_item['thr-parent'] != $target_item['parent-uri'])) {
				$headers .= ' <' . Email::iri2msgid($target_item['thr-parent']) . '>';
			}

			$headers .= "\n";

			if (empty($target_item['title'])) {
				$condition = ['uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
				$title     = Post::selectFirst(['title'], $condition);

				if (DBA::isResult($title) && ($title['title'] != '')) {
					$subject = $title['title'];
				} else {
					$condition = ['parent-uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
					$title     = Post::selectFirst(['title'], $condition);

					if (DBA::isResult($title) && ($title['title'] != '')) {
						$subject = $title['title'];
					}
				}
			}

			if (strncasecmp($subject, 'RE:', 3)) {
				$subject = 'Re: ' . $subject;
			}
		}

		// Try to send email
		$success = Email::send($addr, $subject, $headers, $target_item);

		if ($success) {
			// Success
			Post\DeliveryData::incrementQueueDone($target_item['uri-id'], Post\DeliveryData::MAIL);
			DI::logger()->info('Delivered via mail', ['guid' => $target_item['guid'], 'to' => $addr, 'subject' => $subject]);
		} else {
			// Failed
			DI::logger()->warning('Delivery of mail has FAILED', ['to' => $addr, 'subject' => $subject, 'guid' => $target_item['guid']]);
		}
		return $success;
	}
}

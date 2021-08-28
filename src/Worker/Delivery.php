<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use Friendica\Protocol\Activity;
use Friendica\Util\Network;
use Friendica\Core\Worker;
use Friendica\Model\DiasporaContact;
use Friendica\Model\Item;
use Friendica\Protocol\Relay;

class Delivery
{
	const MAIL          = 'mail';
	const SUGGESTION    = 'suggest';
	const RELOCATION    = 'relocate';
	const DELETION      = 'drop';
	const POST          = 'wall-new';
	const POKE          = 'poke';
	const UPLINK        = 'uplink';
	const REMOVAL       = 'removeme';
	const PROFILEUPDATE = 'profileupdate';

	public static function execute(string $cmd, int $post_uriid, int $contact_id, int $sender_uid = 0)
	{
		Logger::info('Invoked', ['cmd' => $cmd, 'target' => $post_uriid, 'sender_uid' => $sender_uid, 'contact' => $contact_id]);

		$top_level = false;
		$followup = false;
		$public_message = false;

		$items = [];
		if ($cmd == self::MAIL) {
			$target_item = DBA::selectFirst('mail', [], ['id' => $post_uriid]);
			if (!DBA::isResult($target_item)) {
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::SUGGESTION) {
			$target_item = DBA::selectFirst('fsuggest', [], ['id' => $post_uriid]);
			if (!DBA::isResult($target_item)) {
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::RELOCATION) {
			$uid = $post_uriid;
			$target_item = [];
		} else {
			$item = Model\Post::selectFirst(['id', 'parent'], ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
			if (!DBA::isResult($item) || empty($item['parent'])) {
				Logger::warning('Post not found', ['uri-id' => $post_uriid, 'uid' => $sender_uid]);
				return;
			}
			$target_id = intval($item['id']);
			$parent_id = intval($item['parent']);

			$condition = ['id' => [$target_id, $parent_id], 'visible' => true];
			$params = ['order' => ['id']];
			$itemdata = Model\Post::select(Item::DELIVER_FIELDLIST, $condition, $params);

			while ($item = Model\Post::fetch($itemdata)) {
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
				Logger::log('Item ' . $target_id . "wasn't found. Quitting here.");
				return;
			}

			if (empty($parent)) {
				Logger::log('Parent ' . $parent_id . ' for item ' . $target_id . "wasn't found. Quitting here.");
				self::setFailedQueue($cmd, $target_item);
				return;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				Logger::log('Only public users for item ' . $target_id, Logger::DEBUG);
				self::setFailedQueue($cmd, $target_item);
				return;
			}

			$condition = ['uri' => $target_item['thr-parent'], 'uid' => $target_item['uid']];
			$thr_parent = Model\Post::selectFirst(['network', 'object'], $condition);
			if (!DBA::isResult($thr_parent)) {
				// Shouldn't happen. But when this does, we just take the parent as thread parent.
				// That's totally okay for what we use this variable here.
				$thr_parent = $parent;
			}

			if (!empty($contact_id) && Model\Contact::isArchived($contact_id)) {
				Logger::info('Contact is archived', ['id' => $contact_id, 'cmd' => $cmd, 'item' => $target_item['id']]);
				self::setFailedQueue($cmd, $target_item);
				return;
			}

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			$top_level = $target_item['gravity'] == GRAVITY_PARENT;

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = DI::baseUrl()->getHostname();
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
				Logger::log('Followup ' . $target_item["guid"], Logger::DEBUG);
				// local followup to remote post
				$followup = true;
			}

			if (empty($parent['allow_cid'])
				&& empty($parent['allow_gid'])
				&& empty($parent['deny_cid'])
				&& empty($parent['deny_gid'])
				&& ($parent["private"] != Model\Item::PRIVATE)) {
				$public_message = true;
			}
		}

		if (empty($items)) {
			Logger::notice('No delivery data', ['command' => $cmd, 'uri-id' => $post_uriid, 'cid' => $contact_id]);
		}

		$owner = Model\User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			self::setFailedQueue($cmd, $target_item);
			return;
		}

		// We don't deliver our items to blocked, archived or pending contacts, and not to ourselves either
		$contact = DBA::selectFirst('contact', [],
			['id' => $contact_id, 'archive' => false, 'blocked' => false, 'pending' => false, 'self' => false]
		);
		if (!DBA::isResult($contact)) {
			self::setFailedQueue($cmd, $target_item);
			return;
		}

		if (Network::isUrlBlocked($contact['url'])) {
			self::setFailedQueue($cmd, $target_item);
			return;
		}

		$protocol = Model\GServer::getProtocol($contact['gsid'] ?? 0);

		// Transmit via Diaspora if the thread had started as Diaspora post.
		// Also transmit via Diaspora if this is a direct answer to a Diaspora comment.
		// This is done since the uri wouldn't match (Diaspora doesn't transmit it)
		// Also transmit relayed posts from Diaspora contacts via Diaspora.
		if (!empty($parent) && !empty($thr_parent) && in_array(Protocol::DIASPORA, [$parent['network'], $thr_parent['network'], $target_item['network']])) {
			$contact['network'] = Protocol::DIASPORA;
		}

		Logger::notice('Delivering', ['cmd' => $cmd, 'uri-id' => $post_uriid, 'followup' => $followup, 'network' => $contact['network']]);

		switch ($contact['network']) {
			case Protocol::DFRN:
				self::deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup, $protocol);
				break;

			case Protocol::DIASPORA:
				self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
				break;

			case Protocol::MAIL:
				self::deliverMail($cmd, $contact, $owner, $target_item, $thr_parent);
				break;

			default:
				break;
		}

		return;
	}

	/**
	 * Increased the "failed" counter in the item delivery data
	 *
	 * @param string $cmd  Command
	 * @param array  $item Item array
	 */
	private static function setFailedQueue(string $cmd, array $item)
	{
		if (!in_array($cmd, [Delivery::POST, Delivery::POKE])) {
			return;
		}

		Model\Post\DeliveryData::incrementQueueFailed($item['uri-id'] ?? $item['id']);
	}

	/**
	 * Deliver content via DFRN
	 *
	 * @param string  $cmd             Command
	 * @param array   $contact         Contact record of the receiver
	 * @param array   $owner           Owner record of the sender
	 * @param array   $items           Item record of the content and the parent
	 * @param array   $target_item     Item record of the content
	 * @param boolean $public_message  Is the content public?
	 * @param boolean $top_level       Is it a thread starter?
	 * @param boolean $followup        Is it an answer to a remote post?
	 * @param int     $server_protocol The protocol of the server
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup, $server_protocol)
	{
		// Transmit Diaspora reshares via Diaspora if the Friendica contact support Diaspora
		if (Diaspora::isReshare($target_item['body']) && !empty(DiasporaContact::getByURL($contact['addr'], false))) {
			Logger::info('Reshare will be transmitted via Diaspora', ['url' => $contact['url'], 'guid' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
			self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
			return;
		}

		Logger::info('Deliver ' . (($target_item['guid'] ?? '') ?: $target_item['id']) . ' via DFRN to ' . (($contact['addr'] ?? '') ?: $contact['url']));

		if ($cmd == self::MAIL) {
			$item = $target_item;
			$item['body'] = Model\Item::fixPrivatePhotos($item['body'], $owner['uid'], null, $item['contact-id']);
			$atom = DFRN::mail($item, $owner);
		} elseif ($cmd == self::SUGGESTION) {
			$item = $target_item;
			$atom = DFRN::fsuggest($item, $owner);
			DBA::delete('fsuggest', ['id' => $item['id']]);
		} elseif ($cmd == self::RELOCATION) {
			$atom = DFRN::relocate($owner, $owner['uid']);
		} elseif ($followup) {
			$msgitems = [$target_item];
			$atom = DFRN::entries($msgitems, $owner);
		} else {
			if ($target_item['deleted']) {
				$msgitems = [$target_item];
			} else {
				$msgitems = [];
				foreach ($items as $item) {
					// Only add the parent when we don't delete other items.
					if (($target_item['id'] == $item['id']) || ($cmd != self::DELETION)) {
						$item["entry:comment-allow"] = true;
						$item["entry:cid"] = ($top_level ? $contact['id'] : 0);
						$msgitems[] = $item;
					}
				}
			}
			$atom = DFRN::entries($msgitems, $owner);
		}

		Logger::debug('Notifier entry: ' . $contact["url"] . ' ' . (($target_item['guid'] ?? '') ?: $target_item['id']) . ' entry: ' . $atom);

		$protocol = Model\Post\DeliveryData::DFRN;

		// We don't have a relationship with contacts on a public post.
		// Se we transmit with the new method and via Diaspora as a fallback
		if (!empty($items) && (($items[0]['uid'] == 0) || ($contact['uid'] == 0))) {
			// Transmit in public if it's a relay post
			$public_dfrn = ($contact['contact-type'] == Model\Contact::TYPE_RELAY);

			$deliver_status = DFRN::transmit($owner, $contact, $atom, $public_dfrn);

			// We never spool failed relay deliveries
			if ($public_dfrn) {
				Logger::info('Relay delivery to ' . $contact["url"] . ' with guid ' . $target_item["guid"] . ' returns ' . $deliver_status);

				if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
					if (($deliver_status >= 200) && ($deliver_status <= 299)) {
						Model\Post\DeliveryData::incrementQueueDone($target_item['uri-id'], $protocol);

						Model\GServer::setProtocol($contact['gsid'] ?? 0, $protocol);
					} else {
						Model\Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
					}
				}
				return;
			}

			if ((($deliver_status < 200) || ($deliver_status > 299)) && (empty($server_protocol) || ($server_protocol == Model\Post\DeliveryData::LEGACY_DFRN))) {
				// Transmit via Diaspora if not possible via Friendica
				self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
				return;
			}
		} else {
			// DFRN payload over Diaspora transport layer
			$deliver_status = DFRN::transmit($owner, $contact, $atom);
		}

		Logger::info('DFRN Delivery', ['cmd' => $cmd, 'url' => $contact['url'], 'guid' => ($target_item['guid'] ?? '') ?: $target_item['id'], 'return' => $deliver_status]);

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Model\Contact::unmarkForArchival($contact);

			Model\GServer::setProtocol($contact['gsid'] ?? 0, $protocol);

			if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\Post\DeliveryData::incrementQueueDone($target_item['uri-id'], $protocol);
			}
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Model\Contact::markForArchival($contact);

			Logger::info('Delivery failed: defer message', ['id' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
			if (!Worker::defer() && in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
			}
		}
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup)
	{
		// We don't treat Forum posts as "wall-to-wall" to be able to post them via Diaspora
		$walltowall = $top_level && ($owner['id'] != $items[0]['contact-id']) & ($owner['account-type'] != Model\User::ACCOUNT_TYPE_COMMUNITY);

		if ($public_message) {
			$loc = 'public batch ' . $contact['batch'];
		} else {
			$loc = $contact['addr'];
		}

		Logger::notice('Deliver via Diaspora', ['target' => $target_item['id'], 'guid' => $target_item['guid'], 'to' => $loc]);

		if (DI::config()->get('system', 'dfrn_only') || !DI::config()->get('system', 'diaspora_enabled')) {
			return;
		}

		if ($cmd == self::MAIL) {
			Diaspora::sendMail($target_item, $owner, $contact);
			return;
		}

		if ($cmd == self::SUGGESTION) {
			return;
		}

		if (!$contact['pubkey'] && !$public_message) {
			return;
		}

		if ($cmd == self::RELOCATION) {
			$deliver_status = Diaspora::sendAccountMigration($owner, $contact, $owner['uid']);
		} elseif ($target_item['deleted'] && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
			// top-level retraction
			Logger::log('diaspora retract: ' . $loc);
			$deliver_status = Diaspora::sendRetraction($target_item, $owner, $contact, $public_message);
		} elseif ($followup) {
			// send comments and likes to owner to relay
			Logger::log('diaspora followup: ' . $loc);
			$deliver_status = Diaspora::sendFollowup($target_item, $owner, $contact, $public_message);
		} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			Logger::log('diaspora relay: ' . $loc);
			$deliver_status = Diaspora::sendRelay($target_item, $owner, $contact, $public_message);
		} elseif ($top_level && !$walltowall) {
			// currently no workable solution for sending walltowall
			Logger::log('diaspora status: ' . $loc);
			$deliver_status = Diaspora::sendStatus($target_item, $owner, $contact, $public_message);
		} else {
			Logger::log('Unknown mode ' . $cmd . ' for ' . $loc);
			return;
		}

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Model\Contact::unmarkForArchival($contact);

			Model\GServer::setProtocol($contact['gsid'] ?? 0, Model\Post\DeliveryData::DIASPORA);

			if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\Post\DeliveryData::incrementQueueDone($target_item['uri-id'], Model\Post\DeliveryData::DIASPORA);
			}
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Model\Contact::markForArchival($contact);

			// When it is delivered to the public endpoint, we do mark the relay contact for archival as well
			if ($public_message) {
				Relay::markForArchival($contact);
			}

			if (empty($contact['contact-type']) || ($contact['contact-type'] != Model\Contact::TYPE_RELAY)) {
				Logger::info('Delivery failed: defer message', ['id' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
				// defer message for redelivery
				if (!Worker::defer() && in_array($cmd, [Delivery::POST, Delivery::POKE])) {
					Model\Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
				}
			} elseif (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\Post\DeliveryData::incrementQueueFailed($target_item['uri-id']);
			}
		}
	}

	/**
	 * Deliver content via mail
	 *
	 * @param string $cmd         Command
	 * @param array  $contact     Contact record of the receiver
	 * @param array  $owner       Owner record of the sender
	 * @param array  $target_item Item record of the content
	 * @param array  $thr_parent  Item record of the direct parent in the thread
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function deliverMail($cmd, $contact, $owner, $target_item, $thr_parent)
	{
		if (DI::config()->get('system','dfrn_only')) {
			return;
		}

		$addr = $contact['addr'];
		if (!strlen($addr)) {
			return;
		}

		if (!in_array($cmd, [self::POST, self::POKE])) {
			return;
		}

		if ($target_item['verb'] != Activity::POST) {
			return;
		}

		if (!empty($thr_parent['object'])) {
			$data = json_decode($thr_parent['object'], true);
			if (!empty($data['reply_to'])) {
				$addr = $data['reply_to'][0]['mailbox'] . '@' . $data['reply_to'][0]['host'];
				Logger::info('Use "reply-to" address of the thread parent', ['addr' => $addr]);
			} elseif (!empty($data['from'])) {
				$addr = $data['from'][0]['mailbox'] . '@' . $data['from'][0]['host'];
				Logger::info('Use "from" address of the thread parent', ['addr' => $addr]);
			}
		}

		$local_user = DBA::selectFirst('user', [], ['uid' => $owner['uid']]);
		if (!DBA::isResult($local_user)) {
			return;
		}

		Logger::info('About to deliver via mail', ['guid' => $target_item['guid'], 'to' => $addr]);

		$reply_to = '';
		$mailacct = DBA::selectFirst('mailacct', ['reply_to'], ['uid' => $owner['uid']]);
		if (DBA::isResult($mailacct) && !empty($mailacct['reply_to'])) {
			$reply_to = $mailacct['reply_to'];
		}

		$subject  = ($target_item['title'] ? Email::encodeHeader($target_item['title'], 'UTF-8') : DI::l10n()->t("\x28no subject\x29"));

		// only expose our real email address to true friends

		if (($contact['rel'] == Model\Contact::FRIEND) && !$contact['blocked']) {
			if ($reply_to) {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $reply_to . '>' . "\n";
				$headers .= 'Sender: ' . $local_user['email'] . "\n";
			} else {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $local_user['email'] . '>' . "\n";
			}
		} else {
			$sender = DI::config()->get('config', 'sender_email', 'noreply@' . DI::baseUrl()->getHostname());
			$headers  = 'From: '. Email::encodeHeader($local_user['username'], 'UTF-8') . ' <' . $sender . '>' . "\n";
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
				$title = Model\Post::selectFirst(['title'], $condition);

				if (DBA::isResult($title) && ($title['title'] != '')) {
					$subject = $title['title'];
				} else {
					$condition = ['parent-uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
					$title = Model\Post::selectFirst(['title'], $condition);

					if (DBA::isResult($title) && ($title['title'] != '')) {
						$subject = $title['title'];
					}
				}
			}

			if (strncasecmp($subject, 'RE:', 3)) {
				$subject = 'Re: ' . $subject;
			}
		}

		Email::send($addr, $subject, $headers, $target_item);

		Model\Post\DeliveryData::incrementQueueDone($target_item['uri-id'], Model\Post\DeliveryData::MAIL);

		Logger::info('Delivered via mail', ['guid' => $target_item['guid'], 'to' => $addr, 'subject' => $subject]);
	}
}

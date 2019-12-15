<?php
/**
 * @file src/Worker/Delivery.php
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use Friendica\Protocol\Activity;
use Friendica\Util\Strings;
use Friendica\Util\Network;
use Friendica\Core\Worker;

class Delivery extends BaseObject
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

	public static function execute($cmd, $target_id, $contact_id)
	{
		Logger::info('Invoked', ['cmd' => $cmd, 'target' => $target_id, 'contact' => $contact_id]);

		$top_level = false;
		$followup = false;
		$public_message = false;

		$items = [];
		if ($cmd == self::MAIL) {
			$target_item = DBA::selectFirst('mail', [], ['id' => $target_id]);
			if (!DBA::isResult($target_item)) {
				self::setFailedQueue($cmd, $target_id);
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::SUGGESTION) {
			$target_item = DBA::selectFirst('fsuggest', [], ['id' => $target_id]);
			if (!DBA::isResult($target_item)) {
				self::setFailedQueue($cmd, $target_id);
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::RELOCATION) {
			$uid = $target_id;
			$target_item = [];
		} else {
			$item = Model\Item::selectFirst(['parent'], ['id' => $target_id]);
			if (!DBA::isResult($item) || empty($item['parent'])) {
				self::setFailedQueue($cmd, $target_id);
				return;
			}
			$parent_id = intval($item['parent']);

			$condition = ['id' => [$target_id, $parent_id], 'visible' => true, 'moderated' => false];
			$params = ['order' => ['id']];
			$itemdata = Model\Item::select([], $condition, $params);

			while ($item = Model\Item::fetch($itemdata)) {
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
				self::setFailedQueue($cmd, $target_id);
				return;
			}

			if (empty($parent)) {
				Logger::log('Parent ' . $parent_id . ' for item ' . $target_id . "wasn't found. Quitting here.");
				self::setFailedQueue($cmd, $target_id);
				return;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				Logger::log('Only public users for item ' . $target_id, Logger::DEBUG);
				self::setFailedQueue($cmd, $target_id);
				return;
			}

			$condition = ['uri' => $target_item['thr-parent'], 'uid' => $target_item['uid']];
			$thr_parent = Model\Item::selectFirst(['network', 'object'], $condition);
			if (!DBA::isResult($thr_parent)) {
				// Shouldn't happen. But when this does, we just take the parent as thread parent.
				// That's totally okay for what we use this variable here.
				$thr_parent = $parent;
			}

			if (!empty($contact_id) && Model\Contact::isArchived($contact_id)) {
				Logger::info('Contact is archived', ['id' => $contact_id, 'cmd' => $cmd, 'item' => $target_item['id']]);
				self::setFailedQueue($cmd, $target_id);
				return;
			}

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			// When commenting too fast after delivery, a post wasn't recognized as top level post.
			// The count then showed more than one entry. The additional check should help.
			// The check for the "count" should be superfluous, but I'm not totally sure by now, so we keep it.
			if ((($parent['id'] == $target_id) || (count($items) == 1)) && ($parent['uri'] === $parent['parent-uri'])) {
				Logger::log('Top level post');
				$top_level = true;
			}

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = DI::app()->getHostName();
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
				&& !$parent["private"]) {
				$public_message = true;
			}
		}

		if (empty($items)) {
			Logger::log('No delivery data for  ' . $cmd . ' - Item ID: ' .$target_id . ' - Contact ID: ' . $contact_id);
		}

		$owner = Model\User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			self::setFailedQueue($cmd, $target_id);
			return;
		}

		// We don't deliver our items to blocked or pending contacts, and not to ourselves either
		$contact = DBA::selectFirst('contact', [],
			['id' => $contact_id, 'blocked' => false, 'pending' => false, 'self' => false]
		);
		if (!DBA::isResult($contact)) {
			self::setFailedQueue($cmd, $target_id);
			return;
		}

		if (Network::isUrlBlocked($contact['url'])) {
			self::setFailedQueue($cmd, $target_id);
			return;
		}

		// Transmit via Diaspora if the thread had started as Diaspora post.
		// Also transmit via Diaspora if this is a direct answer to a Diaspora comment.
		// This is done since the uri wouldn't match (Diaspora doesn't transmit it)
		if (!empty($parent) && !empty($thr_parent) && in_array(Protocol::DIASPORA, [$parent['network'], $thr_parent['network']])) {
			$contact['network'] = Protocol::DIASPORA;
		}

		Logger::notice('Delivering', ['cmd' => $cmd, 'target' => $target_id, 'followup' => $followup, 'network' => $contact['network']]);

		switch ($contact['network']) {
			case Protocol::DFRN:
				self::deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
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
	 * @param string  $cmd Command
	 * @param integer $id  Item id
	 */
	private static function setFailedQueue(string $cmd, int $id)
	{
		if (!in_array($cmd, [Delivery::POST, Delivery::POKE])) {
			return;
		}

		Model\ItemDeliveryData::incrementQueueFailed($id);
	}

	/**
	 * @brief Deliver content via DFRN
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
	private static function deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup)
	{
		// Transmit Diaspora reshares via Diaspora if the Friendica contact support Diaspora
		if (Diaspora::isReshare($target_item['body']) && !empty(Diaspora::personByHandle($contact['addr'], false))) {
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
			$msgitems = [];
			foreach ($items as $item) {
				// Only add the parent when we don't delete other items.
				if (($target_item['id'] == $item['id']) || ($cmd != self::DELETION)) {
					$item["entry:comment-allow"] = true;
					$item["entry:cid"] = ($top_level ? $contact['id'] : 0);
					$msgitems[] = $item;
				}
			}
			$atom = DFRN::entries($msgitems, $owner);
		}

		Logger::debug('Notifier entry: ' . $contact["url"] . ' ' . (($target_item['guid'] ?? '') ?: $target_item['id']) . ' entry: ' . $atom);

		$basepath =  implode('/', array_slice(explode('/', $contact['url']), 0, 3));

		// perform local delivery if we are on the same site

		if (Strings::compareLink($basepath, System::baseUrl())) {
			$condition = ['nurl' => Strings::normaliseLink($contact['url']), 'self' => true];
			$target_self = DBA::selectFirst('contact', ['uid'], $condition);
			if (!DBA::isResult($target_self)) {
				return;
			}
			$target_uid = $target_self['uid'];

			// Check if the user has got this contact
			$cid = Model\Contact::getIdForURL($owner['url'], $target_uid);
			if (!$cid) {
				// Otherwise there should be a public contact
				$cid = Model\Contact::getIdForURL($owner['url']);
				if (!$cid) {
					return;
				}
			}

			$target_importer = DFRN::getImporter($cid, $target_uid);
			if (empty($target_importer)) {
				// This should never happen
				return;
			}

			DFRN::import($atom, $target_importer);

			if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\ItemDeliveryData::incrementQueueDone($target_item['id'], Model\ItemDeliveryData::DFRN);
			}

			return;
		}

		$protocol = Model\ItemDeliveryData::DFRN;

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
						Model\ItemDeliveryData::incrementQueueDone($target_item['id'], $protocol);
					} else {
						Model\ItemDeliveryData::incrementQueueFailed($target_item['id']);
					}
				}
				return;
			}

			if (($deliver_status < 200) || ($deliver_status > 299)) {
				// Transmit via Diaspora if not possible via Friendica
				self::deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
				return;
			}
		} elseif ($cmd != self::RELOCATION) {
			// DFRN payload over Diaspora transport layer
			$deliver_status = DFRN::transmit($owner, $contact, $atom);
			if ($deliver_status < 200) {
				// Legacy DFRN
				$deliver_status = DFRN::deliver($owner, $contact, $atom);
				$protocol = Model\ItemDeliveryData::LEGACY_DFRN;
			}
		} else {
			$deliver_status = DFRN::deliver($owner, $contact, $atom);
			$protocol = Model\ItemDeliveryData::LEGACY_DFRN;
		}

		Logger::info('DFRN Delivery', ['cmd' => $cmd, 'url' => $contact['url'], 'guid' => ($target_item['guid'] ?? '') ?: $target_item['id'], 'return' => $deliver_status]);

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Model\Contact::unmarkForArchival($contact);

			if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\ItemDeliveryData::incrementQueueDone($target_item['id'], $protocol);
			}
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Model\Contact::markForArchival($contact);

			Logger::info('Delivery failed: defer message', ['id' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
			if (!Worker::defer() && in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\ItemDeliveryData::incrementQueueFailed($target_item['id']);
			}
		}
	}

	/**
	 * @brief Deliver content via Diaspora
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

		if (Config::get('system', 'dfrn_only') || !Config::get('system', 'diaspora_enabled')) {
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

			if (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\ItemDeliveryData::incrementQueueDone($target_item['id'], Model\ItemDeliveryData::DIASPORA);
			}
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Model\Contact::markForArchival($contact);

			// When it is delivered to the public endpoint, we do mark the relay contact for archival as well
			if ($public_message) {
				Diaspora::markRelayForArchival($contact);
			}

			if (empty($contact['contact-type']) || ($contact['contact-type'] != Model\Contact::TYPE_RELAY)) {
				Logger::info('Delivery failed: defer message', ['id' => ($target_item['guid'] ?? '') ?: $target_item['id']]);
				// defer message for redelivery
				if (!Worker::defer() && in_array($cmd, [Delivery::POST, Delivery::POKE])) {
					Model\ItemDeliveryData::incrementQueueFailed($target_item['id']);
				}
			} elseif (in_array($cmd, [Delivery::POST, Delivery::POKE])) {
				Model\ItemDeliveryData::incrementQueueFailed($target_item['id']);
			}
		}
	}

	/**
	 * @brief Deliver content via mail
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
		if (Config::get('system','dfrn_only')) {
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

		$subject  = ($target_item['title'] ? Email::encodeHeader($target_item['title'], 'UTF-8') : L10n::t("\x28no subject\x29"));

		// only expose our real email address to true friends

		if (($contact['rel'] == Model\Contact::FRIEND) && !$contact['blocked']) {
			if ($reply_to) {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $reply_to . '>' . "\n";
				$headers .= 'Sender: ' . $local_user['email'] . "\n";
			} else {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $local_user['email'] . '>' . "\n";
			}
		} else {
			$headers  = 'From: '. Email::encodeHeader($local_user['username'], 'UTF-8') . ' <noreply@' . DI::app()->getHostName() . '>' . "\n";
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
				$title = Model\Item::selectFirst(['title'], $condition);

				if (DBA::isResult($title) && ($title['title'] != '')) {
					$subject = $title['title'];
				} else {
					$condition = ['parent-uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
					$title = Model\Item::selectFirst(['title'], $condition);

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

		Model\ItemDeliveryData::incrementQueueDone($target_item['id'], Model\ItemDeliveryData::MAIL);

		Logger::info('Delivered via mail', ['guid' => $target_item['guid'], 'to' => $addr, 'subject' => $subject]);
	}
}

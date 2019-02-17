<?php
/**
 * @file src/Worker/Delivery.php
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\ItemDeliveryData;
use Friendica\Model\Queue;
use Friendica\Model\User;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class Delivery extends AbstractWorker
{
	const MAIL          = 'mail';
	const SUGGESTION    = 'suggest';
	const RELOCATION    = 'relocate';
	const DELETION      = 'drop';
	const POST          = 'wall-new';
	const COMMENT       = 'comment-new';
	const REMOVAL       = 'removeme';
	const PROFILEUPDATE = 'profileupdate';

	/**
	 * {@inheritdoc}
	 * 
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 3)) {
			return;
		}

		list($cmd, $target_id, $contact_id) = $parameters;

		$this->logger->info('Invoked: ' . $target_id . ' to ' . $contact_id, ['cmd' => $cmd]);

		$top_level = false;
		$followup = false;
		$public_message = false;

		$items = [];
		if ($cmd == self::MAIL) {
			$target_item = DBA::selectFirst('mail', [], ['id' => $target_id]);
			if (!DBA::isResult($target_item)) {
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::SUGGESTION) {
			$target_item = DBA::selectFirst('fsuggest', [], ['id' => $target_id]);
			if (!DBA::isResult($target_item)) {
				return;
			}
			$uid = $target_item['uid'];
		} elseif ($cmd == self::RELOCATION) {
			$uid = $target_id;
			$target_item = [];
		} else {
			$item = Item::selectFirst(['parent'], ['id' => $target_id]);
			if (!DBA::isResult($item) || empty($item['parent'])) {
				return;
			}
			$parent_id = intval($item['parent']);

			$condition = ['id' => [$target_id, $parent_id], 'moderated' => false];
			$params = ['order' => ['id']];
			$itemdata = Item::select([], $condition, $params);

			while ($item = Item::fetch($itemdata)) {
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
				$this->logger->info('Item wasn\'t found. Quitting here.', ['item' => $target_id]);
				return;
			}

			if (empty($parent)) {
				$this->logger->info('Parent for item wasn\'t found. Quitting here.', ['item' => $target_id, 'parent' => $parent_id]);
				return;
			}

			if (!empty($target_item['contact-uid'])) {
				$uid = $target_item['contact-uid'];
			} elseif (!empty($target_item['uid'])) {
				$uid = $target_item['uid'];
			} else {
				$this->logger->info('Only public users for item.', ['item' => $target_id]);
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
				$this->logger->info('Top level post');
				$top_level = true;
			}

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = $this->app->getHostName();
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
				$this->logger->info('Followup.', ['guid' => $target_item['guid']]);
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
			$this->logger->info('No delivery data for  ' . $cmd . ' - Item ID: ' .$target_id . ' - Contact ID: ' . $contact_id);
		}

		$owner = User::getOwnerDataById($uid);
		if (!DBA::isResult($owner)) {
			return;
		}

		// We don't deliver our items to blocked or pending contacts, and not to ourselves either
		$contact = DBA::selectFirst('contact', [],
			['id' => $contact_id, 'blocked' => false, 'pending' => false, 'self' => false]
		);
		if (!DBA::isResult($contact)) {
			return;
		}

		if (Network::isUrlBlocked($contact['url'])) {
			return;
		}

		// Transmit via Diaspora if the thread had started as Diaspora post
		// This is done since the uri wouldn't match (Diaspora doesn't transmit it)
		if (isset($parent) && ($parent['network'] == Protocol::DIASPORA) && ($contact['network'] == Protocol::DFRN)) {
			$contact['network'] = Protocol::DIASPORA;
		}

		$this->logger->info("Delivering " . $cmd . " followup=$followup - via network " . $contact['network']);

		switch ($contact['network']) {

			case Protocol::DFRN:
				$this->deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);

				if (in_array($cmd, [Delivery::POST, Delivery::COMMENT])) {
					ItemDeliveryData::incrementQueueDone($target_id);
				}
				break;

			case Protocol::DIASPORA:
				$this->deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);

				if (in_array($cmd, [Delivery::POST, Delivery::COMMENT])) {
					ItemDeliveryData::incrementQueueDone($target_id);
				}
				break;

			case Protocol::OSTATUS:
				// Do not send to otatus if we are not configured to send to public networks
				if ($owner['prvnets']) {
					break;
				}
				if (Config::get('system','ostatus_disabled') || Config::get('system','dfrn_only')) {
					break;
				}

				// There is currently no code here to distribute anything to OStatus.
				// This is done in "notifier.php" (See "url_recipients" and "push_notify")
				break;

			case Protocol::MAIL:
				$this->deliverMail($cmd, $contact, $owner, $target_item);
				break;

			default:
				break;
		}

		return;
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
	private function deliverDFRN($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup)
	{
		$this->logger->info('Deliver ' . defaults($target_item, 'guid', $target_item['id']) . ' via DFRN to ' . (empty($contact['addr']) ? $contact['url'] : $contact['addr']));

		if ($cmd == self::MAIL) {
			$item = $target_item;
			$item['body'] = Item::fixPrivatePhotos($item['body'], $owner['uid'], null, $item['contact-id']);
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

		$this->logger->debug('Notifier entry: ' . $contact["url"] . ' ' . defaults($target_item, 'guid', $target_item['id']) . ' entry: ' . $atom);

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
			$cid = Contact::getIdForURL($owner['url'], $target_uid);
			if (!$cid) {
				// Otherwise there should be a public contact
				$cid = Contact::getIdForURL($owner['url']);
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
			return;
		}

		// We don't have a relationship with contacts on a public post.
		// Se we transmit with the new method and via Diaspora as a fallback
		if (!empty($items) && (($items[0]['uid'] == 0) || ($contact['uid'] == 0))) {
			// Transmit in public if it's a relay post
			$public_dfrn = ($contact['contact-type'] == Contact::TYPE_RELAY);

			$deliver_status = DFRN::transmit($owner, $contact, $atom, $public_dfrn);

			// We never spool failed relay deliveries
			if ($public_dfrn) {
				$this->logger->info('Relay delivery to ' . $contact["url"] . ' with guid ' . $target_item["guid"] . ' returns ' . $deliver_status);
				return;
			}

			if (($deliver_status < 200) || ($deliver_status > 299)) {
				// Transmit via Diaspora if not possible via Friendica
				$this->deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
				return;
			}
		} elseif ($cmd != self::RELOCATION) {
			$deliver_status = DFRN::deliver($owner, $contact, $atom);
		} else {
			$deliver_status = DFRN::deliver($owner, $contact, $atom, false, true);
		}

		$this->logger->info('Delivery to ' . $contact['url'] . ' with guid ' . defaults($target_item, 'guid', $target_item['id']) . ' returns ' . $deliver_status);

		if ($deliver_status < 0) {
			$this->logger->info('Delivery failed: queuing message ' . defaults($target_item, 'guid', $target_item['id']));
			Queue::add($contact['id'], Protocol::DFRN, $atom, false, $target_item['guid']);
		}

		if (($deliver_status >= 200) && ($deliver_status <= 299)) {
			// We successfully delivered a message, the contact is alive
			Contact::unmarkForArchival($contact);
		} else {
			// The message could not be delivered. We mark the contact as "dead"
			Contact::markForArchival($contact);

			// Transmit via Diaspora when all other methods (legacy DFRN and new one) are failing.
			// This is a fallback for systems that don't know the new methods.
			$this->deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup);
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
	private function deliverDiaspora($cmd, $contact, $owner, $items, $target_item, $public_message, $top_level, $followup)
	{
		// We don't treat Forum posts as "wall-to-wall" to be able to post them via Diaspora
		$walltowall = $top_level && ($owner['id'] != $items[0]['contact-id']) & ($owner['account-type'] != User::ACCOUNT_TYPE_COMMUNITY);

		if ($public_message) {
			$loc = 'public batch ' . $contact['batch'];
		} else {
			$loc = $contact['addr'];
		}

		$this->logger->info('Deliver ' . defaults($target_item, 'guid', $target_item['id']) . ' via Diaspora to ' . $loc);

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
			Diaspora::sendAccountMigration($owner, $contact, $owner['uid']);
			return;
		} elseif ($target_item['deleted'] && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
			// top-level retraction
			$this->logger->info('diaspora retract: ' . $loc);
			Diaspora::sendRetraction($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($followup) {
			// send comments and likes to owner to relay
			$this->logger->info('diaspora followup: ' . $loc);
			Diaspora::sendFollowup($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
			// we are the relay - send comments, likes and relayable_retractions to our conversants
			$this->logger->info('diaspora relay: ' . $loc);
			Diaspora::sendRelay($target_item, $owner, $contact, $public_message);
			return;
		} elseif ($top_level && !$walltowall) {
			// currently no workable solution for sending walltowall
			$this->logger->info('diaspora status: ' . $loc);
			Diaspora::sendStatus($target_item, $owner, $contact, $public_message);
			return;
		}

		$this->logger->info('Unknown mode ' . $cmd . ' for ' . $loc);
	}

	/**
	 * @brief Deliver content via mail
	 *
	 * @param string $cmd         Command
	 * @param array  $contact     Contact record of the receiver
	 * @param array  $owner       Owner record of the sender
	 * @param array  $target_item Item record of the content
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function deliverMail($cmd, $contact, $owner, $target_item)
	{
		if (Config::get('system','dfrn_only')) {
			return;
		}
		// WARNING: does not currently convert to RFC2047 header encodings, etc.

		$addr = $contact['addr'];
		if (!strlen($addr)) {
			return;
		}

		if (!in_array($cmd, [self::POST, self::COMMENT])) {
			return;
		}

		$local_user = DBA::selectFirst('user', [], ['uid' => $owner['uid']]);
		if (!DBA::isResult($local_user)) {
			return;
		}

		$this->logger->info('Deliver ' . $target_item["guid"] . ' via mail to ' . $contact['addr']);

		$reply_to = '';
		$mailacct = DBA::selectFirst('mailacct', ['reply_to'], ['uid' => $owner['uid']]);
		if (DBA::isResult($mailacct) && !empty($mailacct['reply_to'])) {
			$reply_to = $mailacct['reply_to'];
		}

		$subject  = ($target_item['title'] ? Email::encodeHeader($target_item['title'], 'UTF-8') : L10n::t("\x28no subject\x29"));

		// only expose our real email address to true friends

		if (($contact['rel'] == Contact::FRIEND) && !$contact['blocked']) {
			if ($reply_to) {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8') . ' <' . $reply_to.'>' . "\n";
				$headers .= 'Sender: ' . $local_user['email'] . "\n";
			} else {
				$headers  = 'From: ' . Email::encodeHeader($local_user['username'],'UTF-8').' <' . $local_user['email'] . '>' . "\n";
			}
		} else {
			$headers  = 'From: '. Email::encodeHeader($local_user['username'], 'UTF-8') . ' <noreply@' . $this->app->getHostName() . '>' . "\n";
		}

		$headers .= 'Message-Id: <' . Email::iri2msgid($target_item['uri']) . '>' . "\n";

		if ($target_item['uri'] !== $target_item['parent-uri']) {
			$headers .= "References: <" . Email::iri2msgid($target_item["parent-uri"]) . ">";

			// If Threading is enabled, write down the correct parent
			if (($target_item["thr-parent"] != "") && ($target_item["thr-parent"] != $target_item["parent-uri"])) {
				$headers .= " <".Email::iri2msgid($target_item["thr-parent"]).">";
			}

			$headers .= "\n";

			if (empty($target_item['title'])) {
				$condition = ['uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
				$title = Item::selectFirst(['title'], $condition);

				if (DBA::isResult($title) && ($title['title'] != '')) {
					$subject = $title['title'];
				} else {
					$condition = ['parent-uri' => $target_item['parent-uri'], 'uid' => $owner['uid']];
					$title = Item::selectFirst(['title'], $condition);

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
	}
}

<?php
/**
 * @file src/Worker/Notifier.php
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Content\Text;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\PushSubscriber;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;

require_once 'include/dba.php';
require_once 'include/items.php';

/*
 * The notifier is typically called with:
 *
 *		Worker::add(PRIORITY_HIGH, "Notifier", COMMAND, ITEM_ID);
 *
 * where COMMAND is one of the following:
 *
 *		activity				(in diaspora.php, dfrn_confirm.php, profiles.php)
 *		comment-import			(in diaspora.php, items.php)
 *		comment-new				(in item.php)
 *		drop					(in diaspora.php, items.php, photos.php)
 *		edit_post				(in item.php)
 *		event					(in events.php)
 *		like					(in like.php, poke.php)
 *		mail					(in message.php)
 *		suggest					(in fsuggest.php)
 *		tag						(in photos.php, poke.php, tagger.php)
 *		tgroup					(in items.php)
 *		wall-new				(in photos.php, item.php)
 *		removeme				(in Contact.php)
 * 		relocate				(in uimport.php)
 *
 * and ITEM_ID is the id of the item in the database that needs to be sent to others.
 */

class Notifier
{
	public static function execute($cmd, $item_id)
	{
		$a = BaseObject::getApp();

		logger('notifier: invoked: '.$cmd.': '.$item_id, LOGGER_DEBUG);

		$top_level = false;
		$recipients = [];
		$url_recipients = [];

		$normal_mode = true;
		$recipients_relocate = [];

		if ($cmd == Delivery::MAIL) {
			$normal_mode = false;
			$message = DBA::selectFirst('mail', ['uid', 'contact-id'], ['id' => $item_id]);
			if (!DBA::isResult($message)) {
				return;
			}
			$uid = $message['uid'];
			$recipients[] = $message['contact-id'];
		} elseif ($cmd == Delivery::SUGGESTION) {
			$normal_mode = false;
			$suggest = DBA::selectFirst('fsuggest', ['uid', 'cid'], ['id' => $item_id]);
			if (!DBA::isResult($suggest)) {
				return;
			}
			$uid = $suggest['uid'];
			$recipients[] = $suggest['cid'];
		} elseif ($cmd == Delivery::REMOVAL) {
			$r = q("SELECT `contact`.*, `user`.`prvkey` AS `uprvkey`,
					`user`.`timezone`, `user`.`nickname`, `user`.`sprvkey`, `user`.`spubkey`,
					`user`.`page-flags`, `user`.`prvnets`, `user`.`account-type`, `user`.`guid`
				FROM `contact` INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
					WHERE `contact`.`uid` = %d AND `contact`.`self` LIMIT 1",
					intval($item_id));
			if (!$r) {
				return;
			}
			$user = $r[0];

			$r = q("SELECT * FROM `contact` WHERE NOT `self` AND `uid` = %d", intval($item_id));
			if (!$r) {
				return;
			}
			foreach ($r as $contact) {
				Contact::terminateFriendship($user, $contact, true);
			}

			$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser(0);
			foreach ($inboxes as $inbox) {
				logger('Account removal for user ' . $item_id . ' to ' . $inbox .' via ActivityPub', LOGGER_DEBUG);
				Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'APDelivery', Delivery::REMOVAL, '', $inbox, $item_id);
			}

			return;
		} elseif ($cmd == Delivery::RELOCATION) {
			$normal_mode = false;
			$uid = $item_id;

			$recipients_relocate = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `self` AND `network` IN ('%s', '%s')",
						intval($uid), Protocol::DFRN, Protocol::DIASPORA);
		} else {
			// find ancestors
			$condition = ['id' => $item_id, 'visible' => true, 'moderated' => false];
			$target_item = Item::selectFirst([], $condition);

			if (!DBA::isResult($target_item) || !intval($target_item['parent'])) {
				return;
			}

			$parent_id = intval($target_item['parent']);
			$uid = $target_item['contact-uid'];
			$updated = $target_item['edited'];

			$condition = ['parent' => $parent_id, 'visible' => true, 'moderated' => false];
			$params = ['order' => ['id']];
			$ret = Item::select([], $condition, $params);

			if (!DBA::isResult($ret)) {
				return;
			}

			$items = Item::inArray($ret);

			// avoid race condition with deleting entries
			if ($items[0]['deleted']) {
				foreach ($items as $item) {
					$item['deleted'] = 1;
				}
			}

			if ((count($items) == 1) && ($items[0]['id'] === $target_item['id']) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
				logger('notifier: top level post');
				$top_level = true;
			}
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			return;
		}

		$walltowall = ($top_level && ($owner['id'] != $items[0]['contact-id']) ? true : false);

		// Should the post be transmitted to Diaspora?
		$diaspora_delivery = true;

		// If this is a public conversation, notify the feed hub
		$public_message = true;

		// Do a PuSH
		$push_notify = false;

		// Deliver directly to a forum, don't PuSH
		$direct_forum_delivery = false;

		$followup = false;
		$recipients_followup = [];
		$conversants = [];

		if (!in_array($cmd, [Delivery::MAIL, Delivery::SUGGESTION, Delivery::RELOCATION])) {
			$parent = $items[0];

			self::activityPubDelivery($a, $cmd, $item_id, $uid, $target_item, $parent);

			$fields = ['network', 'author-id', 'owner-id'];
			$condition = ['uri' => $target_item["thr-parent"], 'uid' => $target_item["uid"]];
			$thr_parent = Item::selectFirst($fields, $condition);

			logger('GUID: '.$target_item["guid"].': Parent is '.$parent['network'].'. Thread parent is '.$thr_parent['network'], LOGGER_DEBUG);

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.

			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = str_replace('www.','',$a->getHostName());
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


			if (($cmd === 'uplink') && (intval($parent['forum_mode']) == 1) && !$top_level) {
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
			if (self::isForumPost($target_item, $owner)) {
				$relay_to_owner = true;
				$direct_forum_delivery = true;
			}

			// Avoid that comments in a forum thread are sent to OStatus
			if (self::isForumPost($parent, $owner)) {
				$direct_forum_delivery = true;
			}

			if ($relay_to_owner) {
				// local followup to remote post
				$followup = true;
				$public_message = false; // not public
				$conversant_str = DBA::escape($parent['contact-id']);
				$recipients = [$parent['contact-id']];
				$recipients_followup  = [$parent['contact-id']];

				logger('notifier: followup '.$target_item["guid"].' to '.$conversant_str, LOGGER_DEBUG);

				//if (!$target_item['private'] && $target_item['wall'] &&
				if (!$target_item['private'] &&
					(strlen($target_item['allow_cid'].$target_item['allow_gid'].
						$target_item['deny_cid'].$target_item['deny_gid']) == 0))
					$push_notify = true;

				if (($thr_parent && ($thr_parent['network'] == Protocol::OSTATUS)) || ($parent['network'] == Protocol::OSTATUS)) {
					$push_notify = true;

					if ($parent["network"] == Protocol::OSTATUS) {
						// Distribute the message to the DFRN contacts as if this wasn't a followup since OStatus can't relay comments
						// Currently it is work at progress
						$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s' AND NOT `blocked` AND NOT `pending` AND NOT `archive`",
							intval($uid),
							DBA::escape(Protocol::DFRN)
						);
						if (DBA::isResult($r)) {
							foreach ($r as $rr) {
								$recipients_followup[] = $rr['id'];
							}
						}
					}
				}

				if ($direct_forum_delivery) {
					$push_notify = false;
				}

				logger("Notify ".$target_item["guid"]." via PuSH: ".($push_notify?"Yes":"No"), LOGGER_DEBUG);
			} else {
				$followup = false;

				logger('Distributing directly '.$target_item["guid"], LOGGER_DEBUG);

				// don't send deletions onward for other people's stuff

				if ($target_item['deleted'] && !intval($target_item['wall'])) {
					logger('notifier: ignoring delete notification for non-wall item');
					return;
				}

				if (strlen($parent['allow_cid'])
					|| strlen($parent['allow_gid'])
					|| strlen($parent['deny_cid'])
					|| strlen($parent['deny_gid'])) {
					$public_message = false; // private recipients, not public
				}

				$allow_people = Text::expandAcl($parent['allow_cid']);
				$allow_groups = Group::expand(Text::expandAcl($parent['allow_gid']),true);
				$deny_people  = Text::expandAcl($parent['deny_cid']);
				$deny_groups  = Group::expand(Text::expandAcl($parent['deny_gid']));

				// if our parent is a public forum (forum_mode == 1), uplink to the origional author causing
				// a delivery fork. private groups (forum_mode == 2) do not uplink

				if ((intval($parent['forum_mode']) == 1) && !$top_level && ($cmd !== 'uplink')) {
					Worker::add($a->queue['priority'], 'Notifier', 'uplink', $item_id);
				}

				foreach ($items as $item) {
					$recipients[] = $item['contact-id'];
					$conversants[] = $item['contact-id'];
					// pull out additional tagged people to notify (if public message)
					if ($public_message && strlen($item['inform'])) {
						$people = explode(',',$item['inform']);
						foreach ($people as $person) {
							if (substr($person,0,4) === 'cid:') {
								$recipients[] = intval(substr($person,4));
								$conversants[] = intval(substr($person,4));
							} else {
								$url_recipients[] = substr($person,4);
							}
						}
					}
				}

				if (count($url_recipients)) {
					logger('notifier: '.$target_item["guid"].' url_recipients ' . print_r($url_recipients,true));
				}

				$conversants = array_unique($conversants);

				$recipients = array_unique(array_merge($recipients,$allow_people,$allow_groups));
				$deny = array_unique(array_merge($deny_people,$deny_groups));
				$recipients = array_diff($recipients,$deny);

				$conversant_str = DBA::escape(implode(', ',$conversants));
			}

			// If the thread parent is OStatus then do some magic to distribute the messages.
			// We have not only to look at the parent, since it could be a Friendica thread.
			if (($thr_parent && ($thr_parent['network'] == Protocol::OSTATUS)) || ($parent['network'] == Protocol::OSTATUS)) {
				$diaspora_delivery = false;

				logger('Some parent is OStatus for '.$target_item["guid"]." - Author: ".$thr_parent['author-id']." - Owner: ".$thr_parent['owner-id'], LOGGER_DEBUG);

				// Send a salmon to the parent author
				$probed_contact = DBA::selectFirst('contact', ['url', 'notify'], ['id' => $thr_parent['author-id']]);
				if (DBA::isResult($probed_contact) && !empty($probed_contact["notify"])) {
					logger('Notify parent author '.$probed_contact["url"].': '.$probed_contact["notify"]);
					$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
				}

				// Send a salmon to the parent owner
				$probed_contact = DBA::selectFirst('contact', ['url', 'notify'], ['id' => $thr_parent['owner-id']]);
				if (DBA::isResult($probed_contact) && !empty($probed_contact["notify"])) {
					logger('Notify parent owner '.$probed_contact["url"].': '.$probed_contact["notify"]);
					$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
				}

				// Send a salmon notification to every person we mentioned in the post
				$arr = explode(',',$target_item['tag']);
				foreach ($arr as $x) {
					//logger('Checking tag '.$x, LOGGER_DEBUG);
					$matches = null;
					if (preg_match('/@\[url=([^\]]*)\]/',$x,$matches)) {
							$probed_contact = Probe::uri($matches[1]);
						if ($probed_contact["notify"] != "") {
							logger('Notify mentioned user '.$probed_contact["url"].': '.$probed_contact["notify"]);
							$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
						}
					}
				}

				// It only makes sense to distribute answers to OStatus messages to Friendica and OStatus - but not Diaspora
				$networks = [Protocol::OSTATUS, Protocol::DFRN];
			} else {
				$networks = [Protocol::OSTATUS, Protocol::DFRN, Protocol::DIASPORA, Protocol::MAIL];
			}
		} else {
			$public_message = false;
		}

		// If this is a public message and pubmail is set on the parent, include all your email contacts
		if (!empty($target_item) && function_exists('imap_open') && !Config::get('system','imap_disabled')) {
			if (!strlen($target_item['allow_cid']) && !strlen($target_item['allow_gid'])
				&& !strlen($target_item['deny_cid']) && !strlen($target_item['deny_gid'])
				&& intval($target_item['pubmail'])) {
				$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s'",
					intval($uid),
					DBA::escape(Protocol::MAIL)
				);
				if (DBA::isResult($r)) {
					foreach ($r as $rr) {
						$recipients[] = $rr['id'];
					}
				}
			}
		}

		if (($cmd == Delivery::RELOCATION)) {
			$r = $recipients_relocate;
		} else {
			if ($followup) {
				$recipients = $recipients_followup;
			}
			$condition = ['id' => $recipients, 'self' => false,
				'blocked' => false, 'pending' => false, 'archive' => false];
			if (!empty($networks)) {
				$condition['network'] = $networks;
			}
			$contacts = DBA::select('contact', ['id', 'url', 'network'], $condition);
			$r = DBA::toArray($contacts);
		}

		// delivery loop
		if (DBA::isResult($r)) {
			foreach ($r as $contact) {
				logger("Deliver ".$item_id." to ".$contact['url']." via network ".$contact['network'], LOGGER_DEBUG);

				Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
						'Delivery', $cmd, $item_id, (int)$contact['id']);
			}
		}

		// send salmon slaps to mentioned remote tags (@foo@example.com) in OStatus posts
		// They are especially used for notifications to OStatus users that don't follow us.
		if (!Config::get('system', 'dfrn_only') && count($url_recipients) && ($public_message || $push_notify) && $normal_mode) {
			$slap = OStatus::salmon($target_item, $owner);
			foreach ($url_recipients as $url) {
				if ($url) {
					logger('notifier: urldelivery: ' . $url);
					$deliver_status = Salmon::slapper($owner, $url, $slap);
					/// @TODO Redeliver/queue these items on failure, though there is no contact record
				}
			}
		}

		if ($public_message) {
			$r1 = [];

			if ($diaspora_delivery) {
				$r1 = q("SELECT `batch`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`name`) AS `name`, ANY_VALUE(`network`) AS `network`
					FROM `contact` WHERE `network` = '%s' AND `batch` != ''
					AND `uid` = %d AND `rel` != %d AND NOT `blocked` AND NOT `pending` AND NOT `archive` GROUP BY `batch`",
					DBA::escape(Protocol::DIASPORA),
					intval($owner['uid']),
					intval(Contact::SHARING)
				);

				// Fetch the participation list
				// The function will ensure that there are no duplicates
				$r1 = Diaspora::participantsForThread($item_id, $r1);

				// Add the relay to the list, avoid duplicates
				if (!$followup) {
					$r1 = Diaspora::relayList($item_id, $r1);
				}
			}

			$condition = ['network' => Protocol::DFRN, 'uid' => $owner['uid'], 'blocked' => false,
				'pending' => false, 'archive' => false, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]];

			$r2 = DBA::toArray(DBA::select('contact', ['id', 'name', 'network'], $condition));

			$r = array_merge($r2, $r1);

			if (DBA::isResult($r)) {
				logger('pubdeliver '.$target_item["guid"].': '.print_r($r,true), LOGGER_DEBUG);

				foreach ($r as $rr) {
					// except for Diaspora batch jobs
					// Don't deliver to folks who have already been delivered to

					if (($rr['network'] !== Protocol::DIASPORA) && (in_array($rr['id'], $conversants))) {
						logger('notifier: already delivered id=' . $rr['id']);
						continue;
					}

					if (!in_array($cmd, [Delivery::MAIL, Delivery::SUGGESTION]) && !$followup) {
						logger('notifier: delivery agent: '.$rr['name'].' '.$rr['id'].' '.$rr['network'].' '.$target_item["guid"]);
						Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
								'Delivery', $cmd, $item_id, (int)$rr['id']);
					}
				}
			}

			$push_notify = true;
		}

		// Notify PuSH subscribers (Used for OStatus distribution of regular posts)
		if ($push_notify) {
			logger('Activating internal PuSH for item '.$item_id, LOGGER_DEBUG);

			// Handling the pubsubhubbub requests
			PushSubscriber::publishFeed($owner['uid'], $a->queue['priority']);
		}

		logger('notifier: calling hooks for ' . $cmd . ' ' . $item_id, LOGGER_DEBUG);

		if ($normal_mode) {
			Hook::fork($a->queue['priority'], 'notifier_normal', $target_item);
		}

		Addon::callHooks('notifier_end',$target_item);

		return;
	}

	private static function activityPubDelivery($a, $cmd, $item_id, $uid, $target_item, $parent)
	{
		$inboxes = [];
		$personal = false;

		if ($target_item['origin']) {
			$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($target_item, $uid);
			logger('Origin item ' . $item_id . ' with URL ' . $target_item['uri'] . ' will be distributed.', LOGGER_DEBUG);
		} elseif (!DBA::exists('conversation', ['item-uri' => $target_item['uri'], 'protocol' => Conversation::PARCEL_ACTIVITYPUB])) {
			logger('Remote item ' . $item_id . ' with URL ' . $target_item['uri'] . ' is no AP post. It will not be distributed.', LOGGER_DEBUG);
			return;
		} else {
			// Remote items are transmitted via the personal inboxes.
			// Doing so ensures that the dedicated receiver will get the message.
			$personal = true;
			logger('Remote item ' . $item_id . ' with URL ' . $target_item['uri'] . ' will be distributed.', LOGGER_DEBUG);
		}

		if ($parent['origin']) {
			$parent_inboxes = ActivityPub\Transmitter::fetchTargetInboxes($parent, $uid, $personal);
			$inboxes = array_merge($inboxes, $parent_inboxes);
		}

		if (empty($inboxes)) {
			logger('No inboxes found for item ' . $item_id . ' with URL ' . $target_item['uri'] . '. It will not be distributed.', LOGGER_DEBUG);
			return;
		}

		// Fill the item cache
		ActivityPub\Transmitter::createCachedActivityFromItem($item_id, true);

		foreach ($inboxes as $inbox) {
			logger('Deliver ' . $item_id .' to ' . $inbox .' via ActivityPub', LOGGER_DEBUG);

			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'APDelivery', $cmd, $item_id, $inbox, $uid);
		}
	}

	private static function isForumPost($item, $owner) {
		if (($item['author-id'] == $item['owner-id']) ||
			($owner['id'] == $item['contact-id']) ||
			($item['uri'] != $item['parent-uri'])) {
			return false;
		}

		$fields = ['forum', 'prv'];
		$condition = ['id' => $item['contact-id']];
		$contact = DBA::selectFirst('contact', $fields, $condition);
		if (!DBA::isResult($contact)) {
			// Should never happen
			return false;
		}

		// Is the post from a forum?
		return ($contact['forum'] || $contact['prv']);
	}
}

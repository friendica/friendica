<?php
/**
 * @file src/Worker/Notifier.php
 */
namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;
use dba;

require_once 'include/dba.php';
require_once 'include/html2plain.php';
require_once 'include/datetime.php';
require_once 'include/items.php';
require_once 'include/bbcode.php';

/*
 * This file was at one time responsible for doing all deliveries, but this caused
 * big problems when the process was killed or stalled during the delivery process.
 * It now invokes separate queues that are delivering via delivery.php and pubsubpublish.php.
 */

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
 *		expire					(in items.php)
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

class Notifier {
	public static function execute($cmd, $item_id) {
		global $a;

		logger('notifier: invoked: '.$cmd.': '.$item_id, LOGGER_DEBUG);

		$expire = false;
		$mail = false;
		$fsuggest = false;
		$relocate = false;
		$top_level = false;
		$recipients = [];
		$url_recipients = [];

		$normal_mode = true;

		if ($cmd === 'mail') {
			$normal_mode = false;
			$mail = true;
			$message = q("SELECT * FROM `mail` WHERE `id` = %d LIMIT 1",
					intval($item_id)
			);
			if (!count($message)) {
				return;
			}
			$uid = $message[0]['uid'];
			$recipients[] = $message[0]['contact-id'];
			$item = $message[0];

		} elseif ($cmd === 'expire') {
			$normal_mode = false;
			$expire = true;
			$items = q("SELECT * FROM `item` WHERE `uid` = %d AND `wall` = 1
				AND `deleted` = 1 AND `changed` > UTC_TIMESTAMP() - INTERVAL 10 MINUTE",
				intval($item_id)
			);
			$uid = $item_id;
			$item_id = 0;
			if (!count($items)) {
				return;
			}
		} elseif ($cmd === 'suggest') {
			$normal_mode = false;
			$fsuggest = true;

			$suggest = q("SELECT * FROM `fsuggest` WHERE `id` = %d LIMIT 1",
				intval($item_id)
			);
			if (!count($suggest)) {
				return;
			}
			$uid = $suggest[0]['uid'];
			$recipients[] = $suggest[0]['cid'];
			$item = $suggest[0];
		} elseif ($cmd === 'removeme') {
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
				Contact::terminateFriendship($user, $contact);
			}
			return;
		} elseif ($cmd === 'relocate') {
			$normal_mode = false;
			$relocate = true;
			$uid = $item_id;

			$recipients_relocate = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `self` AND `network` IN ('%s', '%s')",
						intval($uid), NETWORK_DFRN, NETWORK_DIASPORA);
		} else {
			// find ancestors
			$target_item = dba::fetch_first("SELECT `item`.*, `contact`.`uid` AS `cuid` FROM `item`
						INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
						WHERE `item`.`id` = ? AND `visible` AND NOT `moderated`", $item_id);

			if (!DBM::is_result($target_item) || !intval($target_item['parent'])) {
				return;
			}

			$parent_id = intval($target_item['parent']);
			$uid = $target_item['cuid'];
			$updated = $target_item['edited'];

			$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
				FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id` WHERE `parent` = %d AND visible AND NOT moderated ORDER BY `id` ASC",
				intval($parent_id)
			);

			if (!count($items)) {
				return;
			}

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

		// fill this in with a single salmon slap if applicable
		$slap = '';

		if (! ($mail || $fsuggest || $relocate)) {

			$slap = OStatus::salmon($target_item, $owner);

			$parent = $items[0];

			$thr_parent = q("SELECT `network`, `author-link`, `owner-link` FROM `item` WHERE `uri` = '%s' AND `uid` = %d",
				dbesc($target_item["thr-parent"]), intval($target_item["uid"]));

			logger('GUID: '.$target_item["guid"].': Parent is '.$parent['network'].'. Thread parent is '.$thr_parent[0]['network'], LOGGER_DEBUG);

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.

			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			// expire sends an entire group of expire messages and cannot be forwarded.
			// However the conversation owner will be a part of the conversation and will
			// be notified during this run.
			// Other DFRN conversation members will be alerted during polled updates.



			// Diaspora members currently are not notified of expirations, and other networks have
			// either limited or no ability to process deletions. We should at least fix Diaspora
			// by stringing togther an array of retractions and sending them onward.


			$localhost = str_replace('www.','',$a->get_hostname());
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

			if (!$top_level && ($parent['wall'] == 0) && !$expire && (stristr($target_item['uri'],$localhost))) {
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
			if (($target_item['author-link'] != $target_item['owner-link']) &&
				($owner['id'] != $target_item['contact-id']) &&
				($target_item['uri'] === $target_item['parent-uri'])) {

				$fields = ['forum', 'prv'];
				$condition = ['id' => $target_item['contact-id']];
				$contact = dba::selectFirst('contact', $fields, $condition);
				if (!DBM::is_result($contact)) {
					// Should never happen
					return false;
				}

				// Is the post from a forum?
				if ($contact['forum'] || $contact['prv']) {
					$relay_to_owner = true;
					$direct_forum_delivery = true;
				}
			}
			if ($relay_to_owner) {
				// local followup to remote post
				$followup = true;
				$public_message = false; // not public
				$conversant_str = dbesc($parent['contact-id']);
				$recipients = [$parent['contact-id']];
				$recipients_followup  = [$parent['contact-id']];

				logger('notifier: followup '.$target_item["guid"].' to '.$conversant_str, LOGGER_DEBUG);

				//if (!$target_item['private'] && $target_item['wall'] &&
				if (!$target_item['private'] &&
					(strlen($target_item['allow_cid'].$target_item['allow_gid'].
						$target_item['deny_cid'].$target_item['deny_gid']) == 0))
					$push_notify = true;

				if (($thr_parent && ($thr_parent[0]['network'] == NETWORK_OSTATUS)) || ($parent['network'] == NETWORK_OSTATUS)) {
					$push_notify = true;

					if ($parent["network"] == NETWORK_OSTATUS) {
						// Distribute the message to the DFRN contacts as if this wasn't a followup since OStatus can't relay comments
						// Currently it is work at progress
						$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s' AND NOT `blocked` AND NOT `pending` AND NOT `archive`",
							intval($uid),
							dbesc(NETWORK_DFRN)
						);
						if (DBM::is_result($r)) {
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

				$allow_people = expand_acl($parent['allow_cid']);
				$allow_groups = Group::expand(expand_acl($parent['allow_gid']),true);
				$deny_people  = expand_acl($parent['deny_cid']);
				$deny_groups  = Group::expand(expand_acl($parent['deny_gid']));

				// if our parent is a public forum (forum_mode == 1), uplink to the origional author causing
				// a delivery fork. private groups (forum_mode == 2) do not uplink

				if ((intval($parent['forum_mode']) == 1) && !$top_level && ($cmd !== 'uplink')) {
					Worker::add($a->queue['priority'], 'Notifier', 'uplink', $item_id);
				}

				$conversants = [];

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

				$conversant_str = dbesc(implode(', ',$conversants));
			}

			// If the thread parent is OStatus then do some magic to distribute the messages.
			// We have not only to look at the parent, since it could be a Friendica thread.
			if (($thr_parent && ($thr_parent[0]['network'] == NETWORK_OSTATUS)) || ($parent['network'] == NETWORK_OSTATUS)) {
				$diaspora_delivery = false;

				logger('Some parent is OStatus for '.$target_item["guid"]." - Author: ".$thr_parent[0]['author-link']." - Owner: ".$thr_parent[0]['owner-link'], LOGGER_DEBUG);

				// Send a salmon to the parent author
				$r = q("SELECT `url`, `notify` FROM `contact` WHERE `nurl`='%s' AND `uid` IN (0, %d) AND `notify` != ''",
					dbesc(normalise_link($thr_parent[0]['author-link'])),
					intval($uid));
				if (DBM::is_result($r)) {
					$probed_contact = $r[0];
				} else {
					$probed_contact = Probe::uri($thr_parent[0]['author-link']);
				}

				if ($probed_contact["notify"] != "") {
					logger('Notify parent author '.$probed_contact["url"].': '.$probed_contact["notify"]);
					$url_recipients[$probed_contact["notify"]] = $probed_contact["notify"];
				}

				// Send a salmon to the parent owner
				$r = q("SELECT `url`, `notify` FROM `contact` WHERE `nurl`='%s' AND `uid` IN (0, %d) AND `notify` != ''",
					dbesc(normalise_link($thr_parent[0]['owner-link'])),
					intval($uid));
				if (DBM::is_result($r)) {
					$probed_contact = $r[0];
				} else {
					$probed_contact = Probe::uri($thr_parent[0]['owner-link']);
				}

				if ($probed_contact["notify"] != "") {
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
				$sql_extra = " AND `network` IN ('".NETWORK_OSTATUS."', '".NETWORK_DFRN."')";
			} else {
				$sql_extra = " AND `network` IN ('".NETWORK_OSTATUS."', '".NETWORK_DFRN."', '".NETWORK_DIASPORA."', '".NETWORK_MAIL."')";
			}
		} else {
			$public_message = false;
		}

		// If this is a public message and pubmail is set on the parent, include all your email contacts

		$mail_disabled = ((function_exists('imap_open') && (!Config::get('system','imap_disabled'))) ? 0 : 1);

		if (!$mail_disabled) {
			if (!strlen($target_item['allow_cid']) && !strlen($target_item['allow_gid'])
				&& !strlen($target_item['deny_cid']) && !strlen($target_item['deny_gid'])
				&& intval($target_item['pubmail'])) {
				$r = q("SELECT `id` FROM `contact` WHERE `uid` = %d AND `network` = '%s'",
					intval($uid),
					dbesc(NETWORK_MAIL)
				);
				if (DBM::is_result($r)) {
					foreach ($r as $rr) {
						$recipients[] = $rr['id'];
					}
				}
			}
		}

		if ($followup) {
			$recip_str = implode(', ', $recipients_followup);
		} else {
			$recip_str = implode(', ', $recipients);
		}
		if ($relocate) {
			$r = $recipients_relocate;
		} else {
			$r = q("SELECT `id`, `url`, `network`, `self` FROM `contact`
				WHERE `id` IN (%s) AND NOT `blocked` AND NOT `pending` AND NOT `archive`".$sql_extra,
				dbesc($recip_str)
			);
		}

		// delivery loop

		if (DBM::is_result($r)) {
			foreach ($r as $contact) {
				if ($contact['self']) {
					continue;
				}
				logger("Deliver ".$target_item["guid"]." to ".$contact['url']." via network ".$contact['network'], LOGGER_DEBUG);

				Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
						'Delivery', $cmd, $item_id, (int)$contact['id']);
			}
		}

		// send salmon slaps to mentioned remote tags (@foo@example.com) in OStatus posts
		// They are especially used for notifications to OStatus users that don't follow us.

		if ($slap && count($url_recipients) && ($public_message || $push_notify) && $normal_mode) {
			if (!Config::get('system', 'dfrn_only')) {
				foreach ($url_recipients as $url) {
					if ($url) {
						logger('notifier: urldelivery: ' . $url);
						$deliver_status = Salmon::slapper($owner, $url, $slap);
						/// @TODO Redeliver/queue these items on failure, though there is no contact record
					}
				}
			}
		}


		if ($public_message) {

			$r0 = [];
			$r1 = [];

			if ($diaspora_delivery) {
				if (!$followup) {
					$r0 = Diaspora::relayList();
				}

				$r1 = q("SELECT `batch`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`name`) AS `name`, ANY_VALUE(`network`) AS `network`
					FROM `contact` WHERE `network` = '%s' AND `batch` != ''
					AND `uid` = %d AND `rel` != %d AND NOT `blocked` AND NOT `pending` AND NOT `archive` GROUP BY `batch`",
					dbesc(NETWORK_DIASPORA),
					intval($owner['uid']),
					intval(CONTACT_IS_SHARING)
				);

				// Fetch the participation list
				// The function will ensure that there are no duplicates
				$r1 = Diaspora::participantsForThread($item_id, $r1);

			}

			$r2 = q("SELECT `id`, `name`,`network` FROM `contact`
				WHERE `network` in ('%s') AND `uid` = %d AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `rel` != %d",
				dbesc(NETWORK_DFRN),
				intval($owner['uid']),
				intval(CONTACT_IS_SHARING)
			);


			$r = array_merge($r2, $r1, $r0);

			if (DBM::is_result($r)) {
				logger('pubdeliver '.$target_item["guid"].': '.print_r($r,true), LOGGER_DEBUG);

				foreach ($r as $rr) {

					// except for Diaspora batch jobs
					// Don't deliver to folks who have already been delivered to

					if (($rr['network'] !== NETWORK_DIASPORA) && (in_array($rr['id'],$conversants))) {
						logger('notifier: already delivered id=' . $rr['id']);
						continue;
					}

					if (!$mail && !$fsuggest && !$followup) {
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
			// Set push flag for PuSH subscribers to this topic,
			// they will be notified in queue.php
			q("UPDATE `push_subscriber` SET `push` = 1 ".
			  "WHERE `nickname` = '%s' AND `push` = 0", dbesc($owner['nickname']));

			logger('Activating internal PuSH for item '.$item_id, LOGGER_DEBUG);

			// Handling the pubsubhubbub requests
			Worker::add(['priority' => PRIORITY_HIGH, 'created' => $a->queue['created'], 'dont_fork' => true],
					'PubSubPublish');
		}

		logger('notifier: calling hooks', LOGGER_DEBUG);

		if ($normal_mode) {
			Addon::callHooks('notifier_normal',$target_item);
		}

		Addon::callHooks('notifier_end',$target_item);

		return;
	}
}

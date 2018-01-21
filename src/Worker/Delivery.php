<?php
/**
 * @file src/Worker/Delivery.php
 */
namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Queue;
use Friendica\Model\User;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\Email;
use dba;

require_once 'include/html2plain.php';
require_once 'include/datetime.php';
require_once 'include/items.php';
require_once 'include/bbcode.php';

/// @todo This is some ugly code that needs to be split into several methods

class Delivery {
	public static function execute($cmd, $item_id, $contact_id) {
		global $a;

		logger('delivery: invoked: '.$cmd.': '.$item_id.' to '.$contact_id, LOGGER_DEBUG);

		$mail = false;
		$fsuggest = false;
		$relocate = false;
		$top_level = false;
		$recipients = [];
		$url_recipients = [];
		$followup = false;

		$normal_mode = true;

		$recipients[] = $contact_id;

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
		} elseif ($cmd === 'relocate') {
			$normal_mode = false;
			$relocate = true;
			$uid = $item_id;
		} else {
			// find ancestors
			$target_item = dba::fetch_first("SELECT `item`.*, `contact`.`uid` AS `cuid` FROM `item`
							INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
							WHERE `item`.`id` = ? AND `visible` AND NOT `moderated`", $item_id);

			if (!DBM::isResult($target_item) || !intval($target_item['parent'])) {
				return;
			}

			$parent_id = intval($target_item['parent']);
			$uid = $target_item['cuid'];
			$updated = $target_item['edited'];

			$items = q("SELECT `item`.*, `sign`.`signed_text`,`sign`.`signature`,`sign`.`signer`
				FROM `item` LEFT JOIN `sign` ON `sign`.`iid` = `item`.`id` WHERE `parent` = %d AND visible = 1 AND moderated = 0 ORDER BY `id` ASC",
				intval($parent_id)
			);

			if (!count($items)) {
				return;
			}

			$icontacts = null;
			$contacts_arr = [];
			foreach ($items as $item) {
				if (!in_array($item['contact-id'],$contacts_arr)) {
					$contacts_arr[] = intval($item['contact-id']);
				}
			}
			if (count($contacts_arr)) {
				$str_contacts = implode(',',$contacts_arr);
				$icontacts = q("SELECT * FROM `contact`
					WHERE `id` IN ( $str_contacts ) "
				);
			}
			if ( !($icontacts && count($icontacts))) {
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
			if ((($items[0]['id'] == $item_id) || (count($items) == 1)) && ($items[0]['uri'] === $items[0]['parent-uri'])) {
				logger('delivery: top level post');
				$top_level = true;
			}
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			return;
		}

		$walltowall = (($top_level && ($owner['id'] != $items[0]['contact-id'])) ? true : false);

		$public_message = true;

		if (!$mail && !$fsuggest && !$relocate) {
			$parent = $items[0];

			// This is IMPORTANT!!!!

			// We will only send a "notify owner to relay" or followup message if the referenced post
			// originated on our system by virtue of having our hostname somewhere
			// in the URI, AND it was a comment (not top_level) AND the parent originated elsewhere.
			// if $parent['wall'] == 1 we will already have the parent message in our array
			// and we will relay the whole lot.

			$localhost = $a->get_hostname();
			if (strpos($localhost,':')) {
				$localhost = substr($localhost,0,strpos($localhost,':'));
			}
			/**
			 *
			 * Be VERY CAREFUL if you make any changes to the following line. Seemingly innocuous changes
			 * have been known to cause runaway conditions which affected several servers, along with
			 * permissions issues.
			 *
			 */

			$relay_to_owner = false;

			if (!$top_level && ($parent['wall'] == 0) && stristr($target_item['uri'], $localhost)) {
				$relay_to_owner = true;
			}

			if ($relay_to_owner) {
				logger('followup '.$target_item["guid"], LOGGER_DEBUG);
				// local followup to remote post
				$followup = true;
			}

			if (strlen($parent['allow_cid'])
				|| strlen($parent['allow_gid'])
				|| strlen($parent['deny_cid'])
				|| strlen($parent['deny_gid'])
				|| $parent["private"]) {
				$public_message = false; // private recipients, not public
			}

		}

		$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `blocked` = 0 AND `pending` = 0",
			intval($contact_id)
		);

		if (DBM::isResult($r)) {
			$contact = $r[0];
		}
		if ($contact['self']) {
			return;
		}
		$deliver_status = 0;

		// Transmit via Diaspora if not possible via Friendica
		if (($item['uid'] == 0) && ($contact['network'] == NETWORK_DFRN)) {
			$contact['network'] = NETWORK_DIASPORA;
		}

		logger("main delivery by delivery: followup=$followup mail=$mail fsuggest=$fsuggest relocate=$relocate - network ".$contact['network']);

		switch ($contact['network']) {

			case NETWORK_DFRN:
				logger('notifier: '.$target_item["guid"].' dfrndelivery: '.$contact['name']);

				if ($mail) {
					$item['body'] = fix_private_photos($item['body'],$owner['uid'],null,$message[0]['contact-id']);
					$atom = DFRN::mail($item, $owner);
				} elseif ($fsuggest) {
					$atom = DFRN::fsuggest($item, $owner);
					q("DELETE FROM `fsuggest` WHERE `id` = %d LIMIT 1", intval($item['id']));
				} elseif ($relocate) {
					$atom = DFRN::relocate($owner, $uid);
				} elseif ($followup) {
					$msgitems = [];
					foreach ($items as $item) {  // there is only one item
						if (!$item['parent']) {
							return;
						}
						if ($item['id'] == $item_id) {
							logger('followup: item: '. print_r($item,true), LOGGER_DATA);
							$msgitems[] = $item;
						}
					}
					$atom = DFRN::entries($msgitems,$owner);
				} else {
					$msgitems = [];
					foreach ($items as $item) {
						if (!$item['parent']) {
							return;
						}

						// private emails may be in included in public conversations. Filter them.
						if ($public_message && $item['private']) {
							return;
						}

						$item_contact = get_item_contact($item,$icontacts);
						if (!$item_contact) {
							return;
						}

						if ($normal_mode) {
							if ($item_id == $item['id'] || $item['id'] == $item['parent']) {
								$item["entry:comment-allow"] = true;
								$item["entry:cid"] = (($top_level) ? $contact['id'] : 0);
								$msgitems[] = $item;
							}
						} else {
							$item["entry:comment-allow"] = true;
							$msgitems[] = $item;
						}
					}
					$atom = DFRN::entries($msgitems,$owner);
				}

				logger('notifier entry: '.$contact["url"].' '.$target_item["guid"].' entry: '.$atom, LOGGER_DEBUG);

				logger('notifier: '.$atom, LOGGER_DATA);
				$basepath =  implode('/', array_slice(explode('/',$contact['url']),0,3));

				// perform local delivery if we are on the same site

				if (link_compare($basepath,System::baseUrl())) {
					$nickname = basename($contact['url']);
					if ($contact['issued-id']) {
						$sql_extra = sprintf(" AND `dfrn-id` = '%s' ", dbesc($contact['issued-id']));
					} else {
						$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($contact['dfrn-id']));
					}

					$x = q("SELECT	`contact`.*, `contact`.`uid` AS `importer_uid`,
						`contact`.`pubkey` AS `cpubkey`,
						`contact`.`prvkey` AS `cprvkey`,
						`contact`.`thumb` AS `thumb`,
						`contact`.`url` as `url`,
						`contact`.`name` as `senderName`,
						`user`.*
						FROM `contact`
						INNER JOIN `user` ON `contact`.`uid` = `user`.`uid`
						WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
						AND `contact`.`network` = '%s' AND `user`.`nickname` = '%s'
						$sql_extra
						AND `user`.`account_expired` = 0 AND `user`.`account_removed` = 0 LIMIT 1",
						dbesc(NETWORK_DFRN),
						dbesc($nickname)
					);

					if ($x && count($x)) {
						$write_flag = ((($x[0]['rel']) && ($x[0]['rel'] != CONTACT_IS_SHARING)) ? true : false);
						if ((($owner['page-flags'] == PAGE_COMMUNITY) || $write_flag) && !$x[0]['writable']) {
							q("UPDATE `contact` SET `writable` = 1 WHERE `id` = %d",
								intval($x[0]['id'])
							);
							$x[0]['writable'] = 1;
						}

						$ssl_policy = Config::get('system','ssl_policy');
						fix_contact_ssl_policy($x[0],$ssl_policy);

						// If we are setup as a soapbox we aren't accepting top level posts from this person

						if (($x[0]['page-flags'] == PAGE_SOAPBOX) && $top_level) {
							break;
						}
						logger('mod-delivery: local delivery');
						DFRN::import($atom, $x[0]);
						break;
					}
				}

				if (!Queue::wasDelayed($contact['id'])) {
					$deliver_status = DFRN::deliver($owner, $contact, $atom);
				} else {
					$deliver_status = (-1);
				}

				logger('notifier: dfrn_delivery to '.$contact["url"].' with guid '.$target_item["guid"].' returns '.$deliver_status);

				if ($deliver_status < 0) {
					logger('notifier: delivery failed: queuing message');
					Queue::add($contact['id'], NETWORK_DFRN, $atom);

					// The message could not be delivered. We mark the contact as "dead"
					Contact::markForArchival($contact);
				} else {
					// We successfully delivered a message, the contact is alive
					Contact::unmarkForArchival($contact);
				}

				break;

			case NETWORK_OSTATUS:
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

			case NETWORK_MAIL:

				if (Config::get('system','dfrn_only')) {
					break;
				}
				// WARNING: does not currently convert to RFC2047 header encodings, etc.

				$addr = $contact['addr'];
				if (!strlen($addr)) {
					break;
				}

				if ($cmd === 'wall-new' || $cmd === 'comment-new') {
					$it = null;
					if ($cmd === 'wall-new') {
						$it = $items[0];
					} else {
						$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
							intval($item_id)
						);
						if (DBM::isResult($r)) {
							$it = $r[0];
						}
					}
					if (!$it) {
						break;
					}

					$local_user = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
						intval($uid)
					);
					if (!count($local_user)) {
						break;
					}

					$reply_to = '';
					$r1 = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
						intval($uid)
					);
					if ($r1 && $r1[0]['reply_to']) {
						$reply_to = $r1[0]['reply_to'];
					}

					$subject  = (($it['title']) ? Email::encodeHeader($it['title'],'UTF-8') : t("\x28no subject\x29")) ;

					// only expose our real email address to true friends

					if (($contact['rel'] == CONTACT_IS_FRIEND) && !$contact['blocked']) {
						if ($reply_to) {
							$headers  = 'From: '.Email::encodeHeader($local_user[0]['username'],'UTF-8').' <'.$reply_to.'>'."\n";
							$headers .= 'Sender: '.$local_user[0]['email']."\n";
						} else {
							$headers  = 'From: '.Email::encodeHeader($local_user[0]['username'],'UTF-8').' <'.$local_user[0]['email'].'>'."\n";
						}
					} else {
						$headers  = 'From: '. Email::encodeHeader($local_user[0]['username'],'UTF-8') .' <'. t('noreply') .'@'.$a->get_hostname() .'>'. "\n";
					}

					//if ($reply_to)
					//	$headers .= 'Reply-to: '.$reply_to . "\n";

					$headers .= 'Message-Id: <'. Email::iri2msgid($it['uri']).'>'. "\n";

					//logger("Mail: uri: ".$it['uri']." parent-uri ".$it['parent-uri'], LOGGER_DEBUG);
					//logger("Mail: Data: ".print_r($it, true), LOGGER_DEBUG);
					//logger("Mail: Data: ".print_r($it, true), LOGGER_DATA);

					if ($it['uri'] !== $it['parent-uri']) {
						$headers .= "References: <".Email::iri2msgid($it["parent-uri"]).">";

						// If Threading is enabled, write down the correct parent
						if (($it["thr-parent"] != "") && ($it["thr-parent"] != $it["parent-uri"])) {
							$headers .= " <".Email::iri2msgid($it["thr-parent"]).">";
						}
						$headers .= "\n";

						if (!$it['title']) {
							$r = q("SELECT `title` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
								dbesc($it['parent-uri']),
								intval($uid));

							if (DBM::isResult($r) && ($r[0]['title'] != '')) {
								$subject = $r[0]['title'];
							} else {
								$r = q("SELECT `title` FROM `item` WHERE `parent-uri` = '%s' AND `uid` = %d LIMIT 1",
									dbesc($it['parent-uri']),
									intval($uid));

								if (DBM::isResult($r) && ($r[0]['title'] != '')) {
									$subject = $r[0]['title'];
								}
							}
						}
						if (strncasecmp($subject,'RE:',3)) {
							$subject = 'Re: '.$subject;
						}
					}
					Email::send($addr, $subject, $headers, $it);
				}
				break;

			case NETWORK_DIASPORA:
				if ($public_message) {
					$loc = 'public batch '.$contact['batch'];
				} else {
					$loc = $contact['name'];
				}

				logger('delivery: diaspora batch deliver: '.$loc);

				if (Config::get('system','dfrn_only') || !Config::get('system','diaspora_enabled')) {
					break;
				}
				if ($mail) {
					Diaspora::sendMail($item,$owner,$contact);
					break;
				}

				if (!$normal_mode) {
					break;
				}
				if (!$contact['pubkey'] && !$public_message) {
					break;
				}
				if (($target_item['deleted']) && (($target_item['uri'] === $target_item['parent-uri']) || $followup)) {
					// top-level retraction
					logger('diaspora retract: '.$loc);
					Diaspora::sendRetraction($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($relocate) {
					Diaspora::sendAccountMigration($owner, $contact, $uid);
					break;
				} elseif ($followup) {
					// send comments and likes to owner to relay
					logger('diaspora followup: '.$loc);
					Diaspora::sendFollowup($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($target_item['uri'] !== $target_item['parent-uri']) {
					// we are the relay - send comments, likes and relayable_retractions to our conversants
					logger('diaspora relay: '.$loc);
					Diaspora::sendRelay($target_item,$owner,$contact,$public_message);
					break;
				} elseif ($top_level && !$walltowall) {
					// currently no workable solution for sending walltowall
					logger('diaspora status: '.$loc);
					Diaspora::sendStatus($target_item,$owner,$contact,$public_message);
					break;
				}

				logger('delivery: diaspora unknown mode: '.$contact['name']);

				break;

			default:
				break;
		}

		return;
	}
}

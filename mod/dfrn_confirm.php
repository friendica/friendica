<?php
/**
 * @file mod/dfrn_confirm.php
 * @brief Module: dfrn_confirm
 * Purpose: Friendship acceptance for DFRN contacts
 *
 * There are two possible entry points and three scenarios.
 *
 *   1. A form was submitted by our user approving a friendship that originated elsewhere.
 *      This may also be called from dfrn_request to automatically approve a friendship.
 *
 *   2. We may be the target or other side of the conversation to scenario 1, and will
 *      interact with that process on our own user's behalf.
 *
 *  @see PDF with dfrn specs: https://github.com/friendica/friendica/blob/master/spec/dfrn2.pdf
 *    You also find a graphic which describes the confirmation process at
 *    https://github.com/friendica/friendica/blob/master/spec/dfrn2_contact_confirmation.png
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\XML;

require_once 'include/enotify.php';
require_once 'include/items.php';

function dfrn_confirm_post(App $a, $handsfree = null)
{
	$node = null;
	if (is_array($handsfree)) {
		/*
		 * We were called directly from dfrn_request due to automatic friend acceptance.
		 * Any $_POST parameters we may require are supplied in the $handsfree array.
		 *
		 */
		$node = $handsfree['node'];
		$a->interactive = false; // notice() becomes a no-op since nobody is there to see it
	} elseif ($a->argc > 1) {
		$node = $a->argv[1];
	}

	/*
	 * Main entry point. Scenario 1. Our user received a friend request notification (perhaps
	 * from another site) and clicked 'Approve'.
	 * $POST['source_url'] is not set. If it is, it indicates Scenario 2.
	 *
	 * We may also have been called directly from dfrn_request ($handsfree != null) due to
	 * this being a page type which supports automatic friend acceptance. That is also Scenario 1
	 * since we are operating on behalf of our registered user to approve a friendship.
	 */
	if (!x($_POST, 'source_url')) {
		$uid = defaults($handsfree, 'uid', local_user());
		if (!$uid) {
			notice(L10n::t('Permission denied.') . EOL);
			return;
		}

		$user = dba::selectFirst('user', [], ['uid' => $uid]);
		if (!DBM::is_result($user)) {
			notice(L10n::t('Profile not found.') . EOL);
			return;
		}

		// These data elements may come from either the friend request notification form or $handsfree array.
		if (is_array($handsfree)) {
			logger('Confirm in handsfree mode');
			$dfrn_id  = $handsfree['dfrn_id'];
			$intro_id = $handsfree['intro_id'];
			$duplex   = $handsfree['duplex'];
			$cid      = 0;
			$hidden   = intval(defaults($handsfree, 'hidden'  , 0));
			$activity = intval(defaults($handsfree, 'activity', 0));
		} else {
			$dfrn_id  = notags(trim(defaults($_POST, 'dfrn_id'   , '')));
			$intro_id =      intval(defaults($_POST, 'intro_id'  , 0));
			$duplex   =      intval(defaults($_POST, 'duplex'    , 0));
			$cid      =      intval(defaults($_POST, 'contact_id', 0));
			$hidden   =      intval(defaults($_POST, 'hidden'    , 0));
			$activity =      intval(defaults($_POST, 'activity'  , 0));
		}

		/*
		 * Ensure that dfrn_id has precedence when we go to find the contact record.
		 * We only want to search based on contact id if there is no dfrn_id,
		 * e.g. for OStatus network followers.
		 */
		if (strlen($dfrn_id)) {
			$cid = 0;
		}

		logger('Confirming request for dfrn_id (issued) ' . $dfrn_id);
		if ($cid) {
			logger('Confirming follower with contact_id: ' . $cid);
		}

		/*
		 * The other person will have been issued an ID when they first requested friendship.
		 * Locate their record. At this time, their record will have both pending and blocked set to 1.
		 * There won't be any dfrn_id if this is a network follower, so use the contact_id instead.
		 */
		$r = q("SELECT *
			FROM `contact`
			WHERE (
				(`issued-id` != '' AND `issued-id` = '%s')
				OR
				(`id` = %d AND `id` != 0)
			)
			AND `uid` = %d
			AND `duplex` = 0
			LIMIT 1",
			dbesc($dfrn_id),
			intval($cid),
			intval($uid)
		);
		if (!DBM::is_result($r)) {
			logger('Contact not found in DB.');
			notice(L10n::t('Contact not found.') . EOL);
			notice(L10n::t('This may occasionally happen if contact was requested by both persons and it has already been approved.') . EOL);
			return;
		}

		$contact = $r[0];

		$contact_id   = $contact['id'];
		$relation     = $contact['rel'];
		$site_pubkey  = $contact['site-pubkey'];
		$dfrn_confirm = $contact['confirm'];
		$aes_allow    = $contact['aes_allow'];

		$network = ((strlen($contact['issued-id'])) ? NETWORK_DFRN : NETWORK_OSTATUS);

		if ($contact['network']) {
			$network = $contact['network'];
		}

		if ($network === NETWORK_DFRN) {
			/*
			 * Generate a key pair for all further communications with this person.
			 * We have a keypair for every contact, and a site key for unknown people.
			 * This provides a means to carry on relationships with other people if
			 * any single key is compromised. It is a robust key. We're much more
			 * worried about key leakage than anybody cracking it.
			 */
			$res = Crypto::newKeypair(4096);

			$private_key = $res['prvkey'];
			$public_key  = $res['pubkey'];

			// Save the private key. Send them the public key.
			q("UPDATE `contact` SET `prvkey` = '%s' WHERE `id` = %d AND `uid` = %d",
				dbesc($private_key),
				intval($contact_id),
				intval($uid)
			);

			$params = [];

			/*
			 * Per the DFRN protocol, we will verify both ends by encrypting the dfrn_id with our
			 * site private key (person on the other end can decrypt it with our site public key).
			 * Then encrypt our profile URL with the other person's site public key. They can decrypt
			 * it with their site private key. If the decryption on the other end fails for either
			 * item, it indicates tampering or key failure on at least one site and we will not be
			 * able to provide a secure communication pathway.
			 *
			 * If other site is willing to accept full encryption, (aes_allow is 1 AND we have php5.3
			 * or later) then we encrypt the personal public key we send them using AES-256-CBC and a
			 * random key which is encrypted with their site public key.
			 */

			$src_aes_key = openssl_random_pseudo_bytes(64);

			$result = '';
			openssl_private_encrypt($dfrn_id, $result, $user['prvkey']);

			$params['dfrn_id'] = bin2hex($result);
			$params['public_key'] = $public_key;

			$my_url = System::baseUrl() . '/profile/' . $user['nickname'];

			openssl_public_encrypt($my_url, $params['source_url'], $site_pubkey, OPENSSL_PKCS1_OAEP_PADDING);
			$params['source_url'] = bin2hex($params['source_url']);

			if ($aes_allow && function_exists('openssl_encrypt')) {
				openssl_public_encrypt($src_aes_key, $params['aes_key'], $site_pubkey, OPENSSL_PKCS1_OAEP_PADDING);
				$params['aes_key'] = bin2hex($params['aes_key']);
				$params['public_key'] = bin2hex(openssl_encrypt($public_key, 'AES-256-CBC', $src_aes_key));
			}

			$params['dfrn_version'] = DFRN_PROTOCOL_VERSION;
			if ($duplex == 1) {
				$params['duplex'] = 1;
			}

			if ($user['page-flags'] == PAGE_COMMUNITY) {
				$params['page'] = 1;
			}

			if ($user['page-flags'] == PAGE_PRVGROUP) {
				$params['page'] = 2;
			}

			logger('Confirm: posting data to ' . $dfrn_confirm . ': ' . print_r($params, true), LOGGER_DATA);

			/*
			 *
			 * POST all this stuff to the other site.
			 * Temporarily raise the network timeout to 120 seconds because the default 60
			 * doesn't always give the other side quite enough time to decrypt everything.
			 *
			 */

			$res = Network::post($dfrn_confirm, $params, null, $redirects, 120);

			logger(' Confirm: received data: ' . $res, LOGGER_DATA);

			// Now figure out what they responded. Try to be robust if the remote site is
			// having difficulty and throwing up errors of some kind.

			$leading_junk = substr($res, 0, strpos($res, '<?xml'));

			$res = substr($res, strpos($res, '<?xml'));
			if (!strlen($res)) {
				// No XML at all, this exchange is messed up really bad.
				// We shouldn't proceed, because the xml parser might choke,
				// and $status is going to be zero, which indicates success.
				// We can hardly call this a success.
				notice(L10n::t('Response from remote site was not understood.') . EOL);
				return;
			}

			if (strlen($leading_junk) && Config::get('system', 'debugging')) {
				// This might be more common. Mixed error text and some XML.
				// If we're configured for debugging, show the text. Proceed in either case.
				notice(L10n::t('Unexpected response from remote site: ') . EOL . $leading_junk . EOL);
			}

			if (stristr($res, "<status") === false) {
				// wrong xml! stop here!
				notice(L10n::t('Unexpected response from remote site: ') . EOL . htmlspecialchars($res) . EOL);
				return;
			}

			$xml = XML::parseString($res);
			$status = (int) $xml->status;
			$message = unxmlify($xml->message);   // human readable text of what may have gone wrong.
			switch ($status) {
				case 0:
					info(L10n::t("Confirmation completed successfully.") . EOL);
					break;
				case 1:
					// birthday paradox - generate new dfrn-id and fall through.
					$new_dfrn_id = random_string();
					q("UPDATE contact SET `issued-id` = '%s' WHERE `id` = %d AND `uid` = %d",
						dbesc($new_dfrn_id),
						intval($contact_id),
						intval($uid)
					);

				case 2:
					notice(L10n::t("Temporary failure. Please wait and try again.") . EOL);
					break;
				case 3:
					notice(L10n::t("Introduction failed or was revoked.") . EOL);
					break;
			}

			if (strlen($message)) {
				notice(L10n::t('Remote site reported: ') . $message . EOL);
			}

			if (($status == 0) && ($intro_id)) {
				// Success. Delete the notification.
				q("DELETE FROM `intro` WHERE `id` = %d AND `uid` = %d",
					intval($intro_id),
					intval($uid)
				);
			}

			if ($status != 0) {
				return;
			}
		}

		/*
		 * We have now established a relationship with the other site.
		 * Let's make our own personal copy of their profile photo so we don't have
		 * to always load it from their site.
		 *
		 * We will also update the contact record with the nature and scope of the relationship.
		 */
		Contact::updateAvatar($contact['photo'], $uid, $contact_id);

		logger('dfrn_confirm: confirm - imported photos');

		if ($network === NETWORK_DFRN) {
			$new_relation = CONTACT_IS_FOLLOWER;
			if (($relation == CONTACT_IS_SHARING) || ($duplex)) {
				$new_relation = CONTACT_IS_FRIEND;
			}

			if (($relation == CONTACT_IS_SHARING) && ($duplex)) {
				$duplex = 0;
			}

			$r = q("UPDATE `contact` SET `rel` = %d,
				`name-date` = '%s',
				`uri-date` = '%s',
				`blocked` = 0,
				`pending` = 0,
				`duplex` = %d,
				`hidden` = %d,
				`network` = '%s' WHERE `id` = %d
			",
				intval($new_relation),
				dbesc(DateTimeFormat::utcNow()),
				dbesc(DateTimeFormat::utcNow()),
				intval($duplex),
				intval($hidden),
				dbesc(NETWORK_DFRN),
				intval($contact_id)
			);
		} else {
			// $network !== NETWORK_DFRN
			$network = defaults($contact, 'network', NETWORK_OSTATUS);

			$arr = Probe::uri($contact['url']);

			$notify  = defaults($contact, 'notify' , $arr['notify']);
			$poll    = defaults($contact, 'poll'   , $arr['poll']);

			$addr = $arr['addr'];

			$new_relation = $contact['rel'];
			$writable = $contact['writable'];

			if ($network === NETWORK_DIASPORA) {
				if ($duplex) {
					$new_relation = CONTACT_IS_FRIEND;
				} else {
					$new_relation = CONTACT_IS_FOLLOWER;
				}

				if ($new_relation != CONTACT_IS_FOLLOWER) {
					$writable = 1;
				}
			}

			q("DELETE FROM `intro` WHERE `id` = %d AND `uid` = %d",
				intval($intro_id),
				intval($uid)
			);

			$r = q("UPDATE `contact` SET `name-date` = '%s',
				`uri-date` = '%s',
				`addr` = '%s',
				`notify` = '%s',
				`poll` = '%s',
				`blocked` = 0,
				`pending` = 0,
				`network` = '%s',
				`writable` = %d,
				`hidden` = %d,
				`rel` = %d
				WHERE `id` = %d
			",
				dbesc(DateTimeFormat::utcNow()),
				dbesc(DateTimeFormat::utcNow()),
				dbesc($addr),
				dbesc($notify),
				dbesc($poll),
				dbesc($network),
				intval($writable),
				intval($hidden),
				intval($new_relation),
				intval($contact_id)
			);
		}

		/// @TODO is DBM::is_result() working here?
		if (!DBM::is_result($r)) {
			notice(L10n::t('Unable to set contact photo.') . EOL);
		}

		// reload contact info
		$contact = dba::selectFirst('contact', [], ['id' => $contact_id]);
		if ((isset($new_relation) && $new_relation == CONTACT_IS_FRIEND)) {
			if (DBM::is_result($contact) && ($contact['network'] === NETWORK_DIASPORA)) {
				$ret = Diaspora::sendShare($user, $contact);
				logger('share returns: ' . $ret);
			}

			// Send a new friend post if we are allowed to...
			$profile = dba::selectFirst('profile', ['hide-friends'], ['is-default' => true, 'uid' => $uid]);
			if (x($profile, 'hide-friends') === 0 && $activity && !$hidden) {
				$self = dba::selectFirst('contact', [], ['self' => true, 'uid' => $uid]);
				if (DBM::is_result($self)) {
					$arr = [];
					$arr['guid'] = get_guid(32);
					$arr['uri'] = $arr['parent-uri'] = item_new_uri($a->get_hostname(), $uid);
					$arr['uid'] = $uid;
					$arr['contact-id'] = $self['id'];
					$arr['wall'] = 1;
					$arr['type'] = 'wall';
					$arr['gravity'] = 0;
					$arr['origin'] = 1;
					$arr['author-name']   = $arr['owner-name']   = $self['name'];
					$arr['author-link']   = $arr['owner-link']   = $self['url'];
					$arr['author-avatar'] = $arr['owner-avatar'] = $self['thumb'];

					$A = '[url=' . $self['url'] . ']' . $self['name'] . '[/url]';
					$B = '[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';
					$BPhoto = '[url=' . $contact['url'] . ']' . '[img]' . $contact['thumb'] . '[/img][/url]';

					$arr['verb'] = ACTIVITY_FRIEND;
					$arr['object-type'] = ACTIVITY_OBJ_PERSON;
					$arr['body'] = L10n::t('%1$s is now friends with %2$s', $A, $B) . "\n\n\n" . $BPhoto;

					$arr['object'] = '<object><type>' . ACTIVITY_OBJ_PERSON . '</type><title>' . $contact['name'] . '</title>'
						. '<id>' . $contact['url'] . '/' . $contact['name'] . '</id>';
					$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $contact['url'] . '" />' . "\n");
					$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $contact['thumb'] . '" />' . "\n");
					$arr['object'] .= '</link></object>' . "\n";

					$arr['allow_cid'] = $user['allow_cid'];
					$arr['allow_gid'] = $user['allow_gid'];
					$arr['deny_cid']  = $user['deny_cid'];
					$arr['deny_gid']  = $user['deny_gid'];

					$i = Item::insert($arr);
					if ($i) {
						Worker::add(PRIORITY_HIGH, "Notifier", "activity", $i);
					}
				}
			}
		}

		Group::addMember(User::getDefaultGroup($uid, $contact["network"]), $contact['id']);

		// Let's send our user to the contact editor in case they want to
		// do anything special with this new friend.
		if ($handsfree === null) {
			goaway(System::baseUrl() . '/contacts/' . intval($contact_id));
		} else {
			return;
		}
		//NOTREACHED
	}

	/*
	 * End of Scenario 1. [Local confirmation of remote friend request].
	 *
	 * Begin Scenario 2. This is the remote response to the above scenario.
	 * This will take place on the site that originally initiated the friend request.
	 * In the section above where the confirming party makes a POST and
	 * retrieves xml status information, they are communicating with the following code.
	 */
	if (x($_POST, 'source_url')) {
		// We are processing an external confirmation to an introduction created by our user.
		$public_key =         defaults($_POST, 'public_key', '');
		$dfrn_id    = hex2bin(defaults($_POST, 'dfrn_id'   , ''));
		$source_url = hex2bin(defaults($_POST, 'source_url', ''));
		$aes_key    =         defaults($_POST, 'aes_key'   , '');
		$duplex     =  intval(defaults($_POST, 'duplex'    , 0));
		$page       =  intval(defaults($_POST, 'page'      , 0));

		$forum = (($page == 1) ? 1 : 0);
		$prv   = (($page == 2) ? 1 : 0);

		logger('dfrn_confirm: requestee contacted: ' . $node);

		logger('dfrn_confirm: request: POST=' . print_r($_POST, true), LOGGER_DATA);

		// If $aes_key is set, both of these items require unpacking from the hex transport encoding.

		if (x($aes_key)) {
			$aes_key = hex2bin($aes_key);
			$public_key = hex2bin($public_key);
		}

		// Find our user's account
		$user = dba::selectFirst('user', [], ['nickname' => $node]);
		if (!DBM::is_result($user)) {
			$message = L10n::t('No user record found for \'%s\' ', $node);
			System::xmlExit(3, $message); // failure
			// NOTREACHED
		}

		$my_prvkey = $user['prvkey'];
		$local_uid = $user['uid'];


		if (!strstr($my_prvkey, 'PRIVATE KEY')) {
			$message = L10n::t('Our site encryption key is apparently messed up.');
			System::xmlExit(3, $message);
		}

		// verify everything

		$decrypted_source_url = "";
		Crypto::opensslPrivateDecrypt($source_url, $decrypted_source_url, $my_prvkey);


		if (!strlen($decrypted_source_url)) {
			$message = L10n::t('Empty site URL was provided or URL could not be decrypted by us.');
			System::xmlExit(3, $message);
			// NOTREACHED
		}

		$contact = dba::selectFirst('contact', [], ['url' => $decrypted_source_url, 'uid' => $local_uid]);
		if (!DBM::is_result($contact)) {
			if (strstr($decrypted_source_url, 'http:')) {
				$newurl = str_replace('http:', 'https:', $decrypted_source_url);
			} else {
				$newurl = str_replace('https:', 'http:', $decrypted_source_url);
			}

			$contact = dba::selectFirst('contact', [], ['url' => $newurl, 'uid' => $local_uid]);
			if (!DBM::is_result($contact)) {
				// this is either a bogus confirmation (?) or we deleted the original introduction.
				$message = L10n::t('Contact record was not found for you on our site.');
				System::xmlExit(3, $message);
				return; // NOTREACHED
			}
		}

		$relation = $contact['rel'];

		// Decrypt all this stuff we just received

		$foreign_pubkey = $contact['site-pubkey'];
		$dfrn_record = $contact['id'];

		if (!$foreign_pubkey) {
			$message = L10n::t('Site public key not available in contact record for URL %s.', $decrypted_source_url);
			System::xmlExit(3, $message);
		}

		$decrypted_dfrn_id = "";
		openssl_public_decrypt($dfrn_id, $decrypted_dfrn_id, $foreign_pubkey);

		if (strlen($aes_key)) {
			$decrypted_aes_key = "";
			Crypto::opensslPrivateDecrypt($aes_key, $decrypted_aes_key, $my_prvkey);
			$dfrn_pubkey = openssl_decrypt($public_key, 'AES-256-CBC', $decrypted_aes_key);
		} else {
			$dfrn_pubkey = $public_key;
		}

		if (dba::exists('contact', ['dfrn-id' => $decrypted_dfrn_id])) {
			$message = L10n::t('The ID provided by your system is a duplicate on our system. It should work if you try again.');
			System::xmlExit(1, $message); // Birthday paradox - duplicate dfrn-id
			// NOTREACHED
		}

		$r = q("UPDATE `contact` SET `dfrn-id` = '%s', `pubkey` = '%s' WHERE `id` = %d",
			dbesc($decrypted_dfrn_id),
			dbesc($dfrn_pubkey),
			intval($dfrn_record)
		);
		if (!DBM::is_result($r)) {
			$message = L10n::t('Unable to set your contact credentials on our system.');
			System::xmlExit(3, $message);
		}

		// It's possible that the other person also requested friendship.
		// If it is a duplex relationship, ditch the issued-id if one exists.

		if ($duplex) {
			q("UPDATE `contact` SET `issued-id` = '' WHERE `id` = %d",
				intval($dfrn_record)
			);
		}

		// We're good but now we have to scrape the profile photo and send notifications.
		$contact = dba::selectFirst('contact', ['photo'], ['id' => $dfrn_record]);
		if (DBM::is_result($contact)) {
			$photo = $contact['photo'];
		} else {
			$photo = System::baseUrl() . '/images/person-175.jpg';
		}

		Contact::updateAvatar($photo, $local_uid, $dfrn_record);

		logger('dfrn_confirm: request - photos imported');

		$new_relation = CONTACT_IS_SHARING;
		if (($relation == CONTACT_IS_FOLLOWER) || ($duplex)) {
			$new_relation = CONTACT_IS_FRIEND;
		}

		if (($relation == CONTACT_IS_FOLLOWER) && ($duplex)) {
			$duplex = 0;
		}

		$r = q("UPDATE `contact` SET
			`rel` = %d,
			`name-date` = '%s',
			`uri-date` = '%s',
			`blocked` = 0,
			`pending` = 0,
			`duplex` = %d,
			`forum` = %d,
			`prv` = %d,
			`network` = '%s' WHERE `id` = %d
		",
			intval($new_relation),
			dbesc(DateTimeFormat::utcNow()),
			dbesc(DateTimeFormat::utcNow()),
			intval($duplex),
			intval($forum),
			intval($prv),
			dbesc(NETWORK_DFRN),
			intval($dfrn_record)
		);
		if (!DBM::is_result($r)) {	// indicates schema is messed up or total db failure
			$message = L10n::t('Unable to update your contact profile details on our system');
			System::xmlExit(3, $message);
		}

		// Otherwise everything seems to have worked and we are almost done. Yay!
		// Send an email notification

		logger('dfrn_confirm: request: info updated');

		$combined = null;
		$r = q("SELECT `contact`.*, `user`.*
			FROM `contact`
			LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`id` = %d
			LIMIT 1",
			intval($dfrn_record)
		);
		if (DBM::is_result($r)) {
			$combined = $r[0];

			if ($combined['notify-flags'] & NOTIFY_CONFIRM) {
				$mutual = ($new_relation == CONTACT_IS_FRIEND);
				notification([
					'type'         => NOTIFY_CONFIRM,
					'notify_flags' => $combined['notify-flags'],
					'language'     => $combined['language'],
					'to_name'      => $combined['username'],
					'to_email'     => $combined['email'],
					'uid'          => $combined['uid'],
					'link'         => System::baseUrl() . '/contacts/' . $dfrn_record,
					'source_name'  => ((strlen(stripslashes($combined['name']))) ? stripslashes($combined['name']) : L10n::t('[Name Withheld]')),
					'source_link'  => $combined['url'],
					'source_photo' => $combined['photo'],
					'verb'         => ($mutual?ACTIVITY_FRIEND:ACTIVITY_FOLLOW),
					'otype'        => 'intro'
				]);
			}
		}

		// Send a new friend post if we are allowed to...
		if ($page && intval(PConfig::get($local_uid, 'system', 'post_joingroup'))) {
			$profile = dba::selectFirst('profile', ['hide-friends'], ['is-default' => true, 'uid' => $local_uid]);
			if (x($profile, 'hide-friends') === 0) {
				$self = dba::selectFirst('contact', [], ['self' => true, 'uid' => $local_uid]);
				if (DBM::is_result($self)) {
					$arr = [];
					$arr['uri'] = $arr['parent-uri'] = item_new_uri($a->get_hostname(), $local_uid);
					$arr['uid'] = $local_uid;
					$arr['contact-id'] = $self['id'];
					$arr['wall'] = 1;
					$arr['type'] = 'wall';
					$arr['gravity'] = 0;
					$arr['origin'] = 1;
					$arr['author-name']   = $arr['owner-name']   = $self['name'];
					$arr['author-link']   = $arr['owner-link']   = $self['url'];
					$arr['author-avatar'] = $arr['owner-avatar'] = $self['thumb'];

					$A = '[url=' . $self['url'] . ']' . $self['name'] . '[/url]';
					$B = '[url=' . $combined['url'] . ']' . $combined['name'] . '[/url]';
					$BPhoto = '[url=' . $combined['url'] . ']' . '[img]' . $combined['thumb'] . '[/img][/url]';

					$arr['verb'] = ACTIVITY_JOIN;
					$arr['object-type'] = ACTIVITY_OBJ_GROUP;
					$arr['body'] = L10n::t('%1$s has joined %2$s', $A, $B) . "\n\n\n" . $BPhoto;
					$arr['object'] = '<object><type>' . ACTIVITY_OBJ_GROUP . '</type><title>' . $combined['name'] . '</title>'
						. '<id>' . $combined['url'] . '/' . $combined['name'] . '</id>';
					$arr['object'] .= '<link>' . xmlify('<link rel="alternate" type="text/html" href="' . $combined['url'] . '" />' . "\n");
					$arr['object'] .= xmlify('<link rel="photo" type="image/jpeg" href="' . $combined['thumb'] . '" />' . "\n");
					$arr['object'] .= '</link></object>' . "\n";

					$arr['allow_cid'] = $user['allow_cid'];
					$arr['allow_gid'] = $user['allow_gid'];
					$arr['deny_cid']  = $user['deny_cid'];
					$arr['deny_gid']  = $user['deny_gid'];

					$i = Item::insert($arr);
					if ($i) {
						Worker::add(PRIORITY_HIGH, "Notifier", "activity", $i);
					}
				}
			}
		}
		System::xmlExit(0); // Success
		return; // NOTREACHED
		////////////////////// End of this scenario ///////////////////////////////////////////////
	}

	// somebody arrived here by mistake or they are fishing. Send them to the homepage.
	goaway(System::baseUrl());
	// NOTREACHED
}

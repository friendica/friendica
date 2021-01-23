<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
 * Friendship acceptance for DFRN contacts
 *
 * There are two possible entry points and three scenarios.
 *
 *   1. A form was submitted by our user approving a friendship that originated elsewhere.
 *      This may also be called from dfrn_request to automatically approve a friendship.
 *
 *   2. We may be the target or other side of the conversation to scenario 1, and will
 *      interact with that process on our own user's behalf.
 *
 *  @see PDF with dfrn specs: https://github.com/friendica/friendica/blob/stable/spec/dfrn2.pdf
 *    You also find a graphic which describes the confirmation process at
 *    https://github.com/friendica/friendica/blob/stable/spec/dfrn2_contact_confirmation.png
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Notification;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;
use Friendica\Util\XML;

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
	if (empty($_POST['source_url'])) {
		$uid = ($handsfree['uid'] ?? 0) ?: local_user();
		if (!$uid) {
			notice(DI::l10n()->t('Permission denied.'));
			return;
		}

		$user = DBA::selectFirst('user', [], ['uid' => $uid]);
		if (!DBA::isResult($user)) {
			notice(DI::l10n()->t('Profile not found.'));
			return;
		}

		// These data elements may come from either the friend request notification form or $handsfree array.
		if (is_array($handsfree)) {
			Logger::log('Confirm in handsfree mode');
			$dfrn_id  = $handsfree['dfrn_id'];
			$intro_id = $handsfree['intro_id'];
			$duplex   = $handsfree['duplex'];
			$cid      = 0;
			$hidden   = intval($handsfree['hidden'] ?? 0);
		} else {
			$dfrn_id  = Strings::escapeTags(trim($_POST['dfrn_id'] ?? ''));
			$intro_id = intval($_POST['intro_id']   ?? 0);
			$duplex   = intval($_POST['duplex']     ?? 0);
			$cid      = intval($_POST['contact_id'] ?? 0);
			$hidden   = intval($_POST['hidden']     ?? 0);
		}

		/*
		 * Ensure that dfrn_id has precedence when we go to find the contact record.
		 * We only want to search based on contact id if there is no dfrn_id,
		 * e.g. for OStatus network followers.
		 */
		if (strlen($dfrn_id)) {
			$cid = 0;
		}

		Logger::log('Confirming request for dfrn_id (issued) ' . $dfrn_id);
		if ($cid) {
			Logger::log('Confirming follower with contact_id: ' . $cid);
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
			DBA::escape($dfrn_id),
			intval($cid),
			intval($uid)
		);
		if (!DBA::isResult($r)) {
			Logger::log('Contact not found in DB.');
			notice(DI::l10n()->t('Contact not found.'));
			notice(DI::l10n()->t('This may occasionally happen if contact was requested by both persons and it has already been approved.'));
			return;
		}

		$contact = $r[0];

		$contact_id   = $contact['id'];
		$relation     = $contact['rel'];
		$site_pubkey  = $contact['site-pubkey'];
		$dfrn_confirm = $contact['confirm'];
		$aes_allow    = $contact['aes_allow'];
		$protocol     = $contact['network'];

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
		$fields = ['prvkey' => $private_key, 'protocol' => Protocol::DFRN];
		DBA::update('contact', $fields, ['id' => $contact_id]);

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

		$my_url = DI::baseUrl() . '/profile/' . $user['nickname'];

		openssl_public_encrypt($my_url, $params['source_url'], $site_pubkey);
		$params['source_url'] = bin2hex($params['source_url']);

		if ($aes_allow && function_exists('openssl_encrypt')) {
			openssl_public_encrypt($src_aes_key, $params['aes_key'], $site_pubkey);
			$params['aes_key'] = bin2hex($params['aes_key']);
			$params['public_key'] = bin2hex(openssl_encrypt($public_key, 'AES-256-CBC', $src_aes_key));
		}

		$params['dfrn_version'] = DFRN_PROTOCOL_VERSION;
		if ($duplex == 1) {
			$params['duplex'] = 1;
		}

		if ($user['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
			$params['page'] = 1;
		}

		if ($user['page-flags'] == User::PAGE_FLAGS_PRVGROUP) {
			$params['page'] = 2;
		}

		Logger::debug('Confirm: posting data', ['confirm'  => $dfrn_confirm, 'parameter' => $params]);

		/*
		 *
		 * POST all this stuff to the other site.
		 * Temporarily raise the network timeout to 120 seconds because the default 60
		 * doesn't always give the other side quite enough time to decrypt everything.
		 *
		 */

		$res = DI::httpRequest()->post($dfrn_confirm, $params, [], 120)->getBody();

		Logger::log(' Confirm: received data: ' . $res, Logger::DATA);

		// Now figure out what they responded. Try to be robust if the remote site is
		// having difficulty and throwing up errors of some kind.

		$leading_junk = substr($res, 0, strpos($res, '<?xml'));

		$res = substr($res, strpos($res, '<?xml'));
		if (!strlen($res)) {
			// No XML at all, this exchange is messed up really bad.
			// We shouldn't proceed, because the xml parser might choke,
			// and $status is going to be zero, which indicates success.
			// We can hardly call this a success.
			notice(DI::l10n()->t('Response from remote site was not understood.'));
			return;
		}

		if (strlen($leading_junk) && DI::config()->get('system', 'debugging')) {
			// This might be more common. Mixed error text and some XML.
			// If we're configured for debugging, show the text. Proceed in either case.
			notice(DI::l10n()->t('Unexpected response from remote site: ') . $leading_junk);
		}

		if (stristr($res, "<status") === false) {
			// wrong xml! stop here!
			Logger::log('Unexpected response posting to ' . $dfrn_confirm);
			notice(DI::l10n()->t('Unexpected response from remote site: ') . EOL . htmlspecialchars($res));
			return;
		}

		$xml = XML::parseString($res);
		$status = (int) $xml->status;
		$message = XML::unescape($xml->message);   // human readable text of what may have gone wrong.
		switch ($status) {
			case 0:
				info(DI::l10n()->t("Confirmation completed successfully."));
				break;
			case 1:
				// birthday paradox - generate new dfrn-id and fall through.
				$new_dfrn_id = Strings::getRandomHex();
				q("UPDATE contact SET `issued-id` = '%s' WHERE `id` = %d AND `uid` = %d",
					DBA::escape($new_dfrn_id),
					intval($contact_id),
					intval($uid)
				);

			case 2:
				notice(DI::l10n()->t("Temporary failure. Please wait and try again."));
				break;
			case 3:
				notice(DI::l10n()->t("Introduction failed or was revoked."));
				break;
		}

		if (strlen($message)) {
			notice(DI::l10n()->t('Remote site reported: ') . $message);
		}

		if (($status == 0) && $intro_id) {
			$intro = DBA::selectFirst('intro', ['note'], ['id' => $intro_id]);
			if (DBA::isResult($intro)) {
				DBA::update('contact', ['reason' => $intro['note']], ['id' => $contact_id]);
			}

			// Success. Delete the notification.
			DBA::delete('intro', ['id' => $intro_id]);
		}

		if ($status != 0) {
			return;
		}

		/*
		 * We have now established a relationship with the other site.
		 * Let's make our own personal copy of their profile photo so we don't have
		 * to always load it from their site.
		 *
		 * We will also update the contact record with the nature and scope of the relationship.
		 */
		Contact::updateAvatar($contact_id, $contact['photo']);

		Logger::log('dfrn_confirm: confirm - imported photos');

		$new_relation = Contact::FOLLOWER;

		if (($relation == Contact::SHARING) || ($duplex)) {
			$new_relation = Contact::FRIEND;
		}

		if (($relation == Contact::SHARING) && ($duplex)) {
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
			DBA::escape(DateTimeFormat::utcNow()),
			DBA::escape(DateTimeFormat::utcNow()),
			intval($duplex),
			intval($hidden),
			DBA::escape(Protocol::DFRN),
			intval($contact_id)
		);

		// reload contact info
		$contact = DBA::selectFirst('contact', [], ['id' => $contact_id]);

		Group::addMember(User::getDefaultGroup($uid, $contact["network"]), $contact['id']);

		// Let's send our user to the contact editor in case they want to
		// do anything special with this new friend.
		if ($handsfree === null) {
			DI::baseUrl()->redirect('contact/' . intval($contact_id));
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
	if (!empty($_POST['source_url'])) {
		// We are processing an external confirmation to an introduction created by our user.
		$public_key =         $_POST['public_key'] ?? '';
		$dfrn_id    = hex2bin($_POST['dfrn_id']    ?? '');
		$source_url = hex2bin($_POST['source_url'] ?? '');
		$aes_key    =         $_POST['aes_key']    ?? '';
		$duplex     =  intval($_POST['duplex']     ?? 0);
		$page       =  intval($_POST['page']       ?? 0);

		$forum = (($page == 1) ? 1 : 0);
		$prv   = (($page == 2) ? 1 : 0);

		Logger::notice('requestee contacted', ['node' => $node]);

		Logger::debug('request', ['POST' => $_POST]);

		// If $aes_key is set, both of these items require unpacking from the hex transport encoding.

		if (!empty($aes_key)) {
			$aes_key = hex2bin($aes_key);
			$public_key = hex2bin($public_key);
		}

		// Find our user's account
		$user = DBA::selectFirst('user', [], ['nickname' => $node]);
		if (!DBA::isResult($user)) {
			$message = DI::l10n()->t('No user record found for \'%s\' ', $node);
			System::xmlExit(3, $message); // failure
			// NOTREACHED
		}

		$my_prvkey = $user['prvkey'];
		$local_uid = $user['uid'];


		if (!strstr($my_prvkey, 'PRIVATE KEY')) {
			$message = DI::l10n()->t('Our site encryption key is apparently messed up.');
			System::xmlExit(3, $message);
		}

		// verify everything

		$decrypted_source_url = "";
		openssl_private_decrypt($source_url, $decrypted_source_url, $my_prvkey);


		if (!strlen($decrypted_source_url)) {
			$message = DI::l10n()->t('Empty site URL was provided or URL could not be decrypted by us.');
			System::xmlExit(3, $message);
			// NOTREACHED
		}

		$contact = DBA::selectFirst('contact', [], ['url' => $decrypted_source_url, 'uid' => $local_uid]);
		if (!DBA::isResult($contact)) {
			if (strstr($decrypted_source_url, 'http:')) {
				$newurl = str_replace('http:', 'https:', $decrypted_source_url);
			} else {
				$newurl = str_replace('https:', 'http:', $decrypted_source_url);
			}

			$contact = DBA::selectFirst('contact', [], ['url' => $newurl, 'uid' => $local_uid]);
			if (!DBA::isResult($contact)) {
				// this is either a bogus confirmation (?) or we deleted the original introduction.
				$message = DI::l10n()->t('Contact record was not found for you on our site.');
				System::xmlExit(3, $message);
				return; // NOTREACHED
			}
		}

		$relation = $contact['rel'];

		// Decrypt all this stuff we just received

		$foreign_pubkey = $contact['site-pubkey'];
		$dfrn_record = $contact['id'];

		if (!$foreign_pubkey) {
			$message = DI::l10n()->t('Site public key not available in contact record for URL %s.', $decrypted_source_url);
			System::xmlExit(3, $message);
		}

		$decrypted_dfrn_id = "";
		openssl_public_decrypt($dfrn_id, $decrypted_dfrn_id, $foreign_pubkey);

		if (strlen($aes_key)) {
			$decrypted_aes_key = "";
			openssl_private_decrypt($aes_key, $decrypted_aes_key, $my_prvkey);
			$dfrn_pubkey = openssl_decrypt($public_key, 'AES-256-CBC', $decrypted_aes_key);
		} else {
			$dfrn_pubkey = $public_key;
		}

		if (DBA::exists('contact', ['dfrn-id' => $decrypted_dfrn_id])) {
			$message = DI::l10n()->t('The ID provided by your system is a duplicate on our system. It should work if you try again.');
			System::xmlExit(1, $message); // Birthday paradox - duplicate dfrn-id
			// NOTREACHED
		}

		$r = q("UPDATE `contact` SET `dfrn-id` = '%s', `pubkey` = '%s' WHERE `id` = %d",
			DBA::escape($decrypted_dfrn_id),
			DBA::escape($dfrn_pubkey),
			intval($dfrn_record)
		);
		if (!DBA::isResult($r)) {
			$message = DI::l10n()->t('Unable to set your contact credentials on our system.');
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
		$contact = DBA::selectFirst('contact', ['photo'], ['id' => $dfrn_record]);
		if (DBA::isResult($contact)) {
			$photo = $contact['photo'];
		} else {
			$photo = DI::baseUrl() . Contact::DEFAULT_AVATAR_PHOTO;
		}

		Contact::updateAvatar($dfrn_record, $photo);

		Logger::log('dfrn_confirm: request - photos imported');

		$new_relation = Contact::SHARING;

		if (($relation == Contact::FOLLOWER) || ($duplex)) {
			$new_relation = Contact::FRIEND;
		}

		if (($relation == Contact::FOLLOWER) && ($duplex)) {
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
			DBA::escape(DateTimeFormat::utcNow()),
			DBA::escape(DateTimeFormat::utcNow()),
			intval($duplex),
			intval($forum),
			intval($prv),
			DBA::escape(Protocol::DFRN),
			intval($dfrn_record)
		);
		if (!DBA::isResult($r)) {	// indicates schema is messed up or total db failure
			$message = DI::l10n()->t('Unable to update your contact profile details on our system');
			System::xmlExit(3, $message);
		}

		// Otherwise everything seems to have worked and we are almost done. Yay!
		// Send an email notification

		Logger::log('dfrn_confirm: request: info updated');

		$combined = null;
		$r = q("SELECT `contact`.*, `user`.*
			FROM `contact`
			LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`id` = %d
			LIMIT 1",
			intval($dfrn_record)
		);
		if (DBA::isResult($r)) {
			$combined = $r[0];

			if ($combined['notify-flags'] & Notification\Type::CONFIRM) {
				$mutual = ($new_relation == Contact::FRIEND);
				notification([
					'type'  => Notification\Type::CONFIRM,
					'otype' => Notification\ObjectType::INTRO,
					'verb'  => ($mutual ? Activity::FRIEND : Activity::FOLLOW),
					'uid'   => $combined['uid'],
					'cid'   => $combined['id'],
					'link'  => DI::baseUrl() . '/contact/' . $dfrn_record,
				]);
			}
		}

		System::xmlExit(0); // Success
		return; // NOTREACHED
		////////////////////// End of this scenario ///////////////////////////////////////////////
	}

	// somebody arrived here by mistake or they are fishing. Send them to the homepage.
	DI::baseUrl()->redirect();
	// NOTREACHED
}

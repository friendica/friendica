<?php

/**
 * @file mod/dfrn_poll.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Module\Login;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\OStatus;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Util\XML;

require_once 'include/items.php';

function dfrn_poll_init(App $a)
{
	Login::sessionAuth();

	$dfrn_id         = defaults($_GET, 'dfrn_id'        , '');
	$type            = defaults($_GET, 'type'           , 'data');
	$last_update     = defaults($_GET, 'last_update'    , '');
	$destination_url = defaults($_GET, 'destination_url', '');
	$challenge       = defaults($_GET, 'challenge'      , '');
	$sec             = defaults($_GET, 'sec'            , '');
	$dfrn_version    = (float) defaults($_GET, 'dfrn_version'   , 2.0);
	$perm            = defaults($_GET, 'perm'           , 'r');
	$quiet			 = x($_GET, 'quiet');

	// Possibly it is an OStatus compatible server that requests a user feed
	if (($a->argc > 1) && ($dfrn_id == '') && !strstr($_SERVER["HTTP_USER_AGENT"], 'Friendica')) {
		$nickname = $a->argv[1];
		header("Content-type: application/atom+xml");
		echo OStatus::feed($nickname, $last_update, 10);
		killme();
	}

	$direction = -1;

	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	$hidewall = false;

	if (($dfrn_id === '') && (!x($_POST, 'dfrn_id'))) {
		if (Config::get('system', 'block_public') && !local_user() && !remote_user()) {
			System::httpExit(403);
		}

		$user = '';
		if ($a->argc > 1) {
			$r = q("SELECT `hidewall`,`nickname` FROM `user` WHERE `user`.`nickname` = '%s' LIMIT 1",
				dbesc($a->argv[1])
			);
			if (!$r) {
				System::httpExit(404);
			}

			$hidewall = ($r[0]['hidewall'] && !local_user());

			$user = $r[0]['nickname'];
		}

		logger('dfrn_poll: public feed request from ' . $_SERVER['REMOTE_ADDR'] . ' for ' . $user);
		header("Content-type: application/atom+xml");
		echo DFRN::feed('', $user, $last_update, 0, $hidewall);
		killme();
	}

	if (($type === 'profile') && (!strlen($sec))) {
		$sql_extra = '';
		switch ($direction) {
			case -1:
				$sql_extra = sprintf(" AND ( `dfrn-id` = '%s' OR `issued-id` = '%s' ) ", dbesc($dfrn_id), dbesc($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				goaway(System::baseUrl());
				break; // NOTREACHED
		}

		$r = q("SELECT `contact`.*, `user`.`username`, `user`.`nickname`
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
			dbesc($a->argv[1])
		);

		if (DBM::is_result($r)) {
			$s = Network::fetchUrl($r[0]['poll'] . '?dfrn_id=' . $my_id . '&type=profile-check');

			logger("dfrn_poll: old profile returns " . $s, LOGGER_DATA);

			if (strlen($s)) {
				$xml = XML::parseString($s);

				if ((int) $xml->status === 1) {
					$_SESSION['authenticated'] = 1;
					if (!x($_SESSION, 'remote')) {
						$_SESSION['remote'] = [];
					}

					$_SESSION['remote'][] = ['cid' => $r[0]['id'], 'uid' => $r[0]['uid'], 'url' => $r[0]['url']];

					$_SESSION['visitor_id'] = $r[0]['id'];
					$_SESSION['visitor_home'] = $r[0]['url'];
					$_SESSION['visitor_handle'] = $r[0]['addr'];
					$_SESSION['visitor_visiting'] = $r[0]['uid'];
					if (!$quiet) {
						info(L10n::t('%1$s welcomes %2$s', $r[0]['username'], $r[0]['name']) . EOL);
					}

					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s'",
						dbesc($expire),
						dbesc($session_id)
					);
				}
			}
			$profile = $r[0]['nickname'];
			goaway((strlen($destination_url)) ? $destination_url : System::baseUrl() . '/profile/' . $profile);
		}
		goaway(System::baseUrl());
	}

	if ($type === 'profile-check' && $dfrn_version < 2.2) {
		if ((strlen($challenge)) && (strlen($sec))) {
			q("DELETE FROM `profile_check` WHERE `expire` < " . intval(time()));
			$r = q("SELECT * FROM `profile_check` WHERE `sec` = '%s' ORDER BY `expire` DESC LIMIT 1",
				dbesc($sec)
			);
			if (!DBM::is_result($r)) {
				System::xmlExit(3, 'No ticket');
				// NOTREACHED
			}

			$orig_id = $r[0]['dfrn_id'];
			if (strpos($orig_id, ':')) {
				$orig_id = substr($orig_id, 2);
			}

			$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($r[0]['cid'])
			);
			if (!DBM::is_result($c)) {
				System::xmlExit(3, 'No profile');
			}

			$contact = $c[0];

			$sent_dfrn_id = hex2bin($dfrn_id);
			$challenge = hex2bin($challenge);

			$final_dfrn_id = '';

			if (($contact['duplex']) && strlen($contact['prvkey'])) {
				Crypto::opensslPrivateDecrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
				Crypto::opensslPrivateDecrypt($challenge, $decoded_challenge, $contact['prvkey']);
			} else {
				openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
				openssl_public_decrypt($challenge, $decoded_challenge, $contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if (strpos($final_dfrn_id, ':') == 1) {
				$final_dfrn_id = substr($final_dfrn_id, 2);
			}

			if ($final_dfrn_id != $orig_id) {
				logger('profile_check: ' . $final_dfrn_id . ' != ' . $orig_id, LOGGER_DEBUG);
				// did not decode properly - cannot trust this site
				System::xmlExit(3, 'Bad decryption');
			}

			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><dfrn_poll><status>0</status><challenge>$decoded_challenge</challenge><sec>$sec</sec></dfrn_poll>";
			killme();
			// NOTREACHED
		} else {
			// old protocol
			switch ($direction) {
				case 1:
					$dfrn_id = '0:' . $dfrn_id;
					break;
				case 0:
					$dfrn_id = '1:' . $dfrn_id;
					break;
				default:
					break;
			}

			q("DELETE FROM `profile_check` WHERE `expire` < " . intval(time()));
			$r = q("SELECT * FROM `profile_check` WHERE `dfrn_id` = '%s' ORDER BY `expire` DESC",
				dbesc($dfrn_id));
			if (DBM::is_result($r)) {
				System::xmlExit(1);
				return; // NOTREACHED
			}
			System::xmlExit(0);
			return; // NOTREACHED
		}
	}
}

function dfrn_poll_post(App $a)
{
	$dfrn_id      = x($_POST,'dfrn_id')      ? $_POST['dfrn_id']              : '';
	$challenge    = x($_POST,'challenge')    ? $_POST['challenge']            : '';
	$url          = x($_POST,'url')          ? $_POST['url']                  : '';
	$sec          = x($_POST,'sec')          ? $_POST['sec']                  : '';
	$ptype        = x($_POST,'type')         ? $_POST['type']                 : '';
	$dfrn_version = x($_POST,'dfrn_version') ? (float) $_POST['dfrn_version'] : 2.0;
	$perm         = x($_POST,'perm')         ? $_POST['perm']                 : 'r';

	if ($ptype === 'profile-check') {
		if (strlen($challenge) && strlen($sec)) {
			logger('dfrn_poll: POST: profile-check');

			q("DELETE FROM `profile_check` WHERE `expire` < " . intval(time()));
			$r = q("SELECT * FROM `profile_check` WHERE `sec` = '%s' ORDER BY `expire` DESC LIMIT 1",
				dbesc($sec)
			);
			if (!DBM::is_result($r)) {
				System::xmlExit(3, 'No ticket');
				// NOTREACHED
			}

			$orig_id = $r[0]['dfrn_id'];
			if (strpos($orig_id, ':')) {
				$orig_id = substr($orig_id, 2);
			}

			$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($r[0]['cid'])
			);
			if (!DBM::is_result($c)) {
				System::xmlExit(3, 'No profile');
			}

			$contact = $c[0];

			$sent_dfrn_id = hex2bin($dfrn_id);
			$challenge = hex2bin($challenge);

			$final_dfrn_id = '';

			if ($contact['duplex'] && strlen($contact['prvkey'])) {
				Crypto::opensslPrivateDecrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
				Crypto::opensslPrivateDecrypt($challenge, $decoded_challenge, $contact['prvkey']);
			} else {
				openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
				openssl_public_decrypt($challenge, $decoded_challenge, $contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if (strpos($final_dfrn_id, ':') == 1) {
				$final_dfrn_id = substr($final_dfrn_id, 2);
			}

			if ($final_dfrn_id != $orig_id) {
				logger('profile_check: ' . $final_dfrn_id . ' != ' . $orig_id, LOGGER_DEBUG);
				// did not decode properly - cannot trust this site
				System::xmlExit(3, 'Bad decryption');
			}

			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><dfrn_poll><status>0</status><challenge>$decoded_challenge</challenge><sec>$sec</sec></dfrn_poll>";
			killme();
			// NOTREACHED
		}
	}

	$direction = -1;
	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		dbesc($dfrn_id),
		dbesc($challenge)
	);

	if (!DBM::is_result($r)) {
		killme();
	}

	$type = $r[0]['type'];
	$last_update = $r[0]['last_update'];

	$r = q("DELETE FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s'",
		dbesc($dfrn_id),
		dbesc($challenge)
	);


	$sql_extra = '';
	switch ($direction) {
		case -1:
			$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
			$my_id = $dfrn_id;
			break;
		case 0:
			$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			$my_id = '1:' . $dfrn_id;
			break;
		case 1:
			$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
			$my_id = '0:' . $dfrn_id;
			break;
		default:
			goaway(System::baseUrl());
			break; // NOTREACHED
	}

	$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 $sql_extra LIMIT 1");
	if (!DBM::is_result($r)) {
		killme();
	}

	$contact = $r[0];
	$owner_uid = $r[0]['uid'];
	$contact_id = $r[0]['id'];

	if ($type === 'reputation' && strlen($url)) {
		$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($url),
			intval($owner_uid)
		);
		$reputation = 0;
		$text = '';

		if (DBM::is_result($r)) {
			$reputation = $r[0]['rating'];
			$text = $r[0]['reason'];

			if ($r[0]['id'] == $contact_id) { // inquiring about own reputation not allowed
				$reputation = 0;
				$text = '';
			}
		}

		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<reputation>
			<url>$url</url>
			<rating>$reputation</rating>
			<description>$text</description>
		</reputation>
		";
		killme();
		// NOTREACHED
	} else {
		// Update the writable flag if it changed
		logger('dfrn_poll: post request feed: ' . print_r($_POST, true), LOGGER_DATA);
		if ($dfrn_version >= 2.21) {
			if ($perm === 'rw') {
				$writable = 1;
			} else {
				$writable = 0;
			}

			if ($writable != $contact['writable']) {
				q("UPDATE `contact` SET `writable` = %d WHERE `id` = %d",
					intval($writable),
					intval($contact_id)
				);
			}
		}

		header("Content-type: application/atom+xml");
		$o = DFRN::feed($dfrn_id, $a->argv[1], $last_update, $direction);
		echo $o;
		killme();
	}
}

function dfrn_poll_content(App $a)
{
	$dfrn_id         = x($_GET,'dfrn_id')         ? $_GET['dfrn_id']              : '';
	$type            = x($_GET,'type')            ? $_GET['type']                 : 'data';
	$last_update     = x($_GET,'last_update')     ? $_GET['last_update']          : '';
	$destination_url = x($_GET,'destination_url') ? $_GET['destination_url']      : '';
	$sec             = x($_GET,'sec')             ? $_GET['sec']                  : '';
	$dfrn_version    = x($_GET,'dfrn_version')    ? (float) $_GET['dfrn_version'] : 2.0;
	$perm            = x($_GET,'perm')            ? $_GET['perm']                 : 'r';
	$quiet           = x($_GET,'quiet')           ? true                          : false;

	$direction = -1;
	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	if ($dfrn_id != '') {
		// initial communication from external contact
		$hash = random_string();

		$status = 0;

		$r = q("DELETE FROM `challenge` WHERE `expire` < " . intval(time()));

		if ($type !== 'profile') {
			$r = q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` , `type`, `last_update` )
				VALUES( '%s', '%s', '%s', '%s', '%s' ) ",
				dbesc($hash),
				dbesc($dfrn_id),
				intval(time() + 60 ),
				dbesc($type),
				dbesc($last_update)
			);
		}

		$sql_extra = '';
		switch ($direction) {
			case -1:
				if ($type === 'profile') {
					$sql_extra = sprintf(" AND ( `dfrn-id` = '%s' OR `issued-id` = '%s' ) ", dbesc($dfrn_id), dbesc($dfrn_id));
				} else {
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", dbesc($dfrn_id));
				}

				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", dbesc($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				goaway(System::baseUrl());
				break; // NOTREACHED
		}

		$nickname = $a->argv[1];

		$r = q("SELECT `contact`.*, `user`.`username`, `user`.`nickname`
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
			dbesc($nickname)
		);
		if (DBM::is_result($r)) {
			$challenge = '';
			$encrypted_id = '';
			$id_str = $my_id . '.' . mt_rand(1000, 9999);

			if (($r[0]['duplex'] && strlen($r[0]['pubkey'])) || !strlen($r[0]['prvkey'])) {
				openssl_public_encrypt($hash, $challenge, $r[0]['pubkey'], OPENSSL_PKCS1_OAEP_PADDING);
				openssl_public_encrypt($id_str, $encrypted_id, $r[0]['pubkey'], OPENSSL_PKCS1_OAEP_PADDING);
			} else {
				openssl_private_encrypt($hash, $challenge, $r[0]['prvkey']);
				openssl_private_encrypt($id_str, $encrypted_id, $r[0]['prvkey']);
			}

			$challenge = bin2hex($challenge);
			$encrypted_id = bin2hex($encrypted_id);
		} else {
			$status = 1;
			$challenge = '';
			$encrypted_id = '';
		}

		if (($type === 'profile') && (strlen($sec))) {
			// URL reply
			if ($dfrn_version < 2.2) {
				$s = Network::fetchUrl($r[0]['poll']
					. '?dfrn_id=' . $encrypted_id
					. '&type=profile-check'
					. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
					. '&challenge=' . $challenge
					. '&sec=' . $sec
				);
			} else {
				$s = Network::post($r[0]['poll'], [
					'dfrn_id' => $encrypted_id,
					'type' => 'profile-check',
					'dfrn_version' => DFRN_PROTOCOL_VERSION,
					'challenge' => $challenge,
					'sec' => $sec
				]);
			}

			$profile = ((DBM::is_result($r) && $r[0]['nickname']) ? $r[0]['nickname'] : $nickname);

			switch ($destination_url) {
				case 'profile':
					$dest = System::baseUrl() . '/profile/' . $profile . '?f=&tab=profile';
					break;
				case 'photos':
					$dest = System::baseUrl() . '/photos/' . $profile;
					break;
				case 'status':
				case '':
					$dest = System::baseUrl() . '/profile/' . $profile;
					break;
				default:
					$appendix = (strstr($destination_url, '?') ? '&f=&redir=1' : '?f=&redir=1');
					$dest = $destination_url . $appendix;
					break;
			}

			logger("dfrn_poll: sec profile: " . $s, LOGGER_DATA);

			if (strlen($s) && strstr($s, '<?xml')) {
				$xml = XML::parseString($s);

				logger('dfrn_poll: profile: parsed xml: ' . print_r($xml, true), LOGGER_DATA);

				logger('dfrn_poll: secure profile: challenge: ' . $xml->challenge . ' expecting ' . $hash);
				logger('dfrn_poll: secure profile: sec: ' . $xml->sec . ' expecting ' . $sec);

				if (((int) $xml->status == 0) && ($xml->challenge == $hash) && ($xml->sec == $sec)) {
					$_SESSION['authenticated'] = 1;
					if (!x($_SESSION, 'remote')) {
						$_SESSION['remote'] = [];
					}

					$_SESSION['remote'][] = ['cid' => $r[0]['id'], 'uid' => $r[0]['uid'], 'url' => $r[0]['url']];
					$_SESSION['visitor_id'] = $r[0]['id'];
					$_SESSION['visitor_home'] = $r[0]['url'];
					$_SESSION['visitor_visiting'] = $r[0]['uid'];
					if (!$quiet) {
						info(L10n::t('%1$s welcomes %2$s', $r[0]['username'], $r[0]['name']) . EOL);
					}

					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s'",
						dbesc($expire),
						dbesc($session_id)
					);
				}

				goaway($dest);
			}
			goaway($dest);
			// NOTREACHED
		} else {
			// XML reply
			header("Content-type: text/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n"
				. '<dfrn_poll>' . "\r\n"
				. "\t" . '<status>' . $status . '</status>' . "\r\n"
				. "\t" . '<dfrn_version>' . DFRN_PROTOCOL_VERSION . '</dfrn_version>' . "\r\n"
				. "\t" . '<dfrn_id>' . $encrypted_id . '</dfrn_id>' . "\r\n"
				. "\t" . '<challenge>' . $challenge . '</challenge>' . "\r\n"
				. '</dfrn_poll>' . "\r\n";
			killme();
			// NOTREACHED
		}
	}
}

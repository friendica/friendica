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
 */

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\DFRN;
use Friendica\Protocol\OStatus;
use Friendica\Util\Strings;
use Friendica\Util\XML;

function dfrn_poll_init(App $a)
{
	DI::auth()->withSession($a);

	$dfrn_id         =  $_GET['dfrn_id']         ?? '';
	$type            = ($_GET['type']            ?? '') ?: 'data';
	$last_update     =  $_GET['last_update']     ?? '';
	$destination_url =  $_GET['destination_url'] ?? '';
	$challenge       =  $_GET['challenge']       ?? '';
	$sec             =  $_GET['sec']             ?? '';
	$dfrn_version    = floatval(($_GET['dfrn_version'] ?? 0.0) ?: 2.0);
	$quiet			 = !empty($_GET['quiet']);

	// Possibly it is an OStatus compatible server that requests a user feed
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
	if (($a->argc > 1) && ($dfrn_id == '') && !strstr($user_agent, 'Friendica')) {
		$nickname = $a->argv[1];
		header("Content-type: application/atom+xml");
		echo OStatus::feed($nickname, $last_update, 10);
		exit();
	}

	$direction = -1;

	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	$hidewall = false;

	if (($dfrn_id === '') && empty($_POST['dfrn_id'])) {
		if (DI::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$user = '';
		if ($a->argc > 1) {
			$r = q("SELECT `hidewall`,`nickname` FROM `user` WHERE `user`.`nickname` = '%s' LIMIT 1",
				DBA::escape($a->argv[1])
			);
			if (!$r) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}

			$hidewall = ($r[0]['hidewall'] && !local_user());

			$user = $r[0]['nickname'];
		}

		Logger::log('dfrn_poll: public feed request from ' . $_SERVER['REMOTE_ADDR'] . ' for ' . $user);
		header("Content-type: application/atom+xml");
		echo DFRN::feed('', $user, $last_update, 0, $hidewall);
		exit();
	}

	if (($type === 'profile') && (!strlen($sec))) {
		$sql_extra = '';
		switch ($direction) {
			case -1:
				$sql_extra = sprintf(" AND ( `dfrn-id` = '%s' OR `issued-id` = '%s' ) ", DBA::escape($dfrn_id), DBA::escape($dfrn_id));
				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				DI::baseUrl()->redirect();
				break; // NOTREACHED
		}

		$r = q("SELECT `contact`.*, `user`.`username`, `user`.`nickname`
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
			DBA::escape($a->argv[1])
		);

		if (DBA::isResult($r)) {
			$s = DI::httpRequest()->fetch($r[0]['poll'] . '?dfrn_id=' . $my_id . '&type=profile-check');

			Logger::log("dfrn_poll: old profile returns " . $s, Logger::DATA);

			if (strlen($s)) {
				$xml = XML::parseString($s);

				if ((int)$xml->status === 1) {
					$_SESSION['authenticated'] = 1;
					$_SESSION['visitor_id'] = $r[0]['id'];
					$_SESSION['visitor_home'] = $r[0]['url'];
					$_SESSION['visitor_handle'] = $r[0]['addr'];
					$_SESSION['visitor_visiting'] = $r[0]['uid'];
					$_SESSION['my_url'] = $r[0]['url'];

					Session::setVisitorsContacts();

					if (!$quiet) {
						info(DI::l10n()->t('%1$s welcomes %2$s', $r[0]['username'], $r[0]['name']) . EOL);
					}

					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s'",
						DBA::escape($expire),
						DBA::escape($session_id)
					);
				}
			}

			$profile = (count($r) > 0 && isset($r[0]['nickname']) ? $r[0]['nickname'] : '');
			if (!empty($destination_url)) {
				System::externalRedirect($destination_url);
			} else {
				DI::baseUrl()->redirect('profile/' . $profile);
			}
		}
		DI::baseUrl()->redirect();
	}

	if ($type === 'profile-check' && $dfrn_version < 2.2) {
		if ((strlen($challenge)) && (strlen($sec))) {
			DBA::delete('profile_check', ["`expire` < ?", time()]);
			$r = q("SELECT * FROM `profile_check` WHERE `sec` = '%s' ORDER BY `expire` DESC LIMIT 1",
				DBA::escape($sec)
			);
			if (!DBA::isResult($r)) {
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
			if (!DBA::isResult($c)) {
				System::xmlExit(3, 'No profile');
			}

			$contact = $c[0];

			$sent_dfrn_id = hex2bin($dfrn_id);
			$challenge = hex2bin($challenge);

			$final_dfrn_id = '';

			if (($contact['duplex']) && strlen($contact['prvkey'])) {
				openssl_private_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
				openssl_private_decrypt($challenge, $decoded_challenge, $contact['prvkey']);
			} else {
				openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
				openssl_public_decrypt($challenge, $decoded_challenge, $contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if (strpos($final_dfrn_id, ':') == 1) {
				$final_dfrn_id = substr($final_dfrn_id, 2);
			}

			if ($final_dfrn_id != $orig_id) {
				Logger::log('profile_check: ' . $final_dfrn_id . ' != ' . $orig_id, Logger::DEBUG);
				// did not decode properly - cannot trust this site
				System::xmlExit(3, 'Bad decryption');
			}

			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><dfrn_poll><status>0</status><challenge>$decoded_challenge</challenge><sec>$sec</sec></dfrn_poll>";
			exit();
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

			DBA::delete('profile_check', ["`expire` < ?", time()]);
			$r = q("SELECT * FROM `profile_check` WHERE `dfrn_id` = '%s' ORDER BY `expire` DESC",
				DBA::escape($dfrn_id));
			if (DBA::isResult($r)) {
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
	$dfrn_id      =  $_POST['dfrn_id']   ?? '';
	$challenge    =  $_POST['challenge'] ?? '';
	$url          =  $_POST['url']       ?? '';
	$sec          =  $_POST['sec']       ?? '';
	$ptype        =  $_POST['type']      ?? '';
	$perm         = ($_POST['perm']      ?? '') ?: 'r';
	$dfrn_version = floatval(($_GET['dfrn_version'] ?? 0.0) ?: 2.0);

	if ($ptype === 'profile-check') {
		if (strlen($challenge) && strlen($sec)) {
			Logger::log('dfrn_poll: POST: profile-check');

			DBA::delete('profile_check', ["`expire` < ?", time()]);
			$r = q("SELECT * FROM `profile_check` WHERE `sec` = '%s' ORDER BY `expire` DESC LIMIT 1",
				DBA::escape($sec)
			);
			if (!DBA::isResult($r)) {
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
			if (!DBA::isResult($c)) {
				System::xmlExit(3, 'No profile');
			}

			$contact = $c[0];

			$sent_dfrn_id = hex2bin($dfrn_id);
			$challenge = hex2bin($challenge);

			$final_dfrn_id = '';

			if ($contact['duplex'] && strlen($contact['prvkey'])) {
				openssl_private_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['prvkey']);
				openssl_private_decrypt($challenge, $decoded_challenge, $contact['prvkey']);
			} else {
				openssl_public_decrypt($sent_dfrn_id, $final_dfrn_id, $contact['pubkey']);
				openssl_public_decrypt($challenge, $decoded_challenge, $contact['pubkey']);
			}

			$final_dfrn_id = substr($final_dfrn_id, 0, strpos($final_dfrn_id, '.'));

			if (strpos($final_dfrn_id, ':') == 1) {
				$final_dfrn_id = substr($final_dfrn_id, 2);
			}

			if ($final_dfrn_id != $orig_id) {
				Logger::log('profile_check: ' . $final_dfrn_id . ' != ' . $orig_id, Logger::DEBUG);
				// did not decode properly - cannot trust this site
				System::xmlExit(3, 'Bad decryption');
			}

			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?><dfrn_poll><status>0</status><challenge>$decoded_challenge</challenge><sec>$sec</sec></dfrn_poll>";
			exit();
			// NOTREACHED
		}
	}

	$direction = -1;
	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	$r = q("SELECT * FROM `challenge` WHERE `dfrn-id` = '%s' AND `challenge` = '%s' LIMIT 1",
		DBA::escape($dfrn_id),
		DBA::escape($challenge)
	);

	if (!DBA::isResult($r)) {
		exit();
	}

	$type = $r[0]['type'];
	$last_update = $r[0]['last_update'];

	DBA::delete('challenge', ['dfrn-id' => $dfrn_id, 'challenge' => $challenge]);

	$sql_extra = '';
	switch ($direction) {
		case -1:
			$sql_extra = sprintf(" AND `issued-id` = '%s' ", DBA::escape($dfrn_id));
			break;
		case 0:
			$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
			break;
		case 1:
			$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
			break;
		default:
			DI::baseUrl()->redirect();
			break; // NOTREACHED
	}

	$r = q("SELECT * FROM `contact` WHERE `blocked` = 0 AND `pending` = 0 $sql_extra LIMIT 1");
	if (!DBA::isResult($r)) {
		exit();
	}

	$contact = $r[0];
	$owner_uid = $r[0]['uid'];
	$contact_id = $r[0]['id'];

	if ($type === 'reputation' && strlen($url)) {
		$r = q("SELECT * FROM `contact` WHERE `url` = '%s' AND `uid` = %d LIMIT 1",
			DBA::escape($url),
			intval($owner_uid)
		);
		$reputation = 0;
		$text = '';

		if (DBA::isResult($r)) {
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
		exit();
		// NOTREACHED
	} else {
		// Update the writable flag if it changed
		Logger::log('dfrn_poll: post request feed: ' . print_r($_POST, true), Logger::DATA);
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
		exit();
	}
}

function dfrn_poll_content(App $a)
{
	$dfrn_id         =  $_GET['dfrn_id']         ?? '';
	$type            = ($_GET['type']            ?? '') ?: 'data';
	$last_update     =  $_GET['last_update']     ?? '';
	$destination_url =  $_GET['destination_url'] ?? '';
	$sec             =  $_GET['sec']             ?? '';
	$dfrn_version    = floatval(($_GET['dfrn_version'] ?? 0.0) ?: 2.0);
	$quiet           = !empty($_GET['quiet']);

	$direction = -1;
	if (strpos($dfrn_id, ':') == 1) {
		$direction = intval(substr($dfrn_id, 0, 1));
		$dfrn_id = substr($dfrn_id, 2);
	}

	if ($dfrn_id != '') {
		// initial communication from external contact
		$hash = Strings::getRandomHex();

		$status = 0;

		DBA::delete('challenge', ["`expire` < ?", time()]);

		if ($type !== 'profile') {
			q("INSERT INTO `challenge` ( `challenge`, `dfrn-id`, `expire` , `type`, `last_update` )
				VALUES( '%s', '%s', '%s', '%s', '%s' ) ",
				DBA::escape($hash),
				DBA::escape($dfrn_id),
				intval(time() + 60 ),
				DBA::escape($type),
				DBA::escape($last_update)
			);
		}

		$sql_extra = '';
		switch ($direction) {
			case -1:
				if ($type === 'profile') {
					$sql_extra = sprintf(" AND (`dfrn-id` = '%s' OR `issued-id` = '%s') ", DBA::escape($dfrn_id), DBA::escape($dfrn_id));
				} else {
					$sql_extra = sprintf(" AND `issued-id` = '%s' ", DBA::escape($dfrn_id));
				}

				$my_id = $dfrn_id;
				break;
			case 0:
				$sql_extra = sprintf(" AND `issued-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
				$my_id = '1:' . $dfrn_id;
				break;
			case 1:
				$sql_extra = sprintf(" AND `dfrn-id` = '%s' AND `duplex` = 1 ", DBA::escape($dfrn_id));
				$my_id = '0:' . $dfrn_id;
				break;
			default:
				DI::baseUrl()->redirect();
				break; // NOTREACHED
		}

		$nickname = $a->argv[1];

		$r = q("SELECT `contact`.*, `user`.`username`, `user`.`nickname`
			FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
			WHERE `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			AND `user`.`nickname` = '%s' $sql_extra LIMIT 1",
			DBA::escape($nickname)
		);
		if (DBA::isResult($r)) {
			$challenge = '';
			$encrypted_id = '';
			$id_str = $my_id . '.' . mt_rand(1000, 9999);

			if (($r[0]['duplex'] && strlen($r[0]['pubkey'])) || !strlen($r[0]['prvkey'])) {
				openssl_public_encrypt($hash, $challenge, $r[0]['pubkey']);
				openssl_public_encrypt($id_str, $encrypted_id, $r[0]['pubkey']);
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
			// heluecht: I don't know why we don't fail immediately when the user or contact hadn't been found.
			// Since it doesn't make sense to continue from this point on, we now fail here. This should be safe.
			if (!DBA::isResult($r)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}

			// URL reply
			if ($dfrn_version < 2.2) {
				$s = DI::httpRequest()->fetch($r[0]['poll']
				                              . '?dfrn_id=' . $encrypted_id
				                              . '&type=profile-check'
				                              . '&dfrn_version=' . DFRN_PROTOCOL_VERSION
				                              . '&challenge=' . $challenge
				                              . '&sec=' . $sec
				);
			} else {
				$s = DI::httpRequest()->post($r[0]['poll'], [
					'dfrn_id'      => $encrypted_id,
					'type'         => 'profile-check',
					'dfrn_version' => DFRN_PROTOCOL_VERSION,
					'challenge'    => $challenge,
					'sec'          => $sec
				])->getBody();
			}

			Logger::log("dfrn_poll: sec profile: " . $s, Logger::DATA);

			if (strlen($s) && strstr($s, '<?xml')) {
				$xml = XML::parseString($s);

				Logger::log('dfrn_poll: profile: parsed xml: ' . print_r($xml, true), Logger::DATA);

				Logger::log('dfrn_poll: secure profile: challenge: ' . $xml->challenge . ' expecting ' . $hash);
				Logger::log('dfrn_poll: secure profile: sec: ' . $xml->sec . ' expecting ' . $sec);

				if (((int) $xml->status == 0) && ($xml->challenge == $hash) && ($xml->sec == $sec)) {
					$_SESSION['authenticated'] = 1;
					$_SESSION['visitor_id'] = $r[0]['id'];
					$_SESSION['visitor_home'] = $r[0]['url'];
					$_SESSION['visitor_visiting'] = $r[0]['uid'];
					$_SESSION['my_url'] = $r[0]['url'];

					Session::setVisitorsContacts();

					if (!$quiet) {
						info(DI::l10n()->t('%1$s welcomes %2$s', $r[0]['username'], $r[0]['name']) . EOL);
					}

					// Visitors get 1 day session.
					$session_id = session_id();
					$expire = time() + 86400;
					q("UPDATE `session` SET `expire` = '%s' WHERE `sid` = '%s'",
						DBA::escape($expire),
						DBA::escape($session_id)
					);
				}
			}

			$profile = ((DBA::isResult($r) && $r[0]['nickname']) ? $r[0]['nickname'] : $nickname);

			switch ($destination_url) {
				case 'profile':
					DI::baseUrl()->redirect('profile/' . $profile . '/profile');
					break;
				case 'photos':
					DI::baseUrl()->redirect('photos/' . $profile);
					break;
				case 'status':
				case '':
					DI::baseUrl()->redirect('profile/' . $profile);
					break;
				default:
					$appendix = (strstr($destination_url, '?') ? '&redir=1' : '?redir=1');
					DI::baseUrl()->redirect($destination_url . $appendix);
					break;
			}
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
			exit();
			// NOTREACHED
		}
	}
}

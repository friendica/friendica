<?php

/**
 * @file mod/dfrn_request.php
 * @brief Module: dfrn_request
 *
 * Purpose: Handles communication associated with the issuance of
 * friend requests.
 *
 * @see PDF with dfrn specs: https://github.com/friendica/friendica/blob/master/spec/dfrn2.pdf
 *    You also find a graphic which describes the confirmation process at
 *    https://github.com/friendica/friendica/blob/master/spec/dfrn2_contact_request.png
 */

require_once('include/enotify.php');
require_once('include/Scrape.php');
require_once('include/Probe.php');
require_once('include/group.php');

function dfrn_request_init(App $a) {

	if($a->argc > 1)
		$which = $a->argv[1];

	profile_load($a,$which);
	return;
}


/**
 * Function: dfrn_request_post
 *
 * Purpose:
 * Handles multiple scenarios.
 *
 * Scenario 1:
 * Clicking 'submit' on a friend request page.
 *
 * Scenario 2:
 * Following Scenario 1, we are brought back to our home site
 * in order to link our friend request with our own server cell.
 * After logging in, we click 'submit' to approve the linkage.
 *
 */
function dfrn_request_post(App $a) {

	if(($a->argc != 2) || (! count($a->profile))) {
		logger('Wrong count of argc or profiles: argc=' . $a->argc . ',profile()=' . count($a->profile));
		return;
	}


	if(x($_POST, 'cancel')) {
		goaway(z_root());
	}


	/*
	 *
	 * Scenario 2: We've introduced ourself to another cell, then have been returned to our own cell
	 * to confirm the request, and then we've clicked submit (perhaps after logging in).
	 * That brings us here:
	 *
	 */

	if((x($_POST,'localconfirm')) && ($_POST['localconfirm'] == 1)) {

		/*
		 * Ensure this is a valid request
		 */

		if(local_user() && ($a->user['nickname'] == $a->argv[1]) && (x($_POST,'dfrn_url'))) {


			$dfrn_url    = notags(trim($_POST['dfrn_url']));
			$aes_allow   = (((x($_POST,'aes_allow')) && ($_POST['aes_allow'] == 1)) ? 1 : 0);
			$confirm_key = ((x($_POST,'confirm_key')) ? $_POST['confirm_key'] : "");
			$hidden = ((x($_POST,'hidden-contact')) ? intval($_POST['hidden-contact']) : 0);
			$contact_record = null;
			$blocked = 1;
			$pending = 1;

			if(x($dfrn_url)) {

				/*
				 * Lookup the contact based on their URL (which is the only unique thing we have at the moment)
				 */

				$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND NOT `self` LIMIT 1",
					intval(local_user()),
					dbesc(normalise_link($dfrn_url))
				);

				if (dbm::is_result($r)) {
					if(strlen($r[0]['dfrn-id'])) {

						/*
						 * We don't need to be here. It has already happened.
						 */

						notice( t("This introduction has already been accepted.") . EOL );
						return;
					}
					else
						$contact_record = $r[0];
				}

				if(is_array($contact_record)) {
					$r = q("UPDATE `contact` SET `ret-aes` = %d, hidden = %d WHERE `id` = %d",
						intval($aes_allow),
						intval($hidden),
						intval($contact_record['id'])
					);
				}
				else {

					/*
					 * Scrape the other site's profile page to pick up the dfrn links, key, fn, and photo
					 */

					$parms = Probe::profile($dfrn_url);

					if (! count($parms)) {
						notice( t('Profile location is not valid or does not contain profile information.') . EOL );
						return;
					}
					else {
						if (! x($parms,'fn')) {
							notice( t('Warning: profile location has no identifiable owner name.') . EOL );
						}
						if (! x($parms,'photo')) {
							notice( t('Warning: profile location has no profile photo.') . EOL );
						}
						$invalid = Probe::valid_dfrn($parms);
						if ($invalid) {
							notice( sprintf( tt("%d required parameter was not found at the given location",
												"%d required parameters were not found at the given location",
												$invalid), $invalid) . EOL );
							return;
						}
					}

					$dfrn_request = $parms['dfrn-request'];

					$photo = $parms["photo"];

					// Escape the entire array

					dbesc_array($parms);


					/*
					 * Create a contact record on our site for the other person
					 */

					$r = q("INSERT INTO `contact` ( `uid`, `created`,`url`, `nurl`, `addr`, `name`, `nick`, `photo`, `site-pubkey`,
						`request`, `confirm`, `notify`, `poll`, `poco`, `network`, `aes_allow`, `hidden`, `blocked`, `pending`)
						VALUES ( %d, '%s', '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d)",
						intval(local_user()),
						datetime_convert(),
						dbesc($dfrn_url),
						dbesc(normalise_link($dfrn_url)),
						$parms['addr'],
						$parms['fn'],
						$parms['nick'],
						$parms['photo'],
						$parms['key'],
						$parms['dfrn-request'],
						$parms['dfrn-confirm'],
						$parms['dfrn-notify'],
						$parms['dfrn-poll'],
						$parms['dfrn-poco'],
						dbesc(NETWORK_DFRN),
						intval($aes_allow),
						intval($hidden),
						intval($blocked),
						intval($pending)
					);
				}

				if ($r) {
					info( t("Introduction complete.") . EOL);
				}

				$r = q("SELECT `id`, `network` FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `site-pubkey` = '%s' LIMIT 1",
					intval(local_user()),
					dbesc($dfrn_url),
					$parms['key'] // this was already escaped
				);
				if (dbm::is_result($r)) {
					$def_gid = get_default_group(local_user(), $r[0]["network"]);
					if(intval($def_gid))
						group_add_member(local_user(), '', $r[0]['id'], $def_gid);

					if (isset($photo))
						update_contact_avatar($photo, local_user(), $r[0]["id"], true);

					$forwardurl = App::get_baseurl()."/contacts/".$r[0]['id'];
				} else {
					$forwardurl = App::get_baseurl()."/contacts";
				}

				/*
				 * Allow the blocked remote notification to complete
				 */

				if (is_array($contact_record)) {
					$dfrn_request = $contact_record['request'];
				}

				if (strlen($dfrn_request) && strlen($confirm_key)) {
					$s = fetch_url($dfrn_request . '?confirm_key=' . $confirm_key);
				}

				// (ignore reply, nothing we can do it failed)

				// Old: goaway(zrl($dfrn_url));
				goaway($forwardurl);
				return; // NOTREACHED

			}

		}

 		// invalid/bogus request

		notice( t('Unrecoverable protocol error.') . EOL );
		goaway(z_root());
		return; // NOTREACHED
	}

	/*
	 * Otherwise:
	 *
	 * Scenario 1:
	 * We are the requestee. A person from a remote cell has made an introduction
	 * on our profile web page and clicked submit. We will use their DFRN-URL to
	 * figure out how to contact their cell.
	 *
	 * Scrape the originating DFRN-URL for everything we need. Create a contact record
	 * and an introduction to show our user next time he/she logs in.
	 * Finally redirect back to the requestor so that their site can record the request.
	 * If our user (the requestee) later confirms this request, a record of it will need
	 * to exist on the requestor's cell in order for the confirmation process to complete..
	 *
	 * It's possible that neither the requestor or the requestee are logged in at the moment,
	 * and the requestor does not yet have any credentials to the requestee profile.
	 *
	 * Who is the requestee? We've already loaded their profile which means their nickname should be
	 * in $a->argv[1] and we should have their complete info in $a->profile.
	 *
	 */

	if(! (is_array($a->profile) && count($a->profile))) {
		notice( t('Profile unavailable.') . EOL);
		return;
	}

	$nickname       = $a->profile['nickname'];
	$notify_flags   = $a->profile['notify-flags'];
	$uid            = $a->profile['uid'];
	$maxreq         = intval($a->profile['maxreq']);
	$contact_record = null;
	$failed         = false;
	$parms          = null;
	$blocked = 1;
	$pending = 1;


	if( x($_POST,'dfrn_url')) {

		/*
		 * Block friend request spam
		 */

		if($maxreq) {
			$r = q("SELECT * FROM `intro` WHERE `datetime` > '%s' AND `uid` = %d",
				dbesc(datetime_convert('UTC','UTC','now - 24 hours')),
				intval($uid)
			);
			if (dbm::is_result($r) && count($r) > $maxreq) {
				notice( sprintf( t('%s has received too many connection requests today.'),  $a->profile['name']) . EOL);
				notice( t('Spam protection measures have been invoked.') . EOL);
				notice( t('Friends are advised to please try again in 24 hours.') . EOL);
				return;
			}
		}

		/*
		 *
		 * Cleanup old introductions that remain blocked.
		 * Also remove the contact record, but only if there is no existing relationship
		 * Do not remove email contacts as these may be awaiting email verification
		 */

		$r = q("SELECT `intro`.*, `intro`.`id` AS `iid`, `contact`.`id` AS `cid`, `contact`.`rel`
			FROM `intro` LEFT JOIN `contact` on `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`blocked` = 1 AND `contact`.`self` = 0
			AND `contact`.`network` != '%s'
			AND `intro`.`datetime` < UTC_TIMESTAMP() - INTERVAL 30 MINUTE ",
			dbesc(NETWORK_MAIL2)
		);
		if (dbm::is_result($r)) {
			foreach ($r as $rr) {
				if(! $rr['rel']) {
					q("DELETE FROM `contact` WHERE `id` = %d",
						intval($rr['cid'])
					);
				}
				q("DELETE FROM `intro` WHERE `id` = %d",
					intval($rr['iid'])
				);
			}
		}

		/*
		 *
		 * Cleanup any old email intros - which will have a greater lifetime
		 */

		$r = q("SELECT `intro`.*, `intro`.`id` AS `iid`, `contact`.`id` AS `cid`, `contact`.`rel`
			FROM `intro` LEFT JOIN `contact` on `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`blocked` = 1 AND `contact`.`self` = 0
			AND `contact`.`network` = '%s'
			AND `intro`.`datetime` < UTC_TIMESTAMP() - INTERVAL 3 DAY ",
			dbesc(NETWORK_MAIL2)
		);
		if (dbm::is_result($r)) {
			foreach ($r as $rr) {
				if(! $rr['rel']) {
					q("DELETE FROM `contact` WHERE `id` = %d",
						intval($rr['cid'])
					);
				}
				q("DELETE FROM `intro` WHERE `id` = %d",
					intval($rr['iid'])
				);
			}
		}

		$email_follow = (x($_POST,'email_follow') ? intval($_POST['email_follow']) : 0);
		$real_name = (x($_POST,'realname') ? notags(trim($_POST['realname'])) : '');

		$url = trim($_POST['dfrn_url']);
		if(! strlen($url)) {
			notice( t("Invalid locator") . EOL );
			return;
		}

		$hcard = '';

		if($email_follow) {

			if(! validate_email($url)) {
				notice( t('Invalid email address.') . EOL);
				return;
			}

			$addr    = $url;
			$name    = ($realname) ? $realname : $addr;
			$nick    = substr($addr,0,strpos($addr,'@'));
			$url     = 'http://' . substr($addr,strpos($addr,'@') + 1);
			$nurl    = normalise_url($host);
			$poll    = 'email ' . random_string();
			$notify  = 'smtp ' . random_string();
			$network = NETWORK_MAIL2;
			$rel     = CONTACT_IS_FOLLOWER;

			$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
			if(get_config('system','dfrn_only'))
				$mail_disabled = 1;

			if(! $mail_disabled) {
				$failed = false;
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
					intval($uid)
				);

				if (! dbm::is_result($r)) {
					notice( t('This account has not been configured for email. Request failed.') . EOL);
					return;
				}
			}

			$r = q("insert into contact ( uid, created, addr, name, nick, url, nurl, poll, notify, blocked, pending, network, rel )
				values( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d ) ",
				intval($uid),
				dbesc(datetime_convert()),
				dbesc($addr),
				dbesc($name),
				dbesc($nick),
				dbesc($url),
				dbesc($nurl),
				dbesc($poll),
				dbesc($notify),
				intval($blocked),
				intval($pending),
				dbesc($network),
				intval($rel)
			);

			$r = q("SELECT `id`, `network` FROM `contact` WHERE `poll` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($poll),
				intval($uid)
			);
			if (dbm::is_result($r)) {
				$contact_id = $r[0]['id'];

				$def_gid = get_default_group($uid, $r[0]["network"]);
				if (intval($def_gid))
					group_add_member($uid, '', $contact_id, $def_gid);

				$photo = avatar_img($addr);

				$r = q("UPDATE `contact` SET
					`photo` = '%s',
					`thumb` = '%s',
					`micro` = '%s',
					`name-date` = '%s',
					`uri-date` = '%s',
					`avatar-date` = '%s',
					`hidden` = 0,
					WHERE `id` = %d
				",
					dbesc($photos[0]),
					dbesc($photos[1]),
					dbesc($photos[2]),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					intval($contact_id)
				);
			}

			// contact is created. Now create an introduction

			$hash = random_string();

			$r = q("INSERT INTO `intro` ( `uid`, `contact-id`, knowyou, note, hash, datetime, blocked )
				VALUES( %d , %d, %d, '%s', '%s', '%s', %d ) ",
				intval($uid),
				intval($contact_id),
				((x($_POST,'knowyou') && ($_POST['knowyou'] == 1)) ? 1 : 0),
				dbesc(notags(trim($_POST['dfrn-request-message']))),
				dbesc($hash),
				dbesc(datetime_convert()),
				1
			);

			// Next send an email verify form to the requestor.

		} else {
			// Detect the network
			$data = probe_url($url);
			$network = $data["network"];

			// Canonicalise email-style profile locator
			$url = Probe::webfinger_dfrn($url,$hcard);

			if (substr($url,0,5) === 'stat:') {

				// Every time we detect the remote subscription we define this as OStatus.
				// We do this even if it is not OStatus.
				// we only need to pass this through another section of the code.
				if ($network != NETWORK_DIASPORA)
					$network = NETWORK_OSTATUS;

				$url = substr($url,5);
			} else
				$network = NETWORK_DFRN;
		}

		logger('dfrn_request: url: ' . $url . ',network=' . $network, LOGGER_DEBUG);

		if($network === NETWORK_DFRN) {
			$ret = q("SELECT * FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `self` = 0 LIMIT 1",
				intval($uid),
				dbesc($url)
			);

			if (dbm::is_result($ret)) {
				if(strlen($ret[0]['issued-id'])) {
					notice( t('You have already introduced yourself here.') . EOL );
					return;
				}
				elseif($ret[0]['rel'] == CONTACT_IS_FRIEND) {
					notice( sprintf( t('Apparently you are already friends with %s.'), $a->profile['name']) . EOL);
					return;
				}
				else {
					$contact_record = $ret[0];
					$parms = array('dfrn-request' => $ret[0]['request']);
				}
			}

			$issued_id = random_string();

			if(is_array($contact_record)) {
				// There is a contact record but no issued-id, so this
				// is a reciprocal introduction from a known contact
				$r = q("UPDATE `contact` SET `issued-id` = '%s' WHERE `id` = %d",
					dbesc($issued_id),
					intval($contact_record['id'])
				);
			}
			else {
				if (! validate_url($url)) {
					notice( t('Invalid profile URL.') . EOL);
					goaway(App::get_baseurl() . '/' . $a->cmd);
					return; // NOTREACHED
				}

				if (! allowed_url($url)) {
					notice( t('Disallowed profile URL.') . EOL);
					goaway(App::get_baseurl() . '/' . $a->cmd);
					return; // NOTREACHED
				}


				require_once('include/Scrape.php');

				$parms = Probe::profile(($hcard) ? $hcard : $url);

				if (! count($parms)) {
					notice( t('Profile location is not valid or does not contain profile information.') . EOL );
					goaway(App::get_baseurl() . '/' . $a->cmd);
				}
				else {
					if (! x($parms,'fn')) {
						notice( t('Warning: profile location has no identifiable owner name.') . EOL );
					}
					if (! x($parms,'photo')) {
						notice( t('Warning: profile location has no profile photo.') . EOL );
					}
					$invalid = Probe::valid_dfrn($parms);
					if ($invalid) {
						notice( sprintf( tt("%d required parameter was not found at the given location",
											"%d required parameters were not found at the given location",
											$invalid), $invalid) . EOL );

						return;
					}
				}


				$parms['url'] = $url;
				$parms['issued-id'] = $issued_id;
				$photo = $parms["photo"];

				dbesc_array($parms);
				$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `name`, `nick`, `issued-id`, `photo`, `site-pubkey`,
					`request`, `confirm`, `notify`, `poll`, `poco`, `network`, `blocked`, `pending` )
					VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d )",
					intval($uid),
					dbesc(datetime_convert()),
					$parms['url'],
					dbesc(normalise_link($url)),
					$parms['addr'],
					$parms['fn'],
					$parms['nick'],
					$parms['issued-id'],
					$parms['photo'],
					$parms['key'],
					$parms['dfrn-request'],
					$parms['dfrn-confirm'],
					$parms['dfrn-notify'],
					$parms['dfrn-poll'],
					$parms['dfrn-poco'],
					dbesc(NETWORK_DFRN),
					intval($blocked),
					intval($pending)
				);

				// find the contact record we just created
				if ($r) {
					$r = q("SELECT `id` FROM `contact`
						WHERE `uid` = %d AND `url` = '%s' AND `issued-id` = '%s' LIMIT 1",
						intval($uid),
						$parms['url'],
						$parms['issued-id']
					);
					if (dbm::is_result($r)) {
						$contact_record = $r[0];
						update_contact_avatar($photo, $uid, $contact_record["id"], true);
					}
				}

			}
			if ($r === false) {
				notice( t('Failed to update contact record.') . EOL );
				return;
			}

			$hash = random_string() . (string) time();   // Generate a confirm_key

			if (is_array($contact_record)) {
				$ret = q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`)
					VALUES ( %d, %d, 1, %d, '%s', '%s', '%s' )",
					intval($uid),
					intval($contact_record['id']),
					((x($_POST,'knowyou') && ($_POST['knowyou'] == 1)) ? 1 : 0),
					dbesc(notags(trim($_POST['dfrn-request-message']))),
					dbesc($hash),
					dbesc(datetime_convert())
				);
			}

			// This notice will only be seen by the requestor if the requestor and requestee are on the same server.

			if (! $failed) {
				info( t('Your introduction has been sent.') . EOL );
			}

			// "Homecoming" - send the requestor back to their site to record the introduction.

			$dfrn_url = bin2hex(App::get_baseurl() . '/profile/' . $nickname);
			$aes_allow = ((function_exists('openssl_encrypt')) ? 1 : 0);

			goaway($parms['dfrn-request'] . "?dfrn_url=$dfrn_url"
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
				. '&confirm_key='  . $hash
				. (($aes_allow) ? "&aes_allow=1" : "")
			);
			// NOTREACHED
			// END $network === NETWORK_DFRN
		} elseif (($network != NETWORK_PHANTOM) AND ($url != "")) {

			/*
			 *
			 * Substitute our user's feed URL into $url template
			 * Send the subscriber home to subscribe
			 *
			 */

			// Diaspora needs the uri in the format user@domain.tld
			// Diaspora will support the remote subscription in a future version
			if ($network == NETWORK_DIASPORA) {
				$uri = $nickname.'@'.$a->get_hostname();

				if ($a->get_path())
					$uri .= '/'.$a->get_path();

				$uri = urlencode($uri);
			} else {
				$uri = App::get_baseurl().'/profile/'.$nickname;
			}

			$url = str_replace('{uri}', $uri, $url);
			goaway($url);
			// NOTREACHED
			// END $network != NETWORK_PHANTOM
		} else {
			notice(t("Remote subscription can't be done for your network. Please subscribe directly on your system.").EOL);
			return;
		}

	}	return;
}


function dfrn_request_content(App $a) {

	if (($a->argc != 2) || (! count($a->profile))) {
		return "";
	}


	// "Homecoming". Make sure we're logged in to this site as the correct user. Then offer a confirm button
	// to send us to the post section to record the introduction.

	if (x($_GET,'dfrn_url')) {

		if (! local_user()) {
			info( t("Please login to confirm introduction.") . EOL );
			/* setup the return URL to come back to this page if they use openid */
			$_SESSION['return_url'] = $a->query_string;
			return login();
		}

		// Edge case, but can easily happen in the wild. This person is authenticated,
		// but not as the person who needs to deal with this request.

		if ($a->user['nickname'] != $a->argv[1]) {
			notice( t("Incorrect identity currently logged in. Please login to <strong>this</strong> profile.") . EOL);
			return login();
		}

		$dfrn_url = notags(trim(hex2bin($_GET['dfrn_url'])));
		$aes_allow = (((x($_GET,'aes_allow')) && ($_GET['aes_allow'] == 1)) ? 1 : 0);
		$confirm_key = (x($_GET,'confirm_key') ? $_GET['confirm_key'] : "");

		// Checking fastlane for validity
		if (x($_SESSION, "fastlane") AND (normalise_link($_SESSION["fastlane"]) == normalise_link($dfrn_url))) {
			$_POST["dfrn_url"] = $dfrn_url;
			$_POST["confirm_key"] = $confirm_key;
			$_POST["localconfirm"] = 1;
			$_POST["hidden-contact"] = 0;
			$_POST["submit"] = t('Confirm');

			dfrn_request_post($a);

			killme();
			return; // NOTREACHED
		}

		$tpl = get_markup_template("dfrn_req_confirm.tpl");
		$o  = replace_macros($tpl,array(
			'$dfrn_url' => $dfrn_url,
			'$aes_allow' => (($aes_allow) ? '<input type="hidden" name="aes_allow" value="1" />' : "" ),
			'$hidethem' => t('Hide this contact'),
			'$hidechecked' => '',
			'$confirm_key' => $confirm_key,
			'$welcome' => sprintf( t('Welcome home %s.'), $a->user['username']),
			'$please' => sprintf( t('Please confirm your introduction/connection request to %s.'), $dfrn_url),
			'$submit' => t('Confirm'),
			'$uid' => $_SESSION['uid'],
			'$nickname' => $a->user['nickname'],
			'dfrn_rawurl' => $_GET['dfrn_url']
			));
		return $o;

	}
	elseif((x($_GET,'confirm_key')) && strlen($_GET['confirm_key'])) {

		// we are the requestee and it is now safe to send our user their introduction,
		// We could just unblock it, but first we have to jump through a few hoops to
		// send an email, or even to find out if we need to send an email.

		$intro = q("SELECT * FROM `intro` WHERE `hash` = '%s' LIMIT 1",
			dbesc($_GET['confirm_key'])
		);

		if (dbm::is_result($intro)) {

			$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `contact`.`id` = %d LIMIT 1",
				intval($intro[0]['contact-id'])
			);

			$auto_confirm = false;

			if (dbm::is_result($r)) {
				if(($r[0]['page-flags'] != PAGE_NORMAL) && ($r[0]['page-flags'] != PAGE_PRVGROUP))
					$auto_confirm = true;

				if(! $auto_confirm) {

					notification(array(
						'type'         => NOTIFY_INTRO,
						'notify_flags' => $r[0]['notify-flags'],
						'language'     => $r[0]['language'],
						'to_name'      => $r[0]['username'],
						'to_email'     => $r[0]['email'],
						'uid'          => $r[0]['uid'],
						'link'         => App::get_baseurl() . '/notifications/intros',
						'source_name'  => ((strlen(stripslashes($r[0]['name']))) ? stripslashes($r[0]['name']) : t('[Name Withheld]')),
						'source_link'  => $r[0]['url'],
						'source_photo' => $r[0]['photo'],
						'verb'         => ACTIVITY_REQ_FRIEND,
						'otype'        => 'intro'
					));
				}

				if($auto_confirm) {
					require_once('mod/dfrn_confirm.php');
					$handsfree = array(
						'uid'      => $r[0]['uid'],
						'node'     => $r[0]['nickname'],
						'dfrn_id'  => $r[0]['issued-id'],
						'intro_id' => $intro[0]['id'],
						'duplex'   => (($r[0]['page-flags'] == PAGE_FREELOVE) ? 1 : 0),
						'activity' => intval(get_pconfig($r[0]['uid'],'system','post_newfriend'))
					);
					dfrn_confirm_post($a,$handsfree);
				}

			}

			if(! $auto_confirm) {

				// If we are auto_confirming, this record will have already been nuked
				// in dfrn_confirm_post()

				$r = q("UPDATE `intro` SET `blocked` = 0 WHERE `hash` = '%s'",
					dbesc($_GET['confirm_key'])
				);
			}
		}

		killme();
		return; // NOTREACHED
	}
	else {

		/*
		 * Normal web request. Display our user's introduction form.
		 */

		if((get_config('system','block_public')) && (! local_user()) && (! remote_user())) {
			if(! get_config('system','local_block')) {
				notice( t('Public access denied.') . EOL);
				return;
			}
		}


		/*
		 * Try to auto-fill the profile address
		 */

		// At first look if an address was provided
		// Otherwise take the local address
		if (x($_GET,'addr') AND ($_GET['addr'] != "")) {
			$myaddr = hex2bin($_GET['addr']);
		} elseif (x($_GET,'address') AND ($_GET['address'] != "")) {
			$myaddr = $_GET['address'];
		} elseif (local_user()) {
			if (strlen($a->path)) {
				$myaddr = App::get_baseurl() . '/profile/' . $a->user['nickname'];
			} else {
				$myaddr = $a->user['nickname'] . '@' . substr(z_root(), strpos(z_root(),'://') + 3 );
			}
		} else {
			// last, try a zrl
			$myaddr = get_my_url();
		}

		$target_addr = $a->profile['nickname'] . '@' . substr(z_root(), strpos(z_root(),'://') + 3 );


		/*
		 *
		 * The auto_request form only has the profile address
		 * because nobody is going to read the comments and
		 * it doesn't matter if they know you or not.
		 *
		 */

		if ($a->profile['page-flags'] == PAGE_NORMAL) {
			$tpl = get_markup_template('dfrn_request.tpl');
		} else {
			$tpl = get_markup_template('auto_request.tpl');
		}

		$page_desc = t("Please enter your 'Identity Address' from one of the following supported communications networks:");

		// see if we are allowed to have NETWORK_MAIL2 contacts

		$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

		if (get_config('system','dfrn_only')) {
			$mail_disabled = 1;
		}

		if (! $mail_disabled) {
			$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d LIMIT 1",
				intval($a->profile['uid'])
			);
			if (! dbm::is_result($r)) {
				$mail_disabled = 1;
			}
		}

		// "coming soon" is disabled for now
		//$emailnet = (($mail_disabled) ? '' : t("<strike>Connect as an email follower</strike> \x28Coming soon\x29"));
		$emailnet = "";

		$invite_desc = sprintf(
			t('If you are not yet a member of the free social web, <a href="%s/siteinfo">follow this link to find a public Friendica site and join us today</a>.'),
			get_server()
		);

		$o = replace_macros($tpl,array(
			'$header' => t('Friend/Connection Request'),
			'$desc' => t('Examples: jojo@demo.friendica.com, http://demo.friendica.com/profile/jojo, testuser@identi.ca'),
			'$pls_answer' => t('Please answer the following:'),
			'$does_know_you' => array('knowyou', sprintf(t('Does %s know you?'),$a->profile['name']), false, '', array(t('No'),t('Yes'))),
			/*'$does_know' => sprintf( t('Does %s know you?'),$a->profile['name']),
			'$yes' => t('Yes'),
			'$no' => t('No'), */
			'$add_note' => t('Add a personal note:'),
			'$page_desc' => $page_desc,
			'$friendica' => t('Friendica'),
			'$statusnet' => t('StatusNet/Federated Social Web'),
			'$diaspora' => t('Diaspora'),
			'$diasnote' => sprintf (t(' - please do not use this form.  Instead, enter %s into your Diaspora search bar.'),$target_addr),
			'$your_address' => t('Your Identity Address:'),
			'$invite_desc' => $invite_desc,
			'$emailnet' => $emailnet,
			'$submit' => t('Submit Request'),
			'$cancel' => t('Cancel'),
			'$nickname' => $a->argv[1],
			'$name' => $a->profile['name'],
			'$myaddr' => $myaddr
		));
		return $o;
	}

	return; // Somebody is fishing.
}

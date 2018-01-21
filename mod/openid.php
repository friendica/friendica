<?php

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBM;

require_once('library/openid.php');

function openid_content(App $a) {

	$noid = Config::get('system','no_openid');
	if($noid)
		goaway(System::baseUrl());

	logger('mod_openid ' . print_r($_REQUEST,true), LOGGER_DATA);

	if((x($_GET,'openid_mode')) && (x($_SESSION,'openid'))) {

		$openid = new LightOpenID;

		if($openid->validate()) {

			$authid = $_REQUEST['openid_identity'];

			if(! strlen($authid)) {
				logger( t('OpenID protocol error. No ID returned.') . EOL);
				goaway(System::baseUrl());
			}

			// NOTE: we search both for normalised and non-normalised form of $authid
			//       because the normalization step was removed from setting
			//       mod/settings.php in 8367cad so it might have left mixed
			//       records in the user table
			//
			$r = q("SELECT *
				FROM `user`
				WHERE ( `openid` = '%s' OR `openid` = '%s' )
				AND `blocked` = 0 AND `account_expired` = 0
				AND `account_removed` = 0 AND `verified` = 1
				LIMIT 1",
				dbesc($authid), dbesc(normalise_openid($authid))
			);

			if (DBM::isResult($r)) {

				// successful OpenID login

				unset($_SESSION['openid']);

				require_once('include/security.php');
				authenticate_success($r[0],true,true);

				// just in case there was no return url set
				// and we fell through

				goaway(System::baseUrl());
			}

			// Successful OpenID login - but we can't match it to an existing account.
			// New registration?

			if ($a->config['register_policy'] == REGISTER_CLOSED) {
				notice( t('Account not found and OpenID registration is not permitted on this site.') . EOL);
				goaway(System::baseUrl());
			}

			unset($_SESSION['register']);
			$args = '';
			$attr = $openid->getAttributes();
			if (is_array($attr) && count($attr)) {
				foreach ($attr as $k => $v) {
					if ($k === 'namePerson/friendly') {
						$nick = notags(trim($v));
					}
					if($k === 'namePerson/first') {
						$first = notags(trim($v));
					}
					if($k === 'namePerson') {
						$args .= '&username=' . urlencode(notags(trim($v)));
					}
					if ($k === 'contact/email') {
						$args .= '&email=' . urlencode(notags(trim($v)));
					}
					if ($k === 'media/image/aspect11') {
						$photosq = bin2hex(trim($v));
					}
					if ($k === 'media/image/default') {
						$photo = bin2hex(trim($v));
					}
				}
			}
			if ($nick) {
				$args .= '&nickname=' . urlencode($nick);
			}
			elseif ($first) {
				$args .= '&nickname=' . urlencode($first);
			}

			if ($photosq) {
				$args .= '&photo=' . urlencode($photosq);
			}
			elseif ($photo) {
				$args .= '&photo=' . urlencode($photo);
			}

			$args .= '&openid_url=' . urlencode(notags(trim($authid)));

			goaway(System::baseUrl() . '/register?' . $args);

			// NOTREACHED
		}
	}
	notice( t('Login failed.') . EOL);
	goaway(System::baseUrl());
	// NOTREACHED
}

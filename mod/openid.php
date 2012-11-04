<?php


require_once('library/openid.php');


function openid_content(&$a) {

	$noid = get_config('system','no_openid');
	if($noid)
		goaway(z_root());

	logger('mod_openid ' . print_r($_REQUEST,true), LOGGER_DATA);

	if((x($_GET,'openid_mode')) && (x($_SESSION,'openid'))) {

		$openid = new LightOpenID;

		if($openid->validate()) {

			$authid = normalise_openid($_REQUEST['openid_identity']);

			if(! strlen($authid)) {
				logger( t('OpenID protocol error. No ID returned.') . EOL);
				goaway(z_root());
			}

			$r = q("SELECT `user`.*, `user`.`pubkey` as `upubkey`, `user`.`prvkey` as `uprvkey` 
				FROM `user` WHERE `openid` = '%s' AND `blocked` = 0 
				AND `account_expired` = 0 AND `account_removed` = 0 AND `verified` = 1 LIMIT 1",
				dbesc($authid)
			);

			if($r && count($r)) {

				// successful OpenID login

				unset($_SESSION['openid']);

				require_once('include/security.php');
				authenticate_success($r[0],true,true);

				// just in case there was no return url set 
				// and we fell through

				goaway(z_root());
			}

			// Successful OpenID login - but we can't match it to an existing account.
			// New registration?

			if($a->config['register_policy'] == REGISTER_CLOSED) {
				notice( t('Account not found and OpenID registration is not permitted on this site.') . EOL);
				goaway(z_root());
			}

			unset($_SESSION['register']);
			$args = '';
			$attr = $openid->getAttributes();
			if(is_array($attr) && count($attr)) {
				foreach($attr as $k => $v) {
					if($k === 'namePerson/friendly')
						$nick = notags(trim($v));
					if($k === 'namePerson/first')
						$first = notags(trim($v));
					if($k === 'namePerson')
						$args .= '&username=' . notags(trim($v));
					if($k === 'contact/email')
						$args .= '&email=' . notags(trim($v));
					if($k === 'media/image/aspect11')
						$photosq = bin2hex(trim($v));
					if($k === 'media/image/default')
						$photo = bin2hex(trim($v));
				}
			}
			if($nick)
				$args .= '&nickname=' . $nick;
			elseif($first)
				$args .= '&nickname=' . $first;

			if($photosq)
				$args .= '&photo=' . $photosq;
			elseif($photo)
				$args .= '&photo=' . $photo;

			$args .= '&openid_url=' . notags(trim($authid));

			goaway($a->get_baseurl() . '/register' . $args);

			// NOTREACHED
		}
	}
	notice( t('Login failed.') . EOL);
	goaway(z_root());
	// NOTREACHED
}

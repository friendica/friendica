<?php
/**
 * @file mod/openid.php
 */

use Friendica\App;
use Friendica\Core\Authentication;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

function openid_content(App $a) {

	$noid = Config::get('system','no_openid');
	if($noid)
		$a->internalRedirect();

	Logger::log('mod_openid ' . print_r($_REQUEST,true), Logger::DATA);

	if(!empty($_GET['openid_mode']) && !empty($_SESSION['openid'])) {

		$openid = new LightOpenID($a->getHostName());

		if($openid->validate()) {

			$authid = $_REQUEST['openid_identity'];

			if(! strlen($authid)) {
				Logger::log(L10n::t('OpenID protocol error. No ID returned.') . EOL);
				$a->internalRedirect();
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
				DBA::escape($authid), DBA::escape(Strings::normaliseOpenID($authid))
			);

			if (DBA::isResult($r)) {

				// successful OpenID login

				unset($_SESSION['openid']);

				Authentication::setAuthenticatedSessionForUser($r[0],true,true);

				// just in case there was no return url set
				// and we fell through

				$a->internalRedirect();
			}

			// Successful OpenID login - but we can't match it to an existing account.
			// New registration?

			if (intval(Config::get('config', 'register_policy')) === REGISTER_CLOSED) {
				notice(L10n::t('Account not found and OpenID registration is not permitted on this site.') . EOL);
				$a->internalRedirect();
			}

			unset($_SESSION['register']);
			$args = '';
			$attr = $openid->getAttributes();
			if (is_array($attr) && count($attr)) {
				foreach ($attr as $k => $v) {
					if ($k === 'namePerson/friendly') {
						$nick = Strings::escapeTags(trim($v));
					}
					if($k === 'namePerson/first') {
						$first = Strings::escapeTags(trim($v));
					}
					if($k === 'namePerson') {
						$args .= '&username=' . urlencode(Strings::escapeTags(trim($v)));
					}
					if ($k === 'contact/email') {
						$args .= '&email=' . urlencode(Strings::escapeTags(trim($v)));
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

			$args .= '&openid_url=' . urlencode(Strings::escapeTags(trim($authid)));

			$a->internalRedirect('register?' . $args);

			// NOTREACHED
		}
	}
	notice(L10n::t('Login failed.') . EOL);
	$a->internalRedirect();
	// NOTREACHED
}

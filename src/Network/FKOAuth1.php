<?php
/**
 * @file src/Network/FKOAuth1.php
 */
namespace Friendica\Network;

use Friendica\Core\Addon;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use OAuthServer;
use OAuthSignatureMethod_HMAC_SHA1;
use OAuthSignatureMethod_PLAINTEXT;

/**
 * @brief OAuth protocol
 */
class FKOAuth1 extends OAuthServer
{
	/**
	 * @brief Constructor
	 */
	public function __construct()
	{
		parent::__construct(new FKOAuthDataStore());
		$this->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
		$this->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
	}

	/**
	 * @param string $uid user id
	 * @return void
	 */
	public function loginUser($uid)
	{
		logger("FKOAuth1::loginUser $uid");
		$a = get_app();
		$record = DBA::selectFirst('user', [], ['uid' => $uid, 'blocked' => 0, 'account_expired' => 0, 'account_removed' => 0, 'verified' => 1]);

		if (!DBA::isResult($record)) {
			logger('FKOAuth1::loginUser failure: ' . print_r($_SERVER, true), LOGGER_DEBUG);
			header('HTTP/1.0 401 Unauthorized');
			die('This api requires login');
		}
		$_SESSION['uid'] = $record['uid'];
		$_SESSION['theme'] = $record['theme'];
		$_SESSION['mobile-theme'] = PConfig::get($record['uid'], 'system', 'mobile_theme');
		$_SESSION['authenticated'] = 1;
		$_SESSION['page_flags'] = $record['page-flags'];
		$_SESSION['my_url'] = System::baseUrl() . '/profile/' . $record['nickname'];
		$_SESSION['addr'] = $_SERVER['REMOTE_ADDR'];
		$_SESSION["allow_api"] = true;

		$a->user = $record;

		if (strlen($a->user['timezone'])) {
			date_default_timezone_set($a->user['timezone']);
			$a->timezone = $a->user['timezone'];
		}

		$contact = DBA::selectFirst('contact', [], ['uid' => $_SESSION['uid'], 'self' => 1]);
		if (DBA::isResult($contact)) {
			$a->contact = $contact;
			$a->cid = $contact['id'];
			$_SESSION['cid'] = $a->cid;
		}

		DBA::update('user', ['login_date' => DateTimeFormat::utcNow()], ['uid' => $_SESSION['uid']]);

		Addon::callHooks('logged_in', $a->user);
	}
}

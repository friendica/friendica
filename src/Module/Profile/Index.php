<?php

namespace Friendica\Module\Profile;

use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Profile as ProfileModel;
use Friendica\Model\User;
use Friendica\Module\Login;
use \Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;

require_once 'boot.php';

class Index extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (ActivityPub::isRequest()) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $parameters['nickname']]);
			if (DBA::isResult($user)) {
				// The function returns an empty array when the account is removed, expired or blocked
				$data = ActivityPub\Transmitter::getProfile($user['uid']);
				if (!empty($data)) {
					System::jsonExit($data, 'application/activity+json');
				}
			}

			if (DBA::exists('userd', ['username' => self::$which])) {
				// Known deleted user
				$data = ActivityPub\Transmitter::getDeletedUser(self::$which);

				System::jsonError(410, $data);
			} else {
				// Any other case (unknown, blocked, nverified, expired, no profile, no self contact)
				System::jsonError(404, []);
			}
		}
	}

	public static function content(array $parameters = [])
	{
		$a = self::getApp();

		ProfileModel::load($a, $parameters['nickname']);

		$remote_contact_id = Session::getRemoteContactID($a->profile['uid']);

		if (Config::get('system', 'block_public') && !local_user() && !$remote_contact_id) {
			return Login::form();
		}

		$a->page['htmlhead'] .= "\n";

		$blocked   = !local_user() && !$remote_contact_id && Config::get('system', 'block_public');
		$userblock = !local_user() && !$remote_contact_id && $a->profile['hidewall'];

		if (!empty($a->profile['page-flags']) && $a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY) {
			$a->page['htmlhead'] .= '<meta name="friendica.community" content="true" />' . "\n";
		}

		if (!empty($a->profile['openidserver'])) {
			$a->page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\n";
		}

		if (!empty($a->profile['openid'])) {
			$delegate = strstr($a->profile['openid'], '://') ? $a->profile['openid'] : 'https://' . $a->profile['openid'];
			$a->page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\n";
		}

		// site block
		if (!$blocked && !$userblock) {
			$keywords = str_replace(['#', ',', ' ', ',,'], ['', ' ', ',', ','], $a->profile['pub_keywords'] ?? '');
			if (strlen($keywords)) {
				$a->page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\n";
			}
		}

		$a->page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . ($a->profile['net-publish'] ? 'true' : 'false') . '" />' . "\n";

		if (!$a->profile['net-publish'] || $a->profile['hidewall']) {
			$a->page['htmlhead'] .= '<meta content="noindex, noarchive" name="robots" />' . "\n";
		}

		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/dfrn_poll/' . $parameters['nickname'] . '" title="DFRN: ' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/" title="' . L10n::t('%s\'s posts', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/comments" title="' . L10n::t('%s\'s comments', $a->profile['username']) . '"/>' . "\n";
		$a->page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . System::baseUrl() . '/feed/' . $parameters['nickname'] . '/activity" title="' . L10n::t('%s\'s timeline', $a->profile['username']) . '"/>' . "\n";
		$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $a->getHostName() . ($a->getURLPath() ? '/' . $a->getURLPath() : ''));
		$a->page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . System::baseUrl() . '/xrd/?uri=' . $uri . '" />' . "\n";
		header('Link: <' . System::baseUrl() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		$dfrn_pages = ['request', 'confirm', 'notify', 'poll'];
		foreach ($dfrn_pages as $dfrn) {
			$a->page['htmlhead'] .= '<link rel="dfrn-' . $dfrn . '" href="' . System::baseUrl() . '/dfrn_' . $dfrn . '/' . $parameters['nickname'] . '" />' . "\n";
		}
		$a->page['htmlhead'] .= '<link rel="dfrn-poco" href="' . System::baseUrl() . '/poco/' . $parameters['nickname'] . '" />' . "\n";

		$o = '';

		Nav::setSelected('home');

		$is_owner = local_user() == $a->profile['uid'];

		if (!empty($a->profile['hidewall']) && !$is_owner && !$remote_contact_id) {
			notice(L10n::t('Access to this profile has been restricted.'));
			return '';
		}

		$o .= ProfileModel::getTabs($a, 'profile', $is_owner, $a->profile['nickname']);

		$o .= ProfileModel::getAdvanced($a);

		Hook::callAll('profile_advanced', $o);

		return $o;
	}
}

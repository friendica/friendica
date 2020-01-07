<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Session;
use Friendica\Registry\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Registry\App;
use Friendica\Registry\Core;

/**
 * Loads a profile for the HoverCard view
 */
class HoverCard extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if ((local_user()) && ($parameters['action'] ?? '') === 'view') {
			// A logged in user views a profile of a user
			$nickname = $a->user['nickname'];
			$profile  = $parameters['profile'];
		} elseif (empty($parameters['action'])) {
			// Show the profile hovercard
			$nickname = $parameters['profile'];
			$profile  = 0;
		} else {
			throw new NotFoundException(Core::l10n()->t('No profile'));
		}

		Profile::load($a, $nickname, $profile);

		$page = App::page();

		if (!empty($a->profile['page-flags']) && ($a->profile['page-flags'] == User::PAGE_FLAGS_COMMUNITY)) {
			$page['htmlhead'] .= '<meta name="friendica.community" content="true" />';
		}
		if (!empty($a->profile['openidserver'])) {
			$page['htmlhead'] .= '<link rel="openid.server" href="' . $a->profile['openidserver'] . '" />' . "\r\n";
		}
		if (!empty($a->profile['openid'])) {
			$delegate         = ((strstr($a->profile['openid'], '://')) ? $a->profile['openid'] : 'http://' . $a->profile['openid']);
			$page['htmlhead'] .= '<link rel="openid.delegate" href="' . $delegate . '" />' . "\r\n";
		}

		// check if blocked
		if (Core::config()->get('system', 'block_public') && !Session::isAuthenticated()) {
			$keywords = $a->profile['pub_keywords'] ?? '';
			$keywords = str_replace([',', ' ', ',,'], [' ', ',', ','], $keywords);
			if (strlen($keywords)) {
				$page['htmlhead'] .= '<meta name="keywords" content="' . $keywords . '" />' . "\r\n";
			}
		}

		$baseUrl = App::baseUrl();

		$uri = urlencode('acct:' . $a->profile['nickname'] . '@' . $baseUrl->getHostname() . ($baseUrl->getUrlPath() ? '/' . $baseUrl->getUrlPath() : ''));

		$page['htmlhead'] .= '<meta name="dfrn-global-visibility" content="' . (($a->profile['net-publish']) ? 'true' : 'false') . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="alternate" type="application/atom+xml" href="' . $baseUrl->get() . '/dfrn_poll/' . $nickname . '" />' . "\r\n";
		$page['htmlhead'] .= '<link rel="lrdd" type="application/xrd+xml" href="' . $baseUrl->get() . '/xrd/?uri=' . $uri . '" />' . "\r\n";
		header('Link: <' . $baseUrl->get() . '/xrd/?uri=' . $uri . '>; rel="lrdd"; type="application/xrd+xml"', false);

		foreach (['request', 'confirm', 'notify', 'poll'] as $dfrn) {
			$page['htmlhead'] .= "<link rel=\"dfrn-{$dfrn}\" href=\"" . $baseUrl->get() . "/dfrn_{$dfrn}/{$nickname}\" />\r\n";
		}
	}
}

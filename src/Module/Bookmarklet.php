<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\ACL;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Creates a bookmarklet
 * Shows either a editor browser or adds the given bookmarklet to the current user
 */
class Bookmarklet extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$_GET['mode'] = 'minimal';

		$app = DI::app();
		$config = $app->getConfig();

		if (!local_user()) {
			$output = '<h2>' . L10n::t('Login') . '</h2>';
			$output .= Login::form($app->query_string, intval($config->get('config', 'register_policy')) === Register::CLOSED ? false : true);
			return $output;
		}

		$referer = Strings::normaliseLink($_SERVER['HTTP_REFERER'] ?? '');
		$page = Strings::normaliseLink($app->getBaseURL() . "/bookmarklet");

		if (!strstr($referer, $page)) {
			if (empty($_REQUEST["url"])) {
				throw new HTTPException\BadRequestException(L10n::t('This page is missing a url parameter.'));
			}

			$content = add_page_info($_REQUEST["url"]);

			$x = [
				'is_owner'         => true,
				'allow_location'   => $app->user['allow_location'],
				'default_location' => $app->user['default-location'],
				'nickname'         => $app->user['nickname'],
				'lockstate'        => ((is_array($app->user) && ((strlen($app->user['allow_cid'])) || (strlen($app->user['allow_gid'])) || (strlen($app->user['deny_cid'])) || (strlen($app->user['deny_gid'])))) ? 'lock' : 'unlock'),
				'default_perms'    => ACL::getDefaultUserPermissions($app->user),
				'acl'              => ACL::getFullSelectorHTML($app->page, $app->user, true),
				'bang'             => '',
				'visitor'          => 'block',
				'profile_uid'      => local_user(),
				'title'            => trim($_REQUEST['title'] ?? '', '*'),
				'content'          => $content
			];
			$output = status_editor($app, $x, 0, false);
			$output .= "<script>window.resizeTo(800,550);</script>";
		} else {
			$output = '<h2>' . L10n::t('The post was created') . '</h2>';
			$output .= "<script>window.close()</script>";
		}

		return $output;
	}
}

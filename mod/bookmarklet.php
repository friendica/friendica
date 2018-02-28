<?php
/**
 * @file mod/bookmarklet.php
 */

use Friendica\App;
use Friendica\Core\Acl;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Login;

require_once 'include/conversation.php';
require_once 'include/items.php';

function bookmarklet_init()
{
	$_GET["mode"] = "minimal";
}

function bookmarklet_content(App $a)
{
	if (!local_user()) {
		$o = '<h2>' . L10n::t('Login') . '</h2>';
		$o .= Login::form($a->query_string, $a->config['register_policy'] == REGISTER_CLOSED ? false : true);
		return $o;
	}

	$referer = normalise_link($_SERVER["HTTP_REFERER"]);
	$page = normalise_link(System::baseUrl() . "/bookmarklet");

	if (!strstr($referer, $page)) {
		$content = add_page_info($_REQUEST["url"]);

		$x = [
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid'])))) ? 'lock' : 'unlock'),
			'default_perms' => Acl::getDefaultUserPermissions($a->user),
			'acl' => Acl::getFullSelectorHTML($a->user, true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'title' => trim($_REQUEST["title"], "*"),
			'content' => $content
		];
		$o = status_editor($a, $x, 0, false);
		$o .= "<script>window.resizeTo(800,550);</script>";
	} else {
		$o = '<h2>' . L10n::t('The post was created') . '</h2>';
		$o .= "<script>window.close()</script>";
	}

	return $o;
}

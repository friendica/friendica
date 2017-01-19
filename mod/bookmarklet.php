<?php

require_once('include/conversation.php');
require_once('include/items.php');

function bookmarklet_init(App $a) {
	$_GET["mode"] = "minimal";
}

function bookmarklet_content(App $a) {
	if (!local_user()) {
		$o = '<h2>'.t('Login').'</h2>';
		$o .= login(($a->config['register_policy'] == REGISTER_CLOSED) ? false : true);
		return $o;
	}

	$referer = normalise_link($_SERVER["HTTP_REFERER"]);
	$page = normalise_link(App::get_baseurl()."/bookmarklet");

	if (!strstr($referer, $page)) {
		$content = add_page_info($_REQUEST["url"]);

		$x = array(
			'is_owner' => true,
			'allow_location' => $a->user['allow_location'],
			'default_location' => $a->user['default-location'],
			'nickname' => $a->user['nickname'],
			'lockstate' => ((is_array($a->user) && ((strlen($a->user['allow_cid'])) || (strlen($a->user['allow_gid'])) || (strlen($a->user['deny_cid'])) || (strlen($a->user['deny_gid'])))) ? 'lock' : 'unlock'),
			'default_perms' => get_acl_permissions($a->user),
			'acl' => populate_acl($a->user,true),
			'bang' => '',
			'visitor' => 'block',
			'profile_uid' => local_user(),
			'acl_data' => construct_acl_data($a, $a->user), // For non-Javascript ACL selector
			'title' => trim($_REQUEST["title"], "*"),
			'content' => $content
		);
		$o = status_editor($a,$x, 0, false);
		$o .= "<script>window.resizeTo(800,550);</script>";
	} else {
		$o = '<h2>'.t('The post was created').'</h2>';
		$o .= "<script>window.close()</script>";
	}

	return $o;
}

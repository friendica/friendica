<?php
/**
 * @file mod/oexchange.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Login;
use Friendica\Util\Network;

function oexchange_init(App $a) {

	if (($a->argc > 1) && ($a->argv[1] === 'xrd')) {
		$tpl = get_markup_template('oexchange_xrd.tpl');

		$o = replace_macros($tpl, ['$base' => System::baseUrl()]);
		echo $o;
		killme();
	}
}

function oexchange_content(App $a) {

	if (! local_user()) {
		$o = Login::form();
		return $o;
	}

	if (($a->argc > 1) && $a->argv[1] === 'done') {
		info(L10n::t('Post successful.') . EOL);
		return;
	}

	$url = (((x($_REQUEST,'url')) && strlen($_REQUEST['url']))
		? urlencode(notags(trim($_REQUEST['url']))) : '');
	$title = (((x($_REQUEST,'title')) && strlen($_REQUEST['title']))
		? '&title=' . urlencode(notags(trim($_REQUEST['title']))) : '');
	$description = (((x($_REQUEST,'description')) && strlen($_REQUEST['description']))
		? '&description=' . urlencode(notags(trim($_REQUEST['description']))) : '');
	$tags = (((x($_REQUEST,'tags')) && strlen($_REQUEST['tags']))
		? '&tags=' . urlencode(notags(trim($_REQUEST['tags']))) : '');

	$s = Network::fetchUrl(System::baseUrl() . '/parse_url?f=&url=' . $url . $title . $description . $tags);

	if (! strlen($s)) {
		return;
	}

	$post = [];

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done' ;
	$post['body'] = Friendica\Content\Text\HTML::toBBCode($s);
	$post['type'] = 'wall';

	$_REQUEST = $post;
	require_once('mod/item.php');
	item_post($a);
}

<?php


function oexchange_init(&$a) {

	if(($a->argc > 1) && ($a->argv[1] === 'xrd')) {
		$tpl = get_markup_template('oexchange_xrd.tpl');

		$o = replace_macros($tpl, array('$base' => App::get_baseurl()));
		echo $o;
		killme();
	}


}

function oexchange_content(&$a) {

	if (! local_user()) {
		$o = login(false);
		return $o;
	}

	if (($a->argc > 1) && $a->argv[1] === 'done') {
		info( t('Post successful.') . EOL);
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

	$s = fetch_url(App::get_baseurl() . '/parse_url?f=&url=' . $url . $title . $description . $tags);

	if(! strlen($s))
		return;

	require_once('include/html2bbcode.php');

	$post = array();

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done' ;
	$post['body'] = html2bbcode($s);
	$post['type'] = 'wall';

	$_REQUEST = $post;
	require_once('mod/item.php');
	item_post($a);

}



<?php
/**
 * @file mod/oexchange.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\Security\Login;
use Friendica\Registry\App as A;
use Friendica\Util\Network;
use Friendica\Util\Strings;

function oexchange_init(App $a) {

	if (($a->argc > 1) && ($a->argv[1] === 'xrd')) {
		$tpl = Renderer::getMarkupTemplate('oexchange_xrd.tpl');

		$o = Renderer::replaceMacros($tpl, ['$base' => A::baseUrl()]);
		echo $o;
		exit();
	}
}

function oexchange_content(App $a) {

	if (!local_user()) {
		$o = Login::form();
		return $o;
	}

	if (($a->argc > 1) && $a->argv[1] === 'done') {
		info(L10n::t('Post successful.') . EOL);
		return;
	}

	$url = ((!empty($_REQUEST['url']))
		? urlencode(Strings::escapeTags(trim($_REQUEST['url']))) : '');
	$title = ((!empty($_REQUEST['title']))
		? '&title=' . urlencode(Strings::escapeTags(trim($_REQUEST['title']))) : '');
	$description = ((!empty($_REQUEST['description']))
		? '&description=' . urlencode(Strings::escapeTags(trim($_REQUEST['description']))) : '');
	$tags = ((!empty($_REQUEST['tags']))
		? '&tags=' . urlencode(Strings::escapeTags(trim($_REQUEST['tags']))) : '');

	$s = Network::fetchUrl(A::baseUrl() . '/parse_url?f=&url=' . $url . $title . $description . $tags);

	if (!strlen($s)) {
		return;
	}

	$post = [];

	$post['profile_uid'] = local_user();
	$post['return'] = '/oexchange/done';
	$post['body'] = Friendica\Content\Text\HTML::toBBCode($s);

	$_REQUEST = $post;
	require_once('mod/item.php');
	item_post($a);
}

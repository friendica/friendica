<?php
/**
 * @file mod/tagrm.php
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Item;

function tagrm_post(App $a) {

	if (!local_user()) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
	}

	if ((x($_POST,'submit')) && ($_POST['submit'] === L10n::t('Cancel'))) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
	}

	$tag =  ((x($_POST,'tag'))  ? hex2bin(notags(trim($_POST['tag']))) : '');
	$item = ((x($_POST,'item')) ? intval($_POST['item'])               : 0 );

	$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval(local_user())
	);

	if (!DBM::is_result($r)) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
	}

	$arr = explode(',', $r[0]['tag']);
	for ($x = 0; $x < count($arr); $x ++) {
		if ($arr[$x] === $tag) {
			unset($arr[$x]);
			break;
		}
	}

	$tag_str = implode(',',$arr);

	Item::update(['tag' => $tag_str], ['id' => $item]);

	info(L10n::t('Tag removed') . EOL );
	goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);

	// NOTREACHED
}



function tagrm_content(App $a) {

	$o = '';

	if (!local_user()) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
		// NOTREACHED
	}

	$item = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if (!$item) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
		// NOTREACHED
	}

	$r = q("SELECT * FROM `item` WHERE `id` = %d AND `uid` = %d LIMIT 1",
		intval($item),
		intval(local_user())
	);

	if (!DBM::is_result($r)) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
	}

	$arr = explode(',', $r[0]['tag']);

	if (!count($arr)) {
		goaway(System::baseUrl() . '/' . $_SESSION['photo_return']);
	}

	$o .= '<h3>' . L10n::t('Remove Item Tag') . '</h3>';

	$o .= '<p id="tag-remove-desc">' . L10n::t('Select a tag to remove: ') . '</p>';

	$o .= '<form id="tagrm" action="tagrm" method="post" >';
	$o .= '<input type="hidden" name="item" value="' . $item . '" />';
	$o .= '<ul>';

	foreach ($arr as $x) {
		$o .= '<li><input type="checkbox" name="tag" value="' . bin2hex($x) . '" >' . BBCode::convert($x) . '</input></li>';
	}

	$o .= '</ul>';
	$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . L10n::t('Remove') .'" />';
	$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . L10n::t('Cancel') .'" />';
	$o .= '</form>';

	return $o;

}

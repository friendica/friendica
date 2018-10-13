<?php
/**
 * @file mod/tagrm.php
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function tagrm_post(App $a)
{
	if (!local_user()) {
		$a->redirect($_SESSION['photo_return']);
	}

	if (x($_POST,'submit') && ($_POST['submit'] === L10n::t('Cancel'))) {
		$a->redirect($_SESSION['photo_return']);
	}
	
	$tags = [];
	if (defaults($_POST, 'tag', '')){
		foreach ($_POST['tag'] as $t){
			array_push($tags, hex2bin(notags(trim($t))));
		}
	}

	$item_id = defaults($_POST,'item', 0);
	update_tags($item_id, $tags);

	info(L10n::t('Tag(s) removed') . EOL );

	goaway('/' . $_SESSION['photo_return']);

	// NOTREACHED
}

function update_tags($item_id, $tags){
	if (empty($item_id) || empty($tags)){
		goaway('/' . $_SESSION['photo_return']);
	}

	$item = Item::selectFirst(['tag'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
<<<<<<< HEAD
		goaway('/' . $_SESSION['photo_return']);
=======
		$a->redirect($_SESSION['photo_return']);
>>>>>>> Renamed System::redirect() to $a->redirect()
	}

	$arr = explode(',', $item['tag']);

	foreach ($tags as $t) {
		foreach ($arr as $i => $x) {
			if (strcmp($x, $t) == 0) {
				unset($arr[$i]);
				break;
			}
		}
	}

	$tag_str = implode(',',$arr);
	if(empty($tag_str)){
		$tag_str = '';
	}

	Item::update(['tag' => $tag_str], ['id' => $item_id]);

	info(L10n::t('Tag(s) removed') . EOL );
	$a->redirect($_SESSION['photo_return']);

	// NOTREACHED
}

function tagrm_content(App $a)
{
	$o = '';

	if (!local_user()) {
		$a->redirect($_SESSION['photo_return']);
		// NOTREACHED
	}

	if ($a->argc == 3){
		update_tags($a->argv[1], [hex2bin(notags(trim($a->argv[2])))]);
		goaway('/' . $_SESSION['photo_return']);
	}

	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);
	if (!$item_id) {
		$a->redirect($_SESSION['photo_return']);
		// NOTREACHED
	}

	$item = Item::selectFirst(['tag'], ['id' => $item_id, 'uid' => local_user()]);
	if (!DBA::isResult($item)) {
		$a->redirect($_SESSION['photo_return']);
	}

	$arr = explode(',', $item['tag']);

	if (empty($item['tag'])) {
		$a->redirect($_SESSION['photo_return']);
	}

	$o .= '<h3>' . L10n::t('Remove Item Tag') . '</h3>';

	$o .= '<p id="tag-remove-desc">' . L10n::t('Select a tag to remove: ') . '</p>';

	$o .= '<form id="tagrm" action="tagrm" method="post" >';
	$o .= '<input type="hidden" name="item" value="' . $item_id . '" />';
	$o .= '<ul>';

	foreach ($arr as $x) {
		$o .= '<li><input type="checkbox" name="tag[]" value="' . bin2hex($x) . '" >' . BBCode::convert($x) . '</input></li>';
	}

	$o .= '</ul>';
	$o .= '<input id="tagrm-submit" type="submit" name="submit" value="' . L10n::t('Remove') .'" />';
	$o .= '<input id="tagrm-cancel" type="submit" name="submit" value="' . L10n::t('Cancel') .'" />';
	$o .= '</form>';

	return $o;
}

<?php
/**
 * @file mod/filer.php
 */
use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;

require_once 'include/items.php';

function filer_content(App $a)
{
	if (! local_user()) {
		killme();
	}

	$term = unxmlify(trim(defaults($_GET, 'term', '')));
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	logger('filer: tag ' . $term . ' item ' . $item_id);

	if ($item_id && strlen($term)) {
		// file item
		file_tag_save_file(local_user(), $item_id, $term);
	} else {
		// return filer dialog
		$filetags = PConfig::get(local_user(), 'system', 'filetags');
		$filetags = file_tag_file_to_list($filetags, 'file');
		$filetags = explode(",", $filetags);

		$tpl = get_markup_template("filer_dialog.tpl");
		$o = Text::replaceMacros($tpl, [
			'$field' => ['term', L10n::t("Save to Folder:"), '', '', $filetags, L10n::t('- select -')],
			'$submit' => L10n::t('Save'),
		]);

		echo $o;
	}
	killme();
}

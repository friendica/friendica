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

	$term = Text::unxmlify(trim(defaults($_GET, 'term', '')));
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	Text::logger('filer: tag ' . $term . ' item ' . $item_id);

	if ($item_id && strlen($term)) {
		// file item
		Text::fileTagSaveFile(local_user(), $item_id, $term);
	} else {
		// return filer dialog
		$filetags = PConfig::get(local_user(), 'system', 'filetags');
		$filetags = Text::fileTagFileToList($filetags, 'file');
		$filetags = explode(",", $filetags);

		$tpl = Text::getMarkupTemplate("filer_dialog.tpl");
		$o = Text::replaceMacros($tpl, [
			'$field' => ['term', L10n::t("Save to Folder:"), '', '', $filetags, L10n::t('- select -')],
			'$submit' => L10n::t('Save'),
		]);

		echo $o;
	}
	killme();
}

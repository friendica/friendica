<?php
/**
 * @file mod/filer.php
 */
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Model\FileTag;
use Friendica\Util\XML;

require_once 'include/items.php';

function filer_content(App $a)
{
	if (!Session::user()->isLocal()) {
		killme();
	}

	$term = XML::unescape(trim(defaults($_GET, 'term', '')));
	$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

	Logger::log('filer: tag ' . $term . ' item ' . $item_id);

	if ($item_id && strlen($term)) {
		// file item
		FileTag::saveFile(local_user(), $item_id, $term);
	} else {
		// return filer dialog
		$filetags = PConfig::get(local_user(), 'system', 'filetags');
		$filetags = FileTag::fileToList($filetags, 'file');
		$filetags = explode(",", $filetags);

		$tpl = Renderer::getMarkupTemplate("filer_dialog.tpl");
		$o = Renderer::replaceMacros($tpl, [
			'$field' => ['term', L10n::t("Save to Folder:"), '', '', $filetags, L10n::t('- select -')],
			'$submit' => L10n::t('Save'),
		]);

		echo $o;
	}
	killme();
}

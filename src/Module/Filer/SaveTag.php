<?php

namespace Friendica\Module\Filer;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Util\XML;

/**
 * Shows a dialog for adding tags to a file
 */
class SaveTag extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			info(L10n::t('You must be logged in to use this module'));
			DI::app()->internalRedirect();
		}
	}

	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();
		$logger = DI::logger();

		$term = XML::unescape(trim($_GET['term'] ?? ''));
		// @TODO: Replace with parameter from router
		$item_id = (($a->argc > 1) ? intval($a->argv[1]) : 0);

		$logger->info('filer', ['tag' => $term, 'item' => $item_id]);

		if ($item_id && strlen($term)) {
			// file item
			Model\FileTag::saveFile(local_user(), $item_id, $term);
			info(L10n::t('Filetag %s saved to item', $term));
		}

		// return filer dialog
		$filetags = PConfig::get(local_user(), 'system', 'filetags', '');
		$filetags = Model\FileTag::fileToArray($filetags);

		$tpl = Renderer::getMarkupTemplate("filer_dialog.tpl");
		echo Renderer::replaceMacros($tpl, [
			'$field' => ['term', L10n::t("Save to Folder:"), '', '', $filetags, L10n::t('- select -')],
			'$submit' => L10n::t('Save'),
		]);

		exit;
	}
}

<?php

namespace Friendica\Module\Filer;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\FileTag;
use Friendica\Network\HTTPException;
use Friendica\Util\XML;

/**
 * Remove a tag from a file
 */
class RemoveTag extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$app = DI::app();
		$logger = DI::logger();

		$item_id = (($app->argc > 1) ? intval($app->argv[1]) : 0);

		$term = XML::unescape(trim($_GET['term'] ?? ''));
		$cat = XML::unescape(trim($_GET['cat'] ?? ''));

		$category = (($cat) ? true : false);

		if ($category) {
			$term = $cat;
		}

		$logger->info('Filer - Remove Tag', [
			'term'     => $term,
			'item'     => $item_id,
			'category' => ($category ? 'true' : 'false')
		]);

		if ($item_id && strlen($term)) {
			if (FileTag::unsaveFile(local_user(), $item_id, $term, $category)) {
				info('Item removed');
			}
		} else {
			info('Item was not deleted');
		}

		$app->internalRedirect('network?file=' . rawurlencode($term));
	}
}

<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Item;

/**
 * Toggle starred items
 */
class Starred extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!local_user()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		if (empty($parameters['item'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$itemId = intval($parameters['item']);

		$item = Item::selectFirstForUser(local_user(), ['starred'], ['uid' => local_user(), 'id' => $itemId]);
		if (empty($item)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$starred = !(bool)$item['starred'];

		Item::update(['starred' => $starred], ['id' => $itemId]);

		// See if we've been passed a return path to redirect to
		$returnPath = $_REQUEST['return'] ?? '';
		if (!empty($returnPath)) {
			$rand = '_=' . time() . (strpos($returnPath, '?') ? '&' : '?') . 'rand';
			DI::app()->internalRedirect($returnPath . $rand);
		}

		// the json doesn't really matter, it will either be 0 or 1
		echo json_encode((int)$starred);
		exit();
	}
}

<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Item;

/**
 * Toggle pinned items
 */
class Pinned extends BaseModule
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

		$pinned = !Item::getPinned($itemId, local_user());

		Item::setPinned($itemId, local_user(), $pinned);

		// See if we've been passed a return path to redirect to
		$returnPath = $_REQUEST['return'] ?? '';
		if (!empty($returnPath)) {
			$rand = '_=' . time() . (strpos($returnPath, '?') ? '&' : '?') . 'rand';
			DI::app()->internalRedirect($returnPath . $rand);
		}

		// the json doesn't really matter, it will either be 0 or 1
		echo json_encode((int)$pinned);
		exit();
	}
}

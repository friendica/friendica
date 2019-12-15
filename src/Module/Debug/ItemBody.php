<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Network\HTTPException;

/**
 * Print the body of an Item
 */
class ItemBody extends BaseModule
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(L10n::t('Access denied.'));
		}

		$app = DI::app();

		// @TODO: Replace with parameter from router
		$itemId = (($app->argc > 1) ? intval($app->argv[1]) : 0);

		if (!$itemId) {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		}

		$item = Item::selectFirst(['body'], ['uid' => local_user(), 'id' => $itemId]);

		if (!empty($item)) {
			if (DI::mode()->isAjax()) {
				echo str_replace("\n", '<br />', $item['body']);
				exit();
			} else {
				return str_replace("\n", '<br />', $item['body']);
			}
		} else {
			throw new HTTPException\NotFoundException(L10n::t('Item not found.'));
		}
	}
}

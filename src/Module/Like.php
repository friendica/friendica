<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Core\Session;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Performs a like and optionally redirects to a return path
 */
class Like extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		$verb = Strings::escapeTags(trim($_GET['verb']));

		if (!$verb) {
			$verb = 'like';
		}

		$app = DI::app();

		// @TODO: Replace with parameter from router
		$itemId = (($app->argc > 1) ? Strings::escapeTags(trim($app->argv[1])) : 0);

		if (!Item::performLike($itemId, $verb)) {
			throw new HTTPException\BadRequestException();
		}

		// Decide how to return. If we were called with a 'return' argument,
		// then redirect back to the calling page. If not, just quietly end
		$returnPath = $_REQUEST['return'] ?? '';

		if (!empty($returnPath)) {
			$rand = '_=' . time();
			if (strpos($returnPath, '?')) {
				$rand = "&$rand";
			} else {
				$rand = "?$rand";
			}

			$app->internalRedirect($returnPath . $rand);
		}
	}
}

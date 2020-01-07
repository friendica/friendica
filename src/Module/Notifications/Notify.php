<?php

namespace Friendica\Module\Notifications;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Registry\DI;
use Friendica\Network\HTTPException;
use Friendica\Registry\App;
use Friendica\Registry\Model;

/**
 * Interacting with the /notify command
 */
class Notify extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(L10n::t('Permission denied.'));
		}
	}

	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if ($a->argc > 2 && $a->argv[1] === 'mark' && $a->argv[2] === 'all') {
			$success              = Model::notify()->setAllSeen();

			header('Content-type: application/json; charset=utf-8');
			echo json_encode([
				'result' => ($success) ? 'success' : 'fail',
			]);
			exit();
		}
	}

	/**
	 * Redirect to the notifications main page or to the url for the chosen notify
	 *
	 * @return string|void
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if ($a->argc > 2 && $a->argv[1] === 'view' && intval($a->argv[2])) {
			$notificationsManager = Model::notify();
			// @TODO: Replace with parameter from router
			$note = $notificationsManager->getByID($a->argv[2]);
			if (!empty($note)) {
				$notificationsManager->setSeen($note);
				if (!empty($note['link'])) {
					System::externalRedirect($note['link']);
				}
			}

			App::baseUrl()->redirect();
		}

		// @TODO: Replace with parameter from router
		App::baseUrl()->redirect('notifications/system');
	}
}

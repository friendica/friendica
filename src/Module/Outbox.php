<?php
/**
 * @file src/Module/Outbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if (empty($a->argv[1])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = $_REQUEST['page'] ?? null;

		/// @todo Add Authentication to enable fetching of non public content
		// $requester = HTTPSignature::getSigner('', $_SERVER);

		$outbox = ActivityPub\Transmitter::getOutbox($owner, $page);

		header('Content-Type: application/activity+json');
		echo json_encode($outbox);
		exit();
	}
}

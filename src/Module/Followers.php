<?php
/**
 * @file src/Module/Followers.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Followers
 */
class Followers extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if (empty($a->argv[1])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// @TODO: Replace with parameter from router
		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = $_REQUEST['page'] ?? null;

		$followers = ActivityPub\Transmitter::getFollowers($owner, $page);

		header('Content-Type: application/activity+json');
		echo json_encode($followers);
		exit();
	}
}

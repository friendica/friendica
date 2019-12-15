<?php
/**
 * @file src/Module/Objects.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if (empty($a->argv[1])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		if (!ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(str_replace('objects/', 'display/', $a->query_string));
		}

		/// @todo Add Authentication to enable fetching of non public content
		// $requester = HTTPSignature::getSigner('', $_SERVER);

		// At first we try the original post with that guid
		// @TODO: Replace with parameter from router
		$item = Item::selectFirst(['id'], ['guid' => $a->argv[1], 'origin' => true, 'private' => false]);
		if (!DBA::isResult($item)) {
			// If no original post could be found, it could possibly be a forum post, there we remove the "origin" field.
			// @TODO: Replace with parameter from router
			$item = Item::selectFirst(['id', 'author-link'], ['guid' => $a->argv[1], 'private' => false]);
			if (!DBA::isResult($item) || !strstr($item['author-link'], System::baseUrl())) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
		}

		$activity = ActivityPub\Transmitter::createActivityFromItem($item['id'], true);
		// Only display "Create" activity objects here, no reshares or anything else
		if (!is_array($activity['object']) || ($activity['type'] != 'Create')) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$data = ['@context' => ActivityPub::CONTEXT];
		$data = array_merge($data, $activity['object']);

		header('Content-Type: application/activity+json');
		echo json_encode($data);
		exit();
	}
}

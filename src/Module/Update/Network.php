<?php

namespace Friendica\Module\Update;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Conversation\Network as NetworkModule;

class Network extends NetworkModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!isset($_GET['p']) || !isset($_GET['item'])) {
			exit();
		}

		self::parseRequest($parameters, $_GET);

		$profile_uid = intval($_GET['p']);

		$o = '';

		if (!DI::pConfig()->get($profile_uid, 'system', 'no_auto_update') || ($_GET['force'] == 1)) {
			if (!empty($_GET['item'])) {
				$item = Post::selectFirst(['parent'], ['id' => $_GET['item']]);
				$parent = $item['parent'] ?? 0;
			} else {
				$parent = 0;
			}

			$conditionFields = [];
			if (!empty($parent)) {
				// Load only a single thread
				$conditionFields['parent'] = $parent;
			} elseif (self::$order === 'received') {
				// Only load new toplevel posts
				$conditionFields['unseen'] = true;
				$conditionFields['gravity'] = GRAVITY_PARENT;
			} else {
				// Load all unseen items
				$conditionFields['unseen'] = true;
			}

			$params = ['limit' => 100];
			$table = 'network-item-view';

			$items = self::getItems($table, $params, $conditionFields);

			if (self::$order === 'received') {
				$ordering = '`received`';
			} else {
				$ordering = '`commented`';
			}

			$o = conversation(DI::app(), $items, 'network', $profile_uid, false, $ordering, local_user());
		}

		System::htmlUpdateExit($o);
	}
}

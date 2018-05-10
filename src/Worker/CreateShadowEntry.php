<?php
/**
 * @file src/Worker/CreateShadowEntry.php
 * @brief This script creates posts with UID = 0 for a given public post.
 *
 * This script is started from mod/item.php to save some time when doing a post.
 */

namespace Friendica\Worker;

use Friendica\Model\Item;

class CreateShadowEntry {
	public static function execute($message_id = 0) {
		if (empty($message_id)) {
			return;
		}

		Item::addShadowPost($message_id);
	}
}

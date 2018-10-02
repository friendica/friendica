<?php
/**
 * @file src/Worker/RemoveContact.php
 * @brief Removes orphaned data from deleted contacts
 */
namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Core\Protocol;

require_once 'include/dba.php';

class RemoveContact {
	public static function execute($id) {

		// Only delete if the contact is to be deleted
		$condition = ['network' => Protocol::PHANTOM, 'id' => $id];
		$r = DBA::exists('contact', $condition);
		if (!DBA::isResult($r)) {
			return;
		}

		// Now we delete the contact and all depending tables
		DBA::delete('contact', ['id' => $id]);
	}
}

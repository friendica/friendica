<?php
/**
 * @file src/Model/Term
 */
namespace Friendica\Model;

use dba;

require_once "include/dba.php";

class Term
{
	/**
	 * @param integer $itemid item id
	 * @return void
	 */
	public static function createFromItem($itemid)
	{
		$message = dba::selectFirst('item', ['uid', 'deleted', 'file'], ['id' => $itemid]);
		if (!\Friendica\Database\DBM::isResult($message)) {
			return;
		}

		// Clean up all tags
		q("DELETE FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` IN (%d, %d)",
			intval(TERM_OBJ_POST),
			intval($itemid),
			intval(TERM_FILE),
			intval(TERM_CATEGORY));

		if ($message["deleted"]) {
			return;
		}

		if (preg_match_all("/\[(.*?)\]/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				dba::insert('term', [
					'uid' => $message["uid"],
					'oid' => $itemid,
					'otype' => TERM_OBJ_POST,
					'type' => TERM_FILE,
					'term' => $file
				]);
			}
		}

		if (preg_match_all("/\<(.*?)\>/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				dba::insert('term', [
					'uid' => $message["uid"],
					'oid' => $itemid,
					'otype' => TERM_OBJ_POST,
					'type' => TERM_CATEGORY,
					'term' => $file
				]);
			}
		}
	}

	/**
	 * @param string  $itemuri item uri
	 * @param integer $uid     uid
	 * @return void
	 */
	public static function createFromItemURI($itemuri, $uid)
	{
		$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

		if (count($messages)) {
			foreach ($messages as $message) {
				self::createFromItem($message["id"]);
			}
		}
	}
}

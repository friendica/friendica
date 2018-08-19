<?php
/**
 * @file src/Worker/DBClean.php
 * @brief The script is called from time to time to clean the database entries and remove orphaned data.
 */

namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBA;

require_once 'include/dba.php';

class DBClean {
	public static function execute($stage = 0) {

		if (!Config::get('system', 'dbclean', false)) {
			return;
		}

		if ($stage == 0) {
			self::forkCleanProcess();
		} else {
			self::removeOrphans($stage);
		}
	}

	/**
	 * @brief Fork the different DBClean processes
	 */
	private static function forkCleanProcess() {
		// Get the expire days for step 8 and 9
		$days = Config::get('system', 'dbclean-expire-days', 0);

		for ($i = 1; $i <= 10; $i++) {
			// Execute the background script for a step when it isn't finished.
			// Execute step 8 and 9 only when $days is defined.
			if (!Config::get('system', 'finished-dbclean-'.$i, false) && (($i < 8) || ($i > 9) || ($days > 0))) {
				Worker::add(PRIORITY_LOW, 'DBClean', $i);
			}
		}
	}

	/**
	 * @brief Remove orphaned database entries
	 * @param integer $stage What should be deleted?
	 *
	 * Values for $stage:
	 * ------------------
	 *  1:	Old global item entries from item table without user copy.
	 *  2:	Items without parents.
	 *  3:	Orphaned data from thread table.
	 *  4:	Orphaned data from notify table.
	 *  5:	Orphaned data from notify-threads table.
	 *  6:	Orphaned data from sign table.
	 *  7:	Orphaned data from term table.
	 *  8:	Expired threads.
	 *  9:	Old global item entries from expired threads.
	 * 10:	Old conversations.
	 */
	private static function removeOrphans($stage) {
		$count = 0;

		// We split the deletion in many small tasks
		$limit = 1000;

		// Get the expire days for step 8 and 9
		$days = Config::get('system', 'dbclean-expire-days', 0);
		$days_unclaimed = Config::get('system', 'dbclean-expire-unclaimed', 90);

		if ($days_unclaimed == 0) {
			$days_unclaimed = $days;
		}

		if ($stage == 1) {
			if ($days_unclaimed <= 0) {
				return;
			}

			$last_id = Config::get('system', 'dbclean-last-id-1', 0);

			logger("Deleting old global item entries from item table without user copy. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id` FROM `item` WHERE `uid` = 0 AND
						NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0) AND
						`received` < UTC_TIMESTAMP() - INTERVAL ? DAY AND `id` >= ?
					ORDER BY `id` LIMIT ?", $days_unclaimed, $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found global item orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 1, $last_id);
			} else {
				logger("No global item orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." old global item entries from item table without user copy. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-1', $last_id);
		} elseif ($stage == 2) {
			$last_id = Config::get('system', 'dbclean-last-id-2', 0);

			logger("Deleting items without parents. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id` FROM `item`
					WHERE NOT EXISTS (SELECT `id` FROM `item` AS `i` WHERE `item`.`parent` = `i`.`id`)
					AND `id` >= ? ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found item orphans without parents: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 2, $last_id);
			} else {
				logger("No item orphans without parents found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." items without parents. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-2', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-2', true);
			}
		} elseif ($stage == 3) {
			$last_id = Config::get('system', 'dbclean-last-id-3', 0);

			logger("Deleting orphaned data from thread table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid` FROM `thread`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `thread`.`iid`) AND `iid` >= ?
					ORDER BY `iid` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found thread orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["iid"];
					DBA::delete('thread', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 3, $last_id);
			} else {
				logger("No thread orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." orphaned data from thread table. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-3', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-3', true);
			}
		} elseif ($stage == 4) {
			$last_id = Config::get('system', 'dbclean-last-id-4', 0);

			logger("Deleting orphaned data from notify table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid`, `id` FROM `notify`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `notify`.`iid`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found notify orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('notify', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 4, $last_id);
			} else {
				logger("No notify orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." orphaned data from notify table. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-4', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-4', true);
			}
		} elseif ($stage == 5) {
			$last_id = Config::get('system', 'dbclean-last-id-5', 0);

			logger("Deleting orphaned data from notify-threads table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id` FROM `notify-threads`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `notify-threads`.`master-parent-item`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found notify-threads orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('notify-threads', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 5, $last_id);
			} else {
				logger("No notify-threads orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." orphaned data from notify-threads table. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-5', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-5', true);
			}
		} elseif ($stage == 6) {
			$last_id = Config::get('system', 'dbclean-last-id-6', 0);

			logger("Deleting orphaned data from sign table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid`, `id` FROM `sign`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `sign`.`iid`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found sign orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('sign', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 6, $last_id);
			} else {
				logger("No sign orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." orphaned data from sign table. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-6', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-6', true);
			}
		} elseif ($stage == 7) {
			$last_id = Config::get('system', 'dbclean-last-id-7', 0);

			logger("Deleting orphaned data from term table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `oid`, `tid` FROM `term`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `term`.`oid`) AND `tid` >= ?
					ORDER BY `tid` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found term orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["tid"];
					DBA::delete('term', ['oid' => $orphan["oid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 7, $last_id);
			} else {
				logger("No term orphans found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." orphaned data from term table. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-7', $last_id);

			if ($count < $limit) {
				Config::set('system', 'finished-dbclean-7', true);
			}
		} elseif ($stage == 8) {
			if ($days <= 0) {
				return;
			}

			$last_id = Config::get('system', 'dbclean-last-id-8', 0);

			logger("Deleting expired threads. Last ID: ".$last_id);
			$r = DBA::p("SELECT `thread`.`iid` FROM `thread`
	                                INNER JOIN `contact` ON `thread`.`contact-id` = `contact`.`id` AND NOT `notify_new_posts`
	                                WHERE `thread`.`received` < UTC_TIMESTAMP() - INTERVAL ? DAY
	                                        AND NOT `thread`.`mention` AND NOT `thread`.`starred`
	                                        AND NOT `thread`.`wall` AND NOT `thread`.`origin`
	                                        AND `thread`.`uid` != 0 AND `thread`.`iid` >= ?
	                                        AND NOT `thread`.`iid` IN (SELECT `parent` FROM `item`
	                                                        WHERE (`item`.`starred` OR (`item`.`resource-id` != '')
	                                                                OR (`item`.`file` != '') OR (`item`.`event-id` != '')
	                                                                OR (`item`.`attach` != '') OR `item`.`wall` OR `item`.`origin`)
	                                                                AND `item`.`parent` = `thread`.`iid`)
	                                ORDER BY `thread`.`iid` LIMIT 1000", $days, $last_id);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found expired threads: ".$count);
				while ($thread = DBA::fetch($r)) {
					$last_id = $thread["iid"];
					DBA::delete('thread', ['iid' => $thread["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 8, $last_id);
			} else {
				logger("No expired threads found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." expired threads. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-8', $last_id);
		} elseif ($stage == 9) {
			if ($days <= 0) {
				return;
			}

			$last_id = Config::get('system', 'dbclean-last-id-9', 0);
			$till_id = Config::get('system', 'dbclean-last-id-8', 0);

			logger("Deleting old global item entries from expired threads from ID ".$last_id." to ID ".$till_id);
			$r = DBA::p("SELECT `id` FROM `item` WHERE `uid` = 0 AND
						NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0) AND
						`received` < UTC_TIMESTAMP() - INTERVAL 90 DAY AND `id` >= ? AND `id` <= ?
					ORDER BY `id` LIMIT ?", $last_id, $till_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found global item entries from expired threads: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 9, $last_id);
			} else {
				logger("No global item entries from expired threads");
			}
			DBA::close($r);
			logger("Done deleting ".$count." old global item entries from expired threads. Last ID: ".$last_id);

			Config::set('system', 'dbclean-last-id-9', $last_id);
		} elseif ($stage == 10) {
			$last_id = Config::get('system', 'dbclean-last-id-10', 0);
			$days = intval(Config::get('system', 'dbclean_expire_conversation', 90));

			logger("Deleting old conversations. Last created: ".$last_id);
			$r = DBA::p("SELECT `received`, `item-uri` FROM `conversation`
					WHERE `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
					ORDER BY `received` LIMIT ?", $days, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				logger("found old conversations: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["received"];
					DBA::delete('conversation', ['item-uri' => $orphan["item-uri"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 10, $last_id);
			} else {
				logger("No old conversations found");
			}
			DBA::close($r);
			logger("Done deleting ".$count." conversations. Last created: ".$last_id);

			Config::set('system', 'dbclean-last-id-10', $last_id);
		}
	}
}

<?php

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE `queue` SET `last` = '%s' WHERE `id` = %d",
		dbesc(datetime_convert()),
		intval($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM `queue` WHERE `id` = %d",
		intval($id)
	);
}

/**
 * @brief Checks if the communication with a given contact had problems recently
 *
 * @param int $cid Contact id
 *
 * @return bool The communication with this contact has currently problems
 */
function was_recently_delayed($cid) {
	$was_delayed = false;

	// Are there queue entries that were recently added?
	$r = q("SELECT `id` FROM `queue` WHERE `cid` = %d
		AND `last` > UTC_TIMESTAMP() - INTERVAL 15 MINUTE LIMIT 1",
		intval($cid)
	);

	$was_delayed = dbm::is_result($r);

	// We set "term-date" to a current date if the communication has problems.
	// If the communication works again we reset this value.
	if ($was_delayed) {
		$r = q("SELECT `term-date` FROM `contact` WHERE `id` = %d AND `term-date` <= '1000-01-01' LIMIT 1",
			intval($cid)
		);
		$was_delayed = !dbm::is_result($r);
	}

	return $was_delayed;
}


function add_to_queue($cid,$network,$msg,$batch = false) {

	$max_queue = get_config('system','max_contact_queue');
	if($max_queue < 1)
		$max_queue = 500;

	$batch_queue = get_config('system','max_batch_queue');
	if($batch_queue < 1)
		$batch_queue = 1000;

	$r = q("SELECT COUNT(*) AS `total` FROM `queue` INNER JOIN `contact` ON `queue`.`cid` = `contact`.`id` 
		WHERE `queue`.`cid` = %d AND `contact`.`self` = 0 ",
		intval($cid)
	);
	if (dbm::is_result($r)) {
		if($batch &&  ($r[0]['total'] > $batch_queue)) {
			logger('add_to_queue: too many queued items for batch server ' . $cid . ' - discarding message');
			return;
		}
		elseif((! $batch) && ($r[0]['total'] > $max_queue)) {
			logger('add_to_queue: too many queued items for contact ' . $cid . ' - discarding message');
			return;
		}
	}

	q("INSERT INTO `queue` ( `cid`, `network`, `created`, `last`, `content`, `batch`)
		VALUES ( %d, '%s', '%s', '%s', '%s', %d) ",
		intval($cid),
		dbesc($network),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($msg),
		intval(($batch) ? 1: 0)
	);

}

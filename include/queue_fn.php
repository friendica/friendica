<?php

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE `queue` SET `last` = '%s' WHERE `id` = %d LIMIT 1",
		dbesc(datetime_convert()),
		intval($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM `queue` WHERE `id` = %d LIMIT 1",
		intval($id)
	);
}


function add_to_queue($cid,$network,$msg,$batch = false) {

	$max_queue = get_config('system','max_contact_queue');
	if($max_queue < 1)
		$max_queue = 500;

	$batch_queue = get_config('system','max_batch_queue');
	if($batch_queue < 1)
		$batch_queue = 1000;

	$r = q("SELECT COUNT(*) AS `total` FROM `queue` left join `contact` ON `queue`.`cid` = `contact`.`id` 
		WHERE `queue`.`cid` = %d AND `contact`.`self` = 0 ",
		intval($cid)
	);
	if($r && count($r)) {
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

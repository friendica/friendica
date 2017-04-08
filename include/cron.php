<?php
use \Friendica\Core\Config;

function cron_run(&$argv, &$argc){
	global $a;

	require_once 'include/datetime.php';

	// Poll contacts with specific parameters
	if ($argc > 1) {
		cron_poll_contacts($argc, $argv);
		return;
	}

	$last = get_config('system', 'last_cron');

	$poll_interval = intval(get_config('system', 'cron_interval'));
	if (! $poll_interval) {
		$poll_interval = 10;
	}

	if ($last) {
		$next = $last + ($poll_interval * 60);
		if ($next > time()) {
			logger('cron intervall not reached');
			return;
		}
	}

	logger('cron: start');

	// run queue delivery process in the background
	proc_run(PRIORITY_NEGLIGIBLE, "include/queue.php");

	// run the process to discover global contacts in the background
	proc_run(PRIORITY_LOW, "include/discover_poco.php");

	// run the process to update locally stored global contacts in the background
	proc_run(PRIORITY_LOW, "include/discover_poco.php", "checkcontact");

	// Expire and remove user entries
	proc_run(PRIORITY_MEDIUM, "include/cronjobs.php", "expire_and_remove_users");

	// Check OStatus conversations
	proc_run(PRIORITY_MEDIUM, "include/cronjobs.php", "ostatus_mentions");

	// Check every conversation
	proc_run(PRIORITY_MEDIUM, "include/cronjobs.php", "ostatus_conversations");

	// Call possible post update functions
	proc_run(PRIORITY_LOW, "include/cronjobs.php", "post_update");

	// update nodeinfo data
	proc_run(PRIORITY_LOW, "include/cronjobs.php", "nodeinfo");

	// Clear cache entries
	proc_run(PRIORITY_LOW, "include/cronjobs.php", "clear_cache");

	// Repair missing Diaspora values in contacts
	proc_run(PRIORITY_LOW, "include/cronjobs.php", "repair_diaspora");

	// Repair entries in the database
	proc_run(PRIORITY_LOW, "include/cronjobs.php", "repair_database");

	// once daily run birthday_updates and then expire in background
	$d1 = get_config('system', 'last_expire_day');
	$d2 = intval(datetime_convert('UTC','UTC','now','d'));

	if ($d2 != intval($d1)) {

		proc_run(PRIORITY_LOW, "include/cronjobs.php", "update_contact_birthdays");

		proc_run(PRIORITY_LOW, "include/discover_poco.php", "update_server");

		proc_run(PRIORITY_LOW, "include/discover_poco.php", "suggestions");

		set_config('system', 'last_expire_day',$d2);

		proc_run(PRIORITY_LOW, 'include/expire.php');

		proc_run(PRIORITY_MEDIUM, 'include/dbclean.php');

		proc_run(PRIORITY_LOW, "include/cronjobs.php", "update_photo_albums");
	}

	// Poll contacts
	cron_poll_contacts($argc, $argv);

	logger('cron: end');

	set_config('system', 'last_cron', time());

	return;
}

/**
 * @brief Poll contacts for unreceived messages
 *
 * @param Integer $argc Number of command line arguments
 * @param Array $argv Array of command line arguments
 */
function cron_poll_contacts($argc, $argv) {
	$manual_id  = 0;
	$generation = 0;
	$force      = false;
	$restart    = false;

	if (($argc > 1) && ($argv[1] == 'force')) {
		$force = true;
	}
	if (($argc > 1) && ($argv[1] == 'restart')) {
		$restart = true;
		$generation = intval($argv[2]);
		if (!$generation) {
			killme();
		}
	}

	if (($argc > 1) && intval($argv[1])) {
		$manual_id = intval($argv[1]);
		$force     = true;
	}

	$sql_extra = (($manual_id) ? " AND `id` = $manual_id " : "");

	reload_plugins();

	$d = datetime_convert();

	// Only poll from those with suitable relationships,
	// and which have a polling address and ignore Diaspora since
	// we are unable to match those posts with a Diaspora GUID and prevent duplicates.

	$abandon_days = intval(get_config('system', 'account_abandon_days'));
	if ($abandon_days < 1) {
		$abandon_days = 0;
	}
	$abandon_sql = (($abandon_days)
		? sprintf(" AND `user`.`login_date` > UTC_TIMESTAMP() - INTERVAL %d DAY ", intval($abandon_days))
		: ''
	);

	$contacts = q("SELECT `contact`.`id` FROM `user`
			STRAIGHT_JOIN `contact`
			ON `contact`.`uid` = `user`.`uid` AND `contact`.`rel` IN (%d, %d) AND `contact`.`poll` != ''
				AND `contact`.`network` IN ('%s', '%s', '%s', '%s', '%s', '%s') $sql_extra
				AND NOT `contact`.`self` AND NOT `contact`.`blocked` AND NOT `contact`.`readonly`
				AND NOT `contact`.`archive`
			WHERE NOT `user`.`account_expired` AND NOT `user`.`account_removed` $abandon_sql ORDER BY RAND()",
		intval(CONTACT_IS_SHARING),
		intval(CONTACT_IS_FRIEND),
		dbesc(NETWORK_DFRN),
		dbesc(NETWORK_ZOT),
		dbesc(NETWORK_OSTATUS),
		dbesc(NETWORK_FEED),
		dbesc(NETWORK_MAIL),
		dbesc(NETWORK_MAIL2)
	);

	if (!dbm::is_result($contacts)) {
		return;
	}

	foreach ($contacts as $c) {

		$res = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($c['id'])
		);

		if (!dbm::is_result($res)) {
			continue;
		}

		foreach ($res as $contact) {

			$xml = false;

			if ($manual_id) {
				$contact['last-update'] = NULL_DATE;
			}

			if (in_array($contact['network'], array(NETWORK_DFRN, NETWORK_ZOT, NETWORK_OSTATUS))) {
				$contact['priority'] = 2;
			}

			if ($contact['subhub'] AND in_array($contact['network'], array(NETWORK_DFRN, NETWORK_ZOT, NETWORK_OSTATUS))) {
				/*
				 * We should be getting everything via a hub. But just to be sure, let's check once a day.
				 * (You can make this more or less frequent if desired by setting 'pushpoll_frequency' appropriately)
				 * This also lets us update our subscription to the hub, and add or replace hubs in case it
				 * changed. We will only update hubs once a day, regardless of 'pushpoll_frequency'.
				 */
				$poll_interval = get_config('system', 'pushpoll_frequency');
				$contact['priority'] = (($poll_interval !== false) ? intval($poll_interval) : 3);
			}

			if ($contact['priority'] AND !$force) {
				$update = false;

				$t = $contact['last-update'];

				/*
				 * Based on $contact['priority'], should we poll this site now? Or later?
				 */
				switch ($contact['priority']) {
					case 5:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 month")) {
							$update = true;
						}
						break;
					case 4:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 week")) {
							$update = true;
						}
						break;
					case 3:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 day")) {
							$update = true;
						}
						break;
					case 2:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 12 hour")) {
							$update = true;
						}
						break;
					case 1:
					default:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 hour")) {
							$update = true;
						}
						break;
				}
				if (!$update) {
					continue;
				}
			}

			logger("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact["nick"] . " " . $contact["name"]);

			if (($contact['network'] == NETWORK_FEED) AND ($contact['priority'] <= 3)) {
				proc_run(PRIORITY_MEDIUM, 'include/onepoll.php', intval($contact['id']));
			} else {
				proc_run(PRIORITY_LOW, 'include/onepoll.php', intval($contact['id']));
			}

			if ($interval) {
				time_sleep_until(microtime(true) + (float) $interval);
			}
		}
	}
}

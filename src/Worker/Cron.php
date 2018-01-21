<?php
/**
 * @file src/Worker/Cron.php
 */
namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use dba;

require_once 'include/dba.php';

Class Cron {
	public static function execute($parameter = '', $generation = 0) {
		global $a;

		require_once 'include/datetime.php';

		// Poll contacts with specific parameters
		if (!empty($parameter)) {
			self::pollContacts($parameter, $generation);
			return;
		}

		$last = Config::get('system', 'last_cron');

		$poll_interval = intval(Config::get('system', 'cron_interval'));
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
		Worker::add(PRIORITY_NEGLIGIBLE, "Queue");

		// run the process to discover global contacts in the background
		Worker::add(PRIORITY_LOW, "DiscoverPoCo");

		// run the process to update locally stored global contacts in the background
		Worker::add(PRIORITY_LOW, "DiscoverPoCo", "checkcontact");

		// Expire and remove user entries
		Worker::add(PRIORITY_MEDIUM, "CronJobs", "expire_and_remove_users");

		// Call possible post update functions
		Worker::add(PRIORITY_LOW, "CronJobs", "post_update");

		// update nodeinfo data
		Worker::add(PRIORITY_LOW, "CronJobs", "nodeinfo");

		// Clear cache entries
		Worker::add(PRIORITY_LOW, "CronJobs", "clear_cache");

		// Repair missing Diaspora values in contacts
		Worker::add(PRIORITY_LOW, "CronJobs", "repair_diaspora");

		// Repair entries in the database
		Worker::add(PRIORITY_LOW, "CronJobs", "repair_database");

		// once daily run birthday_updates and then expire in background
		$d1 = Config::get('system', 'last_expire_day');
		$d2 = intval(datetime_convert('UTC', 'UTC', 'now', 'd'));

		if ($d2 != intval($d1)) {

			Worker::add(PRIORITY_LOW, "CronJobs", "update_contact_birthdays");

			Worker::add(PRIORITY_LOW, "DiscoverPoCo", "update_server");

			Worker::add(PRIORITY_LOW, "DiscoverPoCo", "suggestions");

			Config::set('system', 'last_expire_day', $d2);

			Worker::add(PRIORITY_LOW, 'Expire');

			Worker::add(PRIORITY_MEDIUM, 'DBClean');

			Worker::add(PRIORITY_LOW, "CronJobs", "update_photo_albums");

			// Delete all done workerqueue entries
			dba::delete('workerqueue', ['`done` AND `executed` < UTC_TIMESTAMP() - INTERVAL 12 HOUR']);

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'CheckVersion');
		}

		// Poll contacts
		self::pollContacts($parameter, $generation);

		logger('cron: end');

		Config::set('system', 'last_cron', time());

		return;
	}

	/**
	 * @brief Poll contacts for unreceived messages
	 *
	 * @todo Currently it seems as if the following parameter aren't used at all ...
	 *
	 * @param string $parameter Parameter (force, restart, ...) for the contact polling
	 * @param integer $generation
	 */
	private static function pollContacts($parameter, $generation) {
		$manual_id  = 0;
		$generation = 0;
		$force      = false;
		$restart    = false;

		if ($parameter == 'force') {
			$force = true;
		}
		if ($parameter == 'restart') {
			$restart = true;
			$generation = intval($generation);
			if (!$generation) {
				killme();
			}
		}

		if (intval($parameter)) {
			$manual_id = intval($parameter);
			$force     = true;
		}

		$min_poll_interval = Config::get('system', 'min_poll_interval', 1);

		$sql_extra = (($manual_id) ? " AND `id` = $manual_id " : "");

		Addon::reload();

		$d = datetime_convert();

		// Only poll from those with suitable relationships,
		// and which have a polling address and ignore Diaspora since
		// we are unable to match those posts with a Diaspora GUID and prevent duplicates.

		$abandon_days = intval(Config::get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}
		$abandon_sql = (($abandon_days)
			? sprintf(" AND `user`.`login_date` > UTC_TIMESTAMP() - INTERVAL %d DAY ", intval($abandon_days))
			: ''
		);

		$contacts = q("SELECT `contact`.`id`, `contact`.`nick`, `contact`.`name`, `contact`.`network`, `contact`.`archive`,
					`contact`.`last-update`, `contact`.`priority`, `contact`.`rel`, `contact`.`subhub`
				FROM `user`
				STRAIGHT_JOIN `contact`
				ON `contact`.`uid` = `user`.`uid` AND `contact`.`poll` != ''
					AND `contact`.`network` IN ('%s', '%s', '%s', '%s', '%s') $sql_extra
					AND NOT `contact`.`self` AND NOT `contact`.`blocked`
				WHERE NOT `user`.`account_expired` AND NOT `user`.`account_removed` $abandon_sql",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_OSTATUS),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_FEED),
			dbesc(NETWORK_MAIL)
		);

		if (!DBM::isResult($contacts)) {
			return;
		}

		foreach ($contacts as $contact) {

			if ($manual_id) {
				$contact['last-update'] = NULL_DATE;
			}

			// Friendica and OStatus are checked once a day
			if (in_array($contact['network'], [NETWORK_DFRN, NETWORK_OSTATUS])) {
				$contact['priority'] = 2;
			}

			if ($contact['subhub'] && in_array($contact['network'], [NETWORK_DFRN, NETWORK_OSTATUS])) {
				/*
				 * We should be getting everything via a hub. But just to be sure, let's check once a day.
				 * (You can make this more or less frequent if desired by setting 'pushpoll_frequency' appropriately)
				 * This also lets us update our subscription to the hub, and add or replace hubs in case it
				 * changed. We will only update hubs once a day, regardless of 'pushpoll_frequency'.
				 */
				$poll_interval = Config::get('system', 'pushpoll_frequency');
				$contact['priority'] = (!is_null($poll_interval) ? intval($poll_interval) : 3);
			}

			// Check Diaspora contacts or followers once a week
			if (($contact["network"] == NETWORK_DIASPORA) || ($contact["rel"] == CONTACT_IS_FOLLOWER)) {
				$contact['priority'] = 4;
			}

			// Check archived contacts once a month
			if ($contact['archive']) {
				$contact['priority'] = 5;
			}

			if (($contact['priority'] >= 0) && !$force) {
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
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + 1 hour")) {
							$update = true;
						}
						break;
					case 0:
					default:
						if (datetime_convert('UTC', 'UTC', 'now') > datetime_convert('UTC', 'UTC', $t . " + ".$min_poll_interval." minute")) {
							$update = true;
						}
						break;
				}
				if (!$update) {
					continue;
				}
			}

			if (($contact['network'] == NETWORK_FEED) && ($contact['priority'] <= 3)) {
				$priority = PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = PRIORITY_NEGLIGIBLE;
			} else {
				$priority = PRIORITY_LOW;
			}

			logger("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true], 'OnePoll', (int)$contact['id']);
		}
	}
}

<?php
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;

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
		Worker::add(PRIORITY_NEGLIGIBLE, "queue");

		// run the process to discover global contacts in the background
		Worker::add(PRIORITY_LOW, "discover_poco");

		// run the process to update locally stored global contacts in the background
		Worker::add(PRIORITY_LOW, "discover_poco", "checkcontact");

		// Expire and remove user entries
		Worker::add(PRIORITY_MEDIUM, "cronjobs", "expire_and_remove_users");

		// Call possible post update functions
		Worker::add(PRIORITY_LOW, "cronjobs", "post_update");

		// update nodeinfo data
		Worker::add(PRIORITY_LOW, "cronjobs", "nodeinfo");

		// Clear cache entries
		Worker::add(PRIORITY_LOW, "cronjobs", "clear_cache");

		// Repair missing Diaspora values in contacts
		Worker::add(PRIORITY_LOW, "cronjobs", "repair_diaspora");

		// Repair entries in the database
		Worker::add(PRIORITY_LOW, "cronjobs", "repair_database");

		// once daily run birthday_updates and then expire in background
		$d1 = Config::get('system', 'last_expire_day');
		$d2 = intval(datetime_convert('UTC', 'UTC', 'now', 'd'));

		if ($d2 != intval($d1)) {

			Worker::add(PRIORITY_LOW, "cronjobs", "update_contact_birthdays");

			Worker::add(PRIORITY_LOW, "discover_poco", "update_server");

			Worker::add(PRIORITY_LOW, "discover_poco", "suggestions");

			Config::set('system', 'last_expire_day', $d2);

			Worker::add(PRIORITY_LOW, 'expire');

			Worker::add(PRIORITY_MEDIUM, 'dbclean');

			Worker::add(PRIORITY_LOW, "cronjobs", "update_photo_albums");

			// Delete all done workerqueue entries
			dba::delete('workerqueue', array('`done` AND `executed` < UTC_TIMESTAMP() - INTERVAL 12 HOUR'));

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'checkversion');
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

		reload_plugins();

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

		if (!DBM::is_result($contacts)) {
			return;
		}

		foreach ($contacts as $c) {

			$res = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($c['id'])
			);

			if (!DBM::is_result($res)) {
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

				if ($contact['subhub'] && in_array($contact['network'], array(NETWORK_DFRN, NETWORK_ZOT, NETWORK_OSTATUS))) {
					/*
					 * We should be getting everything via a hub. But just to be sure, let's check once a day.
					 * (You can make this more or less frequent if desired by setting 'pushpoll_frequency' appropriately)
					 * This also lets us update our subscription to the hub, and add or replace hubs in case it
					 * changed. We will only update hubs once a day, regardless of 'pushpoll_frequency'.
					 */
					$poll_interval = Config::get('system', 'pushpoll_frequency');
					$contact['priority'] = (($poll_interval !== false) ? intval($poll_interval) : 3);
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

				logger("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact["nick"] . " " . $contact["name"]);

				if (($contact['network'] == NETWORK_FEED) && ($contact['priority'] <= 3)) {
					$priority = PRIORITY_MEDIUM;
				} else {
					$priority = PRIORITY_LOW;
				}
				Worker::add(array('priority' => $priority, 'dont_fork' => true), 'OnePoll', (int)$contact['id']);
			}
		}
	}
}

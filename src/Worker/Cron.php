<?php
/**
 * @file src/Worker/Cron.php
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

class Cron
{
	public static function execute($parameter = '', $generation = 0)
	{
		$a = BaseObject::getApp();

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
				Logger::log('cron intervall not reached');
				return;
			}
		}

		Logger::log('cron: start');

		// Fork the cron jobs in separate parts to avoid problems when one of them is crashing
		Hook::fork($a->queue['priority'], "cron");

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

		// Clear cache entries
		Worker::add(PRIORITY_LOW, "CronJobs", "clear_cache");

		// Repair missing Diaspora values in contacts
		Worker::add(PRIORITY_LOW, "CronJobs", "repair_diaspora");

		// Repair entries in the database
		Worker::add(PRIORITY_LOW, "CronJobs", "repair_database");

		// once daily run birthday_updates and then expire in background
		$d1 = Config::get('system', 'last_expire_day');
		$d2 = intval(DateTimeFormat::utcNow('d'));

		// Daily cron calls
		if ($d2 != intval($d1)) {

			Worker::add(PRIORITY_LOW, "CronJobs", "update_contact_birthdays");

			Worker::add(PRIORITY_LOW, "CronJobs", "update_photo_albums");

			// Daily pickup if the expected storage move task started on setting change stopped for any reason
			Worker::add(PRIORITY_LOW, "CronJobs", "move_storage");

			// update nodeinfo data
			Worker::add(PRIORITY_LOW, "CronJobs", "nodeinfo");

			Worker::add(PRIORITY_LOW, "DiscoverPoCo", "update_server");

			Worker::add(PRIORITY_LOW, "DiscoverPoCo", "suggestions");

			Worker::add(PRIORITY_LOW, 'Expire');

			Worker::add(PRIORITY_MEDIUM, 'DBClean');

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'CheckVersion');

			Config::set('system', 'last_expire_day', $d2);
		}

		// Hourly cron calls
		if (Config::get('system', 'last_cron_hourly', 0) + 3600 < time()) {

			// Delete all done workerqueue entries
			DBA::delete('workerqueue', ['`done` AND `executed` < UTC_TIMESTAMP() - INTERVAL 1 HOUR']);

			// Optimizing this table only last seconds
			if (Config::get('system', 'optimize_workerqueue', false)) {
				DBA::e("OPTIMIZE TABLE `workerqueue`");
			}

			Config::set('system', 'last_cron_hourly', time());
		}

		// Ensure to have a .htaccess file.
		// this is a precaution for systems that update automatically
		$basepath = $a->getBasePath();
		if (!file_exists($basepath . '/.htaccess')) {
			copy($basepath . '/.htaccess-dist', $basepath . '/.htaccess');
		}

		// Poll contacts
		self::pollContacts($parameter, $generation);

		Logger::log('cron: end');

		Config::set('system', 'last_cron', time());

		return;
	}

	/**
	 * @brief Poll contacts for unreceived messages
	 *
	 * @todo  Currently it seems as if the following parameter aren't used at all ...
	 *
	 * @param string  $parameter Parameter (force, restart, ...) for the contact polling
	 * @param integer $generation
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function pollContacts($parameter, $generation) {
		$manual_id  = 0;
		$generation = 0;
		$force      = false;

		if ($parameter == 'force') {
			$force = true;
		}
		if ($parameter == 'restart') {
			$generation = intval($generation);
			if (!$generation) {
				exit();
			}
		}

		if (intval($parameter)) {
			$manual_id = intval($parameter);
			$force     = true;
		}

		$min_poll_interval = Config::get('system', 'min_poll_interval', 1);

		$sql_extra = (($manual_id) ? " AND `id` = $manual_id " : "");

		Addon::reload();

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
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::OSTATUS),
			DBA::escape(Protocol::DIASPORA),
			DBA::escape(Protocol::FEED),
			DBA::escape(Protocol::MAIL)
		);

		if (!DBA::isResult($contacts)) {
			return;
		}

		foreach ($contacts as $contact) {

			if ($manual_id) {
				$contact['last-update'] = DBA::NULL_DATETIME;
			}

			// Friendica and OStatus are checked once a day
			if (in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS])) {
				$contact['priority'] = 2;
			}

			if ($contact['subhub'] && in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS])) {
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
			if (($contact["network"] == Protocol::DIASPORA) || ($contact["rel"] == Contact::FOLLOWER)) {
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
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 1 month")) {
							$update = true;
						}
						break;
					case 4:
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 1 week")) {
							$update = true;
						}
						break;
					case 3:
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 1 day")) {
							$update = true;
						}
						break;
					case 2:
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 12 hour")) {
							$update = true;
						}
						break;
					case 1:
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + 1 hour")) {
							$update = true;
						}
						break;
					case 0:
					default:
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + ".$min_poll_interval." minute")) {
							$update = true;
						}
						break;
				}
				if (!$update) {
					continue;
				}
			}

			if (($contact['network'] == Protocol::FEED) && ($contact['priority'] <= 3)) {
				$priority = PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = PRIORITY_NEGLIGIBLE;
			} else {
				$priority = PRIORITY_LOW;
			}

			Logger::log("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true, 'force_priority' => true], 'OnePoll', (int)$contact['id']);
		}
	}
}

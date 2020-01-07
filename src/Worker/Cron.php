<?php
/**
 * @file src/Worker/Cron.php
 */
namespace Friendica\Worker;

use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Registry\DI;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

class Cron
{
	public static function execute()
	{
		$a = DI::app();

		$last = Config::get('system', 'last_cron');

		$poll_interval = intval(Config::get('system', 'cron_interval'));

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

		// run the process to update server directories in the background
		Worker::add(PRIORITY_LOW, 'UpdateServerDirectories');

		// run the process to update locally stored global contacts in the background
		Worker::add(PRIORITY_LOW, 'UpdateGContacts');

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

			// update nodeinfo data
			Worker::add(PRIORITY_LOW, "CronJobs", "nodeinfo");

			Worker::add(PRIORITY_LOW, 'UpdateGServers');

			Worker::add(PRIORITY_LOW, 'UpdateSuggestions');

			Worker::add(PRIORITY_LOW, 'Expire');

			Worker::add(PRIORITY_MEDIUM, 'DBClean');

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'CheckVersion');

			self::checkdeletedContacts();

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
		if (!file_exists($basepath . '/.htaccess') && is_writable($basepath)) {
			copy($basepath . '/.htaccess-dist', $basepath . '/.htaccess');
		}

		// Poll contacts
		self::pollContacts();

		// Update contact information
		self::updatePublicContacts();

		Logger::log('cron: end');

		Config::set('system', 'last_cron', time());

		return;
	}

	/**
	 * Checks for contacts that are about to be deleted and ensures that they are removed.
	 * This should be done automatically in the "remove" function. This here is a cleanup job.
	 */
	private static function checkdeletedContacts()
	{
		$contacts = DBA::select('contact', ['id'], ['deleted' => true]);
		while ($contact = DBA::fetch($contacts)) {
			Worker::add(PRIORITY_MEDIUM, 'RemoveContact', $contact['id']);
		}
		DBA::close($contacts);
	}

	/**
	 * @brief Update public contacts
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updatePublicContacts() {
		$count = 0;
		$last_updated = DateTimeFormat::utc('now - 1 week');
		$condition = ["`network` IN (?, ?, ?, ?) AND `uid` = ? AND NOT `self` AND `last-update` < ?",
			Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, 0, $last_updated];

		$total = DBA::count('contact', $condition);
		$oldest_date = '';
		$oldest_id = '';
		$contacts = DBA::select('contact', ['id', 'last-update'], $condition, ['limit' => 100, 'order' => ['last-update']]);
		while ($contact = DBA::fetch($contacts)) {
			if (empty($oldest_id)) {
				$oldest_id = $contact['id'];
				$oldest_date = $contact['last-update'];
			}
			Worker::add(PRIORITY_LOW, "UpdateContact", $contact['id'], 'force');
			++$count;
		}
		Logger::info('Initiated update for public contacts', ['interval' => $count, 'total' => $total, 'id' => $oldest_id, 'oldest' => $oldest_date]);
		DBA::close($contacts);
	}

	/**
	 * @brief Poll contacts for unreceived messages
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function pollContacts() {
		$min_poll_interval = Config::get('system', 'min_poll_interval', 1);

		Addon::reload();

		$sql = "SELECT `contact`.`id`, `contact`.`nick`, `contact`.`name`, `contact`.`network`, `contact`.`archive`,
					`contact`.`last-update`, `contact`.`priority`, `contact`.`rel`, `contact`.`subhub`
				FROM `user`
				STRAIGHT_JOIN `contact`
				ON `contact`.`uid` = `user`.`uid` AND `contact`.`poll` != ''
					AND `contact`.`network` IN (?, ?, ?, ?)
					AND NOT `contact`.`self` AND NOT `contact`.`blocked`
					AND `contact`.`rel` != ?
				WHERE NOT `user`.`account_expired` AND NOT `user`.`account_removed`";

		$parameters = [Protocol::DFRN, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL, Contact::FOLLOWER];

		// Only poll from those with suitable relationships,
		// and which have a polling address and ignore Diaspora since
		// we are unable to match those posts with a Diaspora GUID and prevent duplicates.
		$abandon_days = intval(Config::get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		if (!empty($abandon_days)) {
			$sql .= " AND `user`.`login_date` > UTC_TIMESTAMP() - INTERVAL ? DAY";
			$parameters[] = $abandon_days;
		}

		$contacts = DBA::p($sql, $parameters);

		if (!DBA::isResult($contacts)) {
			return;
		}

		while ($contact = DBA::fetch($contacts)) {
			// Friendica and OStatus are checked once a day
			if (in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS])) {
				$contact['priority'] = 3;
			}

			// Check archived contacts once a month
			if ($contact['archive']) {
				$contact['priority'] = 5;
			}

			if ($contact['priority'] >= 0) {
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
						if (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . " + " . $min_poll_interval . " minute")) {
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
		DBA::close($contacts);
	}
}

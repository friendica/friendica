<?php

/**
 * @file src/worker/CronJobs.php
 */

namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Photo;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use dba;

require_once 'include/dba.php';
require_once 'include/post_update.php';
require_once 'mod/nodeinfo.php';

class CronJobs
{
	public static function execute($command = '')
	{
		global $a;

		require_once 'include/datetime.php';
		require_once 'include/post_update.php';
		require_once 'mod/nodeinfo.php';

		// No parameter set? So return
		if ($command == '') {
			return;
		}

		logger("Starting cronjob " . $command, LOGGER_DEBUG);

		// Call possible post update functions
		// see include/post_update.php for more details
		if ($command == 'post_update') {
			post_update();
			return;
		}

		// update nodeinfo data
		if ($command == 'nodeinfo') {
			nodeinfo_cron();
			return;
		}

		// Expire and remove user entries
		if ($command == 'expire_and_remove_users') {
			self::expireAndRemoveUsers();
			return;
		}

		if ($command == 'update_contact_birthdays') {
			update_contact_birthdays();
			return;
		}

		if ($command == 'update_photo_albums') {
			self::updatePhotoAlbums();
			return;
		}

		// Clear cache entries
		if ($command == 'clear_cache') {
			self::clearCache($a);
			return;
		}

		// Repair missing Diaspora values in contacts
		if ($command == 'repair_diaspora') {
			self::repairDiaspora($a);
			return;
		}

		// Repair entries in the database
		if ($command == 'repair_database') {
			self::repairDatabase();
			return;
		}

		logger("Xronjob " . $command . " is unknown.", LOGGER_DEBUG);

		return;
	}

	/**
	 * @brief Update the cached values for the number of photo albums per user
	 */
	private static function updatePhotoAlbums()
	{
		$r = q("SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`");
		if (!DBM::isResult($r)) {
			return;
		}

		foreach ($r as $user) {
			Photo::clearAlbumCache($user['uid']);
		}
	}

	/**
	 * @brief Expire and remove user entries
	 */
	private static function expireAndRemoveUsers()
	{
		// expire any expired accounts
		q("UPDATE user SET `account_expired` = 1 where `account_expired` = 0
			AND `account_expires_on` > '%s'
			AND `account_expires_on` < UTC_TIMESTAMP()", dbesc(NULL_DATE));

		// delete user records for recently removed accounts
		$r = q("SELECT * FROM `user` WHERE `account_removed` AND `account_expires_on` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
		if (DBM::isResult($r)) {
			foreach ($r as $user) {
				dba::delete('user', ['uid' => $user['uid']]);
			}
		}
	}

	/**
	 * @brief Clear cache entries
	 *
	 * @param App $a
	 */
	private static function clearCache(App $a)
	{
		$last = Config::get('system', 'cache_last_cleared');

		if ($last) {
			$next = $last + (3600); // Once per hour
			$clear_cache = ($next <= time());
		} else {
			$clear_cache = true;
		}

		if (!$clear_cache) {
			return;
		}

		// clear old cache
		Cache::clear();

		// clear old item cache files
		clear_cache();

		// clear cache for photos
		clear_cache($a->get_basepath(), $a->get_basepath() . "/photo");

		// clear smarty cache
		clear_cache($a->get_basepath() . "/view/smarty3/compiled", $a->get_basepath() . "/view/smarty3/compiled");

		// clear cache for image proxy
		if (!Config::get("system", "proxy_disabled")) {
			clear_cache($a->get_basepath(), $a->get_basepath() . "/proxy");

			$cachetime = Config::get('system', 'proxy_cache_time');
			if (!$cachetime) {
				$cachetime = PROXY_DEFAULT_TIME;
			}
			$condition = ['`uid` = 0 AND `resource-id` LIKE "pic:%" AND `created` < NOW() - INTERVAL ? SECOND', $cachetime];
			dba::delete('photo', $condition);
		}

		// Delete the cached OEmbed entries that are older than three month
		dba::delete('oembed', ["`created` < NOW() - INTERVAL 3 MONTH"]);

		// Delete the cached "parse_url" entries that are older than three month
		dba::delete('parsed_url', ["`created` < NOW() - INTERVAL 3 MONTH"]);

		// Maximum table size in megabyte
		$max_tablesize = intval(Config::get('system', 'optimize_max_tablesize')) * 1000000;
		if ($max_tablesize == 0) {
			$max_tablesize = 100 * 1000000; // Default are 100 MB
		}
		if ($max_tablesize > 0) {
			// Minimum fragmentation level in percent
			$fragmentation_level = intval(Config::get('system', 'optimize_fragmentation')) / 100;
			if ($fragmentation_level == 0) {
				$fragmentation_level = 0.3; // Default value is 30%
			}

			// Optimize some tables that need to be optimized
			$r = q("SHOW TABLE STATUS");
			foreach ($r as $table) {

				// Don't optimize tables that are too large
				if ($table["Data_length"] > $max_tablesize) {
					continue;
				}

				// Don't optimize empty tables
				if ($table["Data_length"] == 0) {
					continue;
				}

				// Calculate fragmentation
				$fragmentation = $table["Data_free"] / ($table["Data_length"] + $table["Index_length"]);

				logger("Table " . $table["Name"] . " - Fragmentation level: " . round($fragmentation * 100, 2), LOGGER_DEBUG);

				// Don't optimize tables that needn't to be optimized
				if ($fragmentation < $fragmentation_level) {
					continue;
				}

				// So optimize it
				logger("Optimize Table " . $table["Name"], LOGGER_DEBUG);
				q("OPTIMIZE TABLE `%s`", dbesc($table["Name"]));
			}
		}

		Config::set('system', 'cache_last_cleared', time());
	}

	/**
	 * @brief Repair missing values in Diaspora contacts
	 *
	 * @param App $a
	 */
	private static function repairDiaspora(App $a)
	{
		$starttime = time();

		$r = q("SELECT `id`, `url` FROM `contact`
			WHERE `network` = '%s' AND (`batch` = '' OR `notify` = '' OR `poll` = '' OR pubkey = '')
				ORDER BY RAND() LIMIT 50", dbesc(NETWORK_DIASPORA));
		if (!DBM::isResult($r)) {
			return;
		}

		foreach ($r AS $contact) {
			// Quit the loop after 3 minutes
			if (time() > ($starttime + 180)) {
				return;
			}

			if (!PortableContact::reachable($contact["url"])) {
				continue;
			}

			$data = Probe::uri($contact["url"]);
			if ($data["network"] != NETWORK_DIASPORA) {
				continue;
			}

			logger("Repair contact " . $contact["id"] . " " . $contact["url"], LOGGER_DEBUG);
			q("UPDATE `contact` SET `batch` = '%s', `notify` = '%s', `poll` = '%s', pubkey = '%s' WHERE `id` = %d",
				dbesc($data["batch"]), dbesc($data["notify"]), dbesc($data["poll"]), dbesc($data["pubkey"]),
				intval($contact["id"]));
		}
	}

	/**
	 * @brief Do some repairs in database entries
	 *
	 */
	private static function repairDatabase()
	{
		// Sometimes there seem to be issues where the "self" contact vanishes.
		// We haven't found the origin of the problem by now.
		$r = q("SELECT `uid` FROM `user` WHERE NOT EXISTS (SELECT `uid` FROM `contact` WHERE `contact`.`uid` = `user`.`uid` AND `contact`.`self`)");
		if (DBM::isResult($r)) {
			foreach ($r AS $user) {
				logger('Create missing self contact for user ' . $user['uid']);
				Contact::createSelfFromUserId($user['uid']);
			}
		}

		// Set the parent if it wasn't set. (Shouldn't happen - but does sometimes)
		// This call is very "cheap" so we can do it at any time without a problem
		q("UPDATE `item` INNER JOIN `item` AS `parent` ON `parent`.`uri` = `item`.`parent-uri` AND `parent`.`uid` = `item`.`uid` SET `item`.`parent` = `parent`.`id` WHERE `item`.`parent` = 0");

		// There was an issue where the nick vanishes from the contact table
		q("UPDATE `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid` SET `nick` = `nickname` WHERE `self` AND `nick`=''");

		// Update the global contacts for local users
		$r = q("SELECT `uid` FROM `user` WHERE `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`");
		if (DBM::isResult($r)) {
			foreach ($r AS $user) {
				GContact::updateForUser($user["uid"]);
			}
		}

		/// @todo
		/// - remove thread entries without item
		/// - remove sign entries without item
		/// - remove children when parent got lost
		/// - set contact-id in item when not present
	}
}

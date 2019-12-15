<?php
/**
 * @file src/worker/CronJobs.php
 */
namespace Friendica\Worker;

use Friendica\App;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\StorageManager;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\PostUpdate;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Model\Nodeinfo;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Util\Network;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;

class CronJobs
{
	public static function execute($command = '')
	{
		$a = DI::app();

		// No parameter set? So return
		if ($command == '') {
			return;
		}

		Logger::log("Starting cronjob " . $command, Logger::DEBUG);

		switch($command) {
			case 'post_update':
				PostUpdate::update();
				break;

			case 'nodeinfo':
				Logger::info('cron_start');
				Nodeinfo::update();
				// Now trying to register
				$url = 'http://the-federation.info/register/' . DI::baseUrl()->getHostname();
				Logger::debug('Check registering url', ['url' => $url]);
				$ret = Network::fetchUrl($url);
				Logger::debug('Check registering answer', ['answer' => $ret]);
				Logger::info('cron_end');
				break;

			case 'expire_and_remove_users':
				self::expireAndRemoveUsers();
				break;

			case 'update_contact_birthdays':
				Contact::updateBirthdays();
				break;

			case 'update_photo_albums':
				self::updatePhotoAlbums();
				break;

			case 'clear_cache':
				self::clearCache($a);
				break;

			case 'repair_diaspora':
				self::repairDiaspora($a);
				break;

			case 'repair_database':
				self::repairDatabase();
				break;

			case 'move_storage':
				self::moveStorage();
				break;

			default:
				Logger::log("Cronjob " . $command . " is unknown.", Logger::DEBUG);
		}

		return;
	}

	/**
	 * @brief Update the cached values for the number of photo albums per user
	 */
	private static function updatePhotoAlbums()
	{
		$r = q("SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`");
		if (!DBA::isResult($r)) {
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
		// expire any expired regular accounts. Don't expire forums.
		$condition = ["NOT `account_expired` AND `account_expires_on` > ? AND `account_expires_on` < UTC_TIMESTAMP() AND `page-flags` = 0", DBA::NULL_DATETIME];
		DBA::update('user', ['account_expired' => true], $condition);

		// Remove any freshly expired account
		$users = DBA::select('user', ['uid'], ['account_expired' => true, 'account_removed' => false]);
		while ($user = DBA::fetch($users)) {
			User::remove($user['uid']);
		}

		// delete user records for recently removed accounts
		$users = DBA::select('user', ['uid'], ["`account_removed` AND `account_expires_on` < UTC_TIMESTAMP() "]);
		while ($user = DBA::fetch($users)) {
			// Delete the contacts of this user
			$self = DBA::selectFirst('contact', ['nurl'], ['self' => true, 'uid' => $user['uid']]);
			if (DBA::isResult($self)) {
				DBA::delete('contact', ['nurl' => $self['nurl'], 'self' => false]);
			}

			DBA::delete('user', ['uid' => $user['uid']]);
		}
	}

	/**
	 * @brief Clear cache entries
	 *
	 * @param App $a
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
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
		clear_cache($a->getBasePath(), $a->getBasePath() . "/photo");

		// clear smarty cache
		clear_cache($a->getBasePath() . "/view/smarty3/compiled", $a->getBasePath() . "/view/smarty3/compiled");

		// clear cache for image proxy
		if (!Config::get("system", "proxy_disabled")) {
			clear_cache($a->getBasePath(), $a->getBasePath() . "/proxy");

			$cachetime = Config::get('system', 'proxy_cache_time');

			if (!$cachetime) {
				$cachetime = ProxyUtils::DEFAULT_TIME;
			}

			$condition = ['`uid` = 0 AND `resource-id` LIKE "pic:%" AND `created` < NOW() - INTERVAL ? SECOND', $cachetime];
			Photo::delete($condition);
		}

		// Delete the cached OEmbed entries that are older than three month
		DBA::delete('oembed', ["`created` < NOW() - INTERVAL 3 MONTH"]);

		// Delete the cached "parse_url" entries that are older than three month
		DBA::delete('parsed_url', ["`created` < NOW() - INTERVAL 3 MONTH"]);

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

				Logger::log("Table " . $table["Name"] . " - Fragmentation level: " . round($fragmentation * 100, 2), Logger::DEBUG);

				// Don't optimize tables that needn't to be optimized
				if ($fragmentation < $fragmentation_level) {
					continue;
				}

				// So optimize it
				Logger::log("Optimize Table " . $table["Name"], Logger::DEBUG);
				q("OPTIMIZE TABLE `%s`", DBA::escape($table["Name"]));
			}
		}

		Config::set('system', 'cache_last_cleared', time());
	}

	/**
	 * @brief Repair missing values in Diaspora contacts
	 *
	 * @param App $a
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function repairDiaspora(App $a)
	{
		$starttime = time();

		$r = q("SELECT `id`, `url` FROM `contact`
			WHERE `network` = '%s' AND (`batch` = '' OR `notify` = '' OR `poll` = '' OR pubkey = '')
				ORDER BY RAND() LIMIT 50", DBA::escape(Protocol::DIASPORA));
		if (!DBA::isResult($r)) {
			return;
		}

		foreach ($r as $contact) {
			// Quit the loop after 3 minutes
			if (time() > ($starttime + 180)) {
				return;
			}

			if (!GServer::reachable($contact["url"])) {
				continue;
			}

			$data = Probe::uri($contact["url"]);
			if ($data["network"] != Protocol::DIASPORA) {
				continue;
			}

			Logger::log("Repair contact " . $contact["id"] . " " . $contact["url"], Logger::DEBUG);
			q("UPDATE `contact` SET `batch` = '%s', `notify` = '%s', `poll` = '%s', pubkey = '%s' WHERE `id` = %d",
				DBA::escape($data["batch"]), DBA::escape($data["notify"]), DBA::escape($data["poll"]), DBA::escape($data["pubkey"]),
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
		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				Logger::log('Create missing self contact for user ' . $user['uid']);
				Contact::createSelfFromUserId($user['uid']);
			}
		}

		// There was an issue where the nick vanishes from the contact table
		q("UPDATE `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid` SET `nick` = `nickname` WHERE `self` AND `nick`=''");

		// Update the global contacts for local users
		$r = q("SELECT `uid` FROM `user` WHERE `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`");
		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				GContact::updateForUser($user["uid"]);
			}
		}

		/// @todo
		/// - remove thread entries without item
		/// - remove sign entries without item
		/// - remove children when parent got lost
		/// - set contact-id in item when not present

		// Add intro entries for pending contacts
		// We don't do this for DFRN entries since such revived contact requests seem to mostly fail.
		$pending_contacts = DBA::p("SELECT `uid`, `id`, `url`, `network`, `created` FROM `contact`
			WHERE `pending` AND `rel` IN (?, ?) AND `network` != ?
				AND NOT EXISTS (SELECT `id` FROM `intro` WHERE `contact-id` = `contact`.`id`)",
			0, Contact::FOLLOWER, Protocol::DFRN);
		while ($contact = DBA::fetch($pending_contacts)) {
			DBA::insert('intro', ['uid' => $contact['uid'], 'contact-id' => $contact['id'], 'blocked' => false,
				'hash' => Strings::getRandomHex(), 'datetime' => $contact['created']]);
		}
		DBA::close($pending_contacts);
	}

	/**
	 * Moves up to 5000 attachments and photos to the current storage system.
	 * Self-replicates if legacy items have been found and moved.
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function moveStorage()
	{
		$current = StorageManager::getBackend();
		$moved = StorageManager::move($current);

		if ($moved) {
			Worker::add(PRIORITY_LOW, "CronJobs", "move_storage");
		}
	}
}

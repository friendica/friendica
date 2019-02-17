<?php
/**
 * @file src/worker/CronJobs.php
 */
namespace Friendica\Worker;

use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Database\PostUpdate;
use Friendica\Model\Contact;
use Friendica\Model\GContact;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\Proxy as ProxyUtils;

require_once 'mod/nodeinfo.php';

class CronJobs extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 1)) {
			return;
		}

		list($command) = $parameters;

		// No parameter set? So return
		if ($command == '') {
			return;
		}

		$this->logger->info('Start.', ['cmd' => 'cronjob', 'action' => $command]);

		// Call possible post update functions
		// see src/Database/PostUpdate.php for more details
		if ($command == 'post_update') {
			PostUpdate::update();
			return;
		}

		// update nodeinfo data
		if ($command == 'nodeinfo') {
			nodeinfo_cron();
			return;
		}

		// Expire and remove user entries
		if ($command == 'expire_and_remove_users') {
			$this->expireAndRemoveUsers();
			return;
		}

		if ($command == 'update_contact_birthdays') {
			Contact::updateBirthdays();
			return;
		}

		if ($command == 'update_photo_albums') {
			$this->updatePhotoAlbums();
			return;
		}

		// Clear cache entries
		if ($command == 'clear_cache') {
			$this->clearCache();
			return;
		}

		// Repair missing Diaspora values in contacts
		if ($command == 'repair_diaspora') {
			$this->repairDiaspora();
			return;
		}

		// Repair entries in the database
		if ($command == 'repair_database') {
			$this->repairDatabase();
			return;
		}

		$this->logger->info('Cronjob is unknown.', ['cmd' => 'cronjob', 'action' => $command]);

		return;
	}

	/**
	 * @brief Update the cached values for the number of photo albums per user
	 */
	private function updatePhotoAlbums()
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
	private function expireAndRemoveUsers()
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function clearCache()
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

		$basePath = $this->app->getBasePath();

		// clear cache for photos
		clear_cache($basePath, $basePath . "/photo");

		// clear smarty cache
		clear_cache($basePath . "/view/smarty3/compiled", $basePath . "/view/smarty3/compiled");

		// clear cache for image proxy
		if (!Config::get("system", "proxy_disabled")) {
			clear_cache($basePath, $basePath . "/proxy");

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

				$this->logger->info("Table " . $table["Name"] . " - Fragmentation level: " . round($fragmentation * 100, 2));

				// Don't optimize tables that needn't to be optimized
				if ($fragmentation < $fragmentation_level) {
					continue;
				}

				// So optimize it
				$logger->info("Optimize Table " . $table["Name"]);
				q("OPTIMIZE TABLE `%s`", DBA::escape($table["Name"]));
			}
		}

		Config::set('system', 'cache_last_cleared', time());
	}

	/**
	 * @brief Repair missing values in Diaspora contacts
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private function repairDiaspora()
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

			if (!PortableContact::reachable($contact["url"])) {
				continue;
			}

			$data = Probe::uri($contact["url"]);
			if ($data["network"] != Protocol::DIASPORA) {
				continue;
			}

			$this->logger->info("Repair contact " . $contact["id"] . " " . $contact["url"]);
			q("UPDATE `contact` SET `batch` = '%s', `notify` = '%s', `poll` = '%s', pubkey = '%s' WHERE `id` = %d",
				DBA::escape($data["batch"]), DBA::escape($data["notify"]), DBA::escape($data["poll"]), DBA::escape($data["pubkey"]),
				intval($contact["id"]));
		}
	}

	/**
	 * @brief Do some repairs in database entries
	 */
	private function repairDatabase()
	{
		// Sometimes there seem to be issues where the "self" contact vanishes.
		// We haven't found the origin of the problem by now.
		$r = q("SELECT `uid` FROM `user` WHERE NOT EXISTS (SELECT `uid` FROM `contact` WHERE `contact`.`uid` = `user`.`uid` AND `contact`.`self`)");
		if (DBA::isResult($r)) {
			foreach ($r AS $user) {
				$this->logger->info('Create missing self contact for user ' . $user['uid']);
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
	}
}

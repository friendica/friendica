<?php
use \Friendica\Core\Config;

function cronjobs_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/ostatus.php');
	require_once('include/post_update.php');
	require_once('mod/nodeinfo.php');
	require_once('include/photos.php');
	require_once('include/user.php');
	require_once('include/socgraph.php');
	require_once('include/Probe.php');

	// No parameter set? So return
	if ($argc <= 1) {
		return;
	}

	logger("Starting cronjob ".$argv[1], LOGGER_DEBUG);

	// Check OStatus conversations
	// Check only conversations with mentions (for a longer time)
	if ($argv[1] == 'ostatus_mentions') {
		ostatus::check_conversations(true);
		return;
	}

	// Check every conversation
	if ($argv[1] == 'ostatus_conversations') {
		ostatus::check_conversations(false);
		return;
	}

	// Call possible post update functions
	// see include/post_update.php for more details
	if ($argv[1] == 'post_update') {
		post_update();
		return;
	}

	// update nodeinfo data
	if ($argv[1] == 'nodeinfo') {
		nodeinfo_cron();
		return;
	}

	// Expire and remove user entries
	if ($argv[1] == 'expire_and_remove_users') {
		cron_expire_and_remove_users();
		return;
	}

	if ($argv[1] == 'update_contact_birthdays') {
		update_contact_birthdays();
		return;
	}

	if ($argv[1] == 'update_photo_albums') {
		cron_update_photo_albums();
		return;
	}

	// Clear cache entries
	if ($argv[1] == 'clear_cache') {
		cron_clear_cache($a);
		return;
	}

	// Repair missing Diaspora values in contacts
	if ($argv[1] == 'repair_diaspora') {
		cron_repair_diaspora($a);
		return;
	}

	// Repair entries in the database
	if ($argv[1] == 'repair_database') {
		cron_repair_database();
		return;
	}

	logger("Xronjob ".$argv[1]." is unknown.", LOGGER_DEBUG);

	return;
}

/**
 * @brief Update the cached values for the number of photo albums per user
 */
function cron_update_photo_albums() {
	$r = q("SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`");
	if (!dbm::is_result($r)) {
		return;
	}

	foreach ($r AS $user) {
		photo_albums($user['uid'], true);
	}
}

/**
 * @brief Expire and remove user entries
 */
function cron_expire_and_remove_users() {
	// expire any expired accounts
	q("UPDATE user SET `account_expired` = 1 where `account_expired` = 0
		AND `account_expires_on` > '%s'
		AND `account_expires_on` < UTC_TIMESTAMP()", dbesc(NULL_DATE));

	// delete user and contact records for recently removed accounts
	$r = q("SELECT * FROM `user` WHERE `account_removed` AND `account_expires_on` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	if (dbm::is_result($r)) {
		foreach ($r as $user) {
			q("DELETE FROM `contact` WHERE `uid` = %d", intval($user['uid']));
			q("DELETE FROM `user` WHERE `uid` = %d", intval($user['uid']));
		}
	}
}

/**
 * @brief Clear cache entries
 *
 * @param App $a
 */
function cron_clear_cache(App $a) {

	$last = get_config('system','cache_last_cleared');

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
	clear_cache($a->get_basepath(), $a->get_basepath()."/photo");

	// clear smarty cache
	clear_cache($a->get_basepath()."/view/smarty3/compiled", $a->get_basepath()."/view/smarty3/compiled");

	// clear cache for image proxy
	if (!get_config("system", "proxy_disabled")) {
		clear_cache($a->get_basepath(), $a->get_basepath()."/proxy");

		$cachetime = get_config('system','proxy_cache_time');
		if (!$cachetime) {
			$cachetime = PROXY_DEFAULT_TIME;
		}
		q('DELETE FROM `photo` WHERE `uid` = 0 AND `resource-id` LIKE "pic:%%" AND `created` < NOW() - INTERVAL %d SECOND', $cachetime);
	}

	// Delete the cached OEmbed entries that are older than one year
	q("DELETE FROM `oembed` WHERE `created` < NOW() - INTERVAL 3 MONTH");

	// Delete the cached "parse_url" entries that are older than one year
	q("DELETE FROM `parsed_url` WHERE `created` < NOW() - INTERVAL 3 MONTH");

	// Maximum table size in megabyte
	$max_tablesize = intval(get_config('system','optimize_max_tablesize')) * 1000000;
	if ($max_tablesize == 0) {
		$max_tablesize = 100 * 1000000; // Default are 100 MB
	}
	if ($max_tablesize > 0) {
		// Minimum fragmentation level in percent
		$fragmentation_level = intval(get_config('system','optimize_fragmentation')) / 100;
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

			logger("Table ".$table["Name"]." - Fragmentation level: ".round($fragmentation * 100, 2), LOGGER_DEBUG);

			// Don't optimize tables that needn't to be optimized
			if ($fragmentation < $fragmentation_level) {
				continue;
			}

			// So optimize it
			logger("Optimize Table ".$table["Name"], LOGGER_DEBUG);
			q("OPTIMIZE TABLE `%s`", dbesc($table["Name"]));
		}
	}

	set_config('system','cache_last_cleared', time());
}

/**
 * @brief Repair missing values in Diaspora contacts
 *
 * @param App $a
 */
function cron_repair_diaspora(App $a) {

        $starttime = time();

	$r = q("SELECT `id`, `url` FROM `contact`
		WHERE `network` = '%s' AND (`batch` = '' OR `notify` = '' OR `poll` = '' OR pubkey = '')
			ORDER BY RAND() LIMIT 50", dbesc(NETWORK_DIASPORA));
	if (!dbm::is_result($r)) {
		return;
	}

	foreach ($r AS $contact) {
		// Quit the loop after 3 minutes
		if (time() > ($starttime + 180)) {
			return;
		}

		if (!poco_reachable($contact["url"])) {
			continue;
		}

		$data = Probe::uri($contact["url"]);
		if ($data["network"] != NETWORK_DIASPORA) {
			continue;
		}

		logger("Repair contact ".$contact["id"]." ".$contact["url"], LOGGER_DEBUG);
		q("UPDATE `contact` SET `batch` = '%s', `notify` = '%s', `poll` = '%s', pubkey = '%s' WHERE `id` = %d",
			dbesc($data["batch"]), dbesc($data["notify"]), dbesc($data["poll"]), dbesc($data["pubkey"]),
			intval($contact["id"]));
	}
}

/**
 * @brief Do some repairs in database entries
 *
 */
function cron_repair_database() {

	// Sometimes there seem to be issues where the "self" contact vanishes.
	// We haven't found the origin of the problem by now.
	$r = q("SELECT `uid` FROM `user` WHERE NOT EXISTS (SELECT `uid` FROM `contact` WHERE `contact`.`uid` = `user`.`uid` AND `contact`.`self`)");
	if (dbm::is_result($r)) {
		foreach ($r AS $user) {
			logger('Create missing self contact for user '.$user['uid']);
			user_create_self_contact($user['uid']);
		}
	}

	// Set the parent if it wasn't set. (Shouldn't happen - but does sometimes)
	// This call is very "cheap" so we can do it at any time without a problem
	q("UPDATE `item` INNER JOIN `item` AS `parent` ON `parent`.`uri` = `item`.`parent-uri` AND `parent`.`uid` = `item`.`uid` SET `item`.`parent` = `parent`.`id` WHERE `item`.`parent` = 0");

	// There was an issue where the nick vanishes from the contact table
	q("UPDATE `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid` SET `nick` = `nickname` WHERE `self` AND `nick`=''");

	// Update the global contacts for local users
	$r = q("SELECT `uid` FROM `user` WHERE `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`");
	if (dbm::is_result($r)) {
		foreach ($r AS $user) {
			update_gcontact_for_user($user["uid"]);
		}
	}

	/// @todo
	/// - remove thread entries without item
	/// - remove sign entries without item
	/// - remove children when parent got lost
	/// - set contact-id in item when not present
}

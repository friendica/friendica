<?php
/**
 * @file update.php
 */
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Photo;
use Friendica\Object\Image;

/**
 *
 * update.php - automatic system update
 *
 * This function is responsible for doing post update changes to the data
 * (not the structure) in the database.
 *
 * Database structure changes are done in src/Database/DBStructure.php
 *
 * If there is a need for a post process to a structure change, update this file
 * by adding a new function at the end with the number of the new DB_UPDATE_VERSION.
 *
 * The numbered script in this file has to be exactly like the DB_UPDATE_VERSION
 *
 * Example:
 * You are currently on version 4711 and you are preparing changes that demand an update script.
 *
 * 1. Create a function "update_4712()" here in the update.php
 * 2. Apply the needed structural changes in src/Database/DBStructure.php
 * 3. Set DB_UPDATE_VERSION in boot.php to 4712.
 */

function update_1178() {
	require_once 'mod/profiles.php';

	$profiles = q("SELECT `uid`, `about`, `locality`, `pub_keywords`, `gender` FROM `profile` WHERE `is-default`");

	foreach ($profiles AS $profile) {
		if ($profile["about"].$profile["locality"].$profile["pub_keywords"].$profile["gender"] == "")
			continue;

		$profile["pub_keywords"] = profile_clean_keywords($profile["pub_keywords"]);

		$r = q("UPDATE `contact` SET `about` = '%s', `location` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `self` AND `uid` = %d",
				dbesc($profile["about"]),
				dbesc($profile["locality"]),
				dbesc($profile["pub_keywords"]),
				dbesc($profile["gender"]),
				intval($profile["uid"])
			);
	}
}

function update_1179() {
	if (Config::get('system','no_community_page'))
		Config::set('system','community_page_style', CP_NO_COMMUNITY_PAGE);

	// Update the central item storage with uid=0
	Worker::add(PRIORITY_LOW, "threadupdate");

	return UPDATE_SUCCESS;
}

function update_1181() {

	// Fill the new fields in the term table.
	Worker::add(PRIORITY_LOW, "TagUpdate");

	return UPDATE_SUCCESS;
}

function update_1189() {

	if (strlen(Config::get('system','directory_submit_url')) &&
		!strlen(Config::get('system','directory'))) {
		Config::set('system','directory', dirname(Config::get('system','directory_submit_url')));
		Config::delete('system','directory_submit_url');
	}

	return UPDATE_SUCCESS;
}

function update_1191()
{
	Config::set('system', 'maintenance', 1);

	if (Addon::isEnabled('forumlist')) {
		$addon = 'forumlist';
		$addons = Config::get('system', 'addon');
		$addons_arr = [];

		if ($addons) {
			$addons_arr = explode(",", str_replace(" ", "", $addons));

			$idx = array_search($addon, $addons_arr);
			if ($idx !== false) {
				unset($addons_arr[$idx]);
				//delete forumlist manually from addon and hook table
				// since uninstall_plugin() don't work here
				q("DELETE FROM `addon` WHERE `name` = 'forumlist' ");
				q("DELETE FROM `hook` WHERE `file` = 'addon/forumlist/forumlist.php' ");
				Config::set('system', 'addon', implode(", ", $addons_arr));
			}
		}
	}

	// select old formlist addon entries
	$r = q("SELECT `uid`, `cat`, `k`, `v` FROM `pconfig` WHERE `cat` = '%s' ",
		dbesc('forumlist')
	);

	// convert old forumlist addon entries in new config entries
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$uid = $rr['uid'];
			$family = $rr['cat'];
			$key = $rr['k'];
			$value = $rr['v'];

			if ($key === 'randomise')
				PConfig::delete($uid,$family,$key);

			if ($key === 'show_on_profile') {
				if ($value)
					PConfig::set($uid,feature,forumlist_profile,$value);

				PConfig::delete($uid,$family,$key);
			}

			if ($key === 'show_on_network') {
				if ($value)
					PConfig::set($uid,feature,forumlist_widget,$value);

				PConfig::delete($uid,$family,$key);
			}
		}
	}

	Config::set('system', 'maintenance', 0);

	return UPDATE_SUCCESS;

}

function update_1203() {
	$r = q("UPDATE `user` SET `account-type` = %d WHERE `page-flags` IN (%d, %d)",
		dbesc(ACCOUNT_TYPE_COMMUNITY), dbesc(PAGE_COMMUNITY), dbesc(PAGE_PRVGROUP));
}

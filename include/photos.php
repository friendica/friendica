<?php
/**
 * @file include/photos.php
 * @brief Functions related to photo handling.
 */

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

function getGps($exifCoord, $hemi) {
	$degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
	$minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
	$seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

	$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

	return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
}

function gps2Num($coordPart) {
	$parts = explode('/', $coordPart);

	if (count($parts) <= 0)
		return 0;

	if (count($parts) == 1)
		return $parts[0];

	return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * @brief Fetch the photo albums that are available for a viewer
 *
 * The query in this function is cost intensive, so it is cached.
 *
 * @param int $uid User id of the photos
 * @param bool $update Update the cache
 *
 * @return array Returns array of the photo albums
 */
function photo_albums($uid, $update = false) {
	$sql_extra = permissions_sql($uid);

	$key = "photo_albums:".$uid.":".local_user().":".remote_user();
	$albums = Cache::get($key);
	if (is_null($albums) OR $update) {
		if (!Config::get('system', 'no_count', false)) {
			/// @todo This query needs to be renewed. It is really slow
			// At this time we just store the data in the cache
			$albums = qu("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`
				FROM `photo` USE INDEX (`uid_album_created`)
				WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
				GROUP BY `album` ORDER BY `created` DESC",
				intval($uid),
				dbesc('Contact Photos'),
				dbesc(t('Contact Photos'))
			);
		} else {
			// This query doesn't do the count and is much faster
			$albums = qu("SELECT DISTINCT(`album`), '' AS `total`
				FROM `photo` USE INDEX (`uid_album_created`)
				WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
				GROUP BY `album` ORDER BY `created` DESC",
				intval($uid),
				dbesc('Contact Photos'),
				dbesc(t('Contact Photos'))
			);
		}
		Cache::set($key, $albums, CACHE_DAY);
	}
	return $albums;
}

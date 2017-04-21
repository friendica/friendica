<?php

define('UPDATE_VERSION' , 1220);

/**
 *
 * update.php - automatic system update
 *
 * Automatically update database schemas and any other development changes such that
 * copying the latest files from the source code repository will always perform a clean
 * and painless upgrade.
 *
 * Each function in this file is named update_nnnn() where nnnn is an increasing number
 * which began counting at 1000.
 *
 * At the top of the file "boot.php" is a define for DB_UPDATE_VERSION. Any time there is a change
 * to the database schema or one which requires an upgrade path from the existing application,
 * the DB_UPDATE_VERSION and the UPDATE_VERSION at the top of this file are incremented.
 *
 * The current DB_UPDATE_VERSION is stored in the config area of the database. If the application starts up
 * and DB_UPDATE_VERSION is greater than the last stored build number, we will process every update function
 * in order from the currently stored value to the new DB_UPDATE_VERSION. This is expected to bring the system
 * up to current without requiring re-installation or manual intervention.
 *
 * Once the upgrade functions have completed, the current DB_UPDATE_VERSION is stored as the current value.
 * The DB_UPDATE_VERSION will always be one greater than the last numbered script in this file.
 *
 * If you change the database schema, the following are required:
 *    1. Update the file include/dbstructure.php to match the new schema.
 *    2. If there is a need for a post procession, update this file by adding a new function at the end with the number of the current DB_UPDATE_VERSION.
 *       This function should perform some post procession steps but no database updates.
 *    3. Increment the DB_UPDATE_VERSION in boot.php *AND* the UPDATE_VERSION in this file to match it
 *    4. TEST the upgrade prior to checkin and filing a pull request.
 *
 * IMPORTANT!
 * NEVER do a database change anymore in the update functions! Only do this in the file include/dbstructure.php!
 */



function update_1000() {

	q("ALTER TABLE `item` DROP `like`, DROP `dislike` ");

	q("ALTER TABLE `item` ADD `verb` CHAR( 255 ) NOT NULL AFTER `body` ,
		ADD `object-type` CHAR( 255 ) NOT NULL AFTER `verb` ,
		ADD `object` TEXT NOT NULL AFTER `object-type` ");

	q("ALTER TABLE `intro` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `knowyou` ");
	q("ALTER TABLE `contact` ADD `duplex` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `rel` ");
 	q("ALTER TABLE `contact` CHANGE `issued-pubkey` `issued-pubkey` TEXTCHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
	q("ALTER TABLE `contact` ADD `term-date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `avatar-date`");
}

function update_1001() {
	q("ALTER TABLE `item` ADD `wall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type` ");
	q("ALTER TABLE `item` ADD INDEX ( `wall` )");
}

function update_1002() {
	q("ALTER TABLE `item` ADD `gravity` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `wall` ");
}

function update_1003() {
	q("ALTER TABLE `contact` DROP `issued-pubkey` , DROP `ret-id` , DROP `ret-pubkey` ");
	q("ALTER TABLE `contact` ADD `usehub` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `ret-aes`");
	q("ALTER TABLE `contact` ADD `hub-verify` CHAR( 255 ) NOT NULL AFTER `usehub`");
	q("ALTER TABLE `contact` ADD INDEX ( `uid` ) ,  ADD INDEX ( `self` ),  ADD INDEX ( `issued-id` ),  ADD INDEX ( `dfrn-id` )");
	q("ALTER TABLE `contact` ADD INDEX ( `blocked` ),   ADD INDEX ( `readonly` )");
}

function update_1004() {
	q("ALTER TABLE `contact` ADD `subhub` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `usehub`");
}

function update_1005() {

	q("ALTER TABLE `user` ADD `spubkey` TEXT NOT NULL AFTER `prvkey` ,
		ADD `sprvkey` TEXT NOT NULL AFTER `spubkey`");

}

function update_1006() {

	// create 's' keys for everybody that does not have one

	$r = q("SELECT * FROM `user` WHERE `spubkey` = '' ");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$sres=openssl_pkey_new(array('encrypt_key' => false ));
			$sprvkey = '';
			openssl_pkey_export($sres, $sprvkey);
			$spkey = openssl_pkey_get_details($sres);
			$spubkey = $spkey["key"];
			$r = q("UPDATE `user` SET `spubkey` = '%s', `sprvkey` = '%s'
				WHERE `uid` = %d",
				dbesc($spubkey),
				dbesc($sprvkey),
				intval($rr['uid'])
			);
		}
	}
}

function update_1007() {
	q("ALTER TABLE `user` ADD `page-flags` INT NOT NULL DEFAULT '0' AFTER `notify-flags`");
	q("ALTER TABLE `user` ADD INDEX ( `nickname` )");
}

function update_1008() {
	q("ALTER TABLE `profile` ADD `with` TEXT NOT NULL AFTER `marital` ");
}

function update_1009() {
	q("ALTER TABLE `user` ADD `allow_location` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `default-location` ");
}

function update_1010() {
	q("ALTER TABLE `contact` ADD `lrdd` CHAR( 255 ) NOT NULL AFTER `url` ");
}

function update_1011() {
	q("ALTER TABLE `contact` ADD `nick` CHAR( 255 ) NOT NULL AFTER `name` ");
	$r = q("SELECT * FROM `contact` WHERE 1");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
				q("UPDATE `contact` SET `nick` = '%s' WHERE `id` = %d",
					dbesc(basename($rr['url'])),
					intval($rr['id'])
				);
		}
	}
}

function update_1012() {
	q("ALTER TABLE `item` ADD `inform` MEDIUMTEXT NOT NULL AFTER `tag` ");
}

function update_1013() {
	q("ALTER TABLE `item` ADD `target-type` CHAR( 255 ) NOT NULL
		AFTER `object` , ADD `target` TEXT NOT NULL AFTER `target-type`");
}

function update_1014() {
	require_once('include/Photo.php');
	q("ALTER TABLE `contact` ADD `micro` TEXT NOT NULL AFTER `thumb` ");
	$r = q("SELECT * FROM `photo` WHERE `scale` = 4");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$ph = new Photo($rr['data']);
			if ($ph->is_valid()) {
				$ph->scaleImage(48);
				$ph->store($rr['uid'],$rr['contact-id'],$rr['resource-id'],$rr['filename'],$rr['album'],6,(($rr['profile']) ? 1 : 0));
			}
		}
	}
	$r = q("SELECT * FROM `contact` WHERE 1");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			if(stristr($rr['thumb'],'avatar'))
				q("UPDATE `contact` SET `micro` = '%s' WHERE `id` = %d",
					dbesc(str_replace('avatar','micro',$rr['thumb'])),
					intval($rr['id']));
			else
				q("UPDATE `contact` SET `micro` = '%s' WHERE `id` = %d",
					dbesc(str_replace('5.jpg','6.jpg',$rr['thumb'])),
					intval($rr['id']));
		}
	}
}

function update_1015() {
	q("ALTER TABLE `item` CHANGE `body` `body` mediumtext NOT NULL");
}

function update_1016() {
	q("ALTER TABLE `user` ADD `openid` CHAR( 255 ) NOT NULL AFTER `email` ");
}

function update_1017() {

	q(" CREATE TABLE IF NOT EXISTS `clients` (
`client_id` VARCHAR( 20 ) NOT NULL ,
`pw` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `client_id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q(" CREATE TABLE IF NOT EXISTS `tokens` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 200 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q("CREATE TABLE IF NOT EXISTS `auth_codes` (
`id` VARCHAR( 40 ) NOT NULL ,
`client_id` VARCHAR( 20 ) NOT NULL ,
`redirect_uri` VARCHAR( 200 ) NOT NULL ,
`expires` INT NOT NULL ,
`scope` VARCHAR( 250 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

}

function update_1018() {
	q("CREATE TABLE IF NOT EXISTS `queue` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`cid` INT NOT NULL ,
`created` DATETIME NOT NULL ,
`last` DATETIME NOT NULL ,
`content` MEDIUMTEXT NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}

function update_1019() {
	q("ALTER TABLE `mail` DROP `delivered`");
	q("ALTER TABLE `profile` ADD `showwith` TINYINT(1) NOT NULL DEFAULT '0' AFTER `marital` ");
}

function update_1020() {
	q("ALTER TABLE `profile` DROP `showwith`");
	q("ALTER TABLE `item` ADD `thr-parent` CHAR( 255 ) NOT NULL AFTER `parent-uri` ");
}

function update_1021() {
	q("ALTER TABLE `profile_check` ADD `sec` CHAR( 255 ) NOT NULL AFTER `dfrn_id` ");
	q("ALTER TABLE `profile_check` ADD `cid` INT(10) unsigned  NOT NULL DEFAULT '0' AFTER `uid`");
	q("ALTER TABLE `item` ADD `private` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `deny_gid` ");
}

function update_1022() {
	q("CREATE TABLE `pconfig` (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`uid` INT NOT NULL DEFAULT '0',
		`cat` CHAR( 255 ) NOT NULL ,
		`k` CHAR( 255 ) NOT NULL ,
		`v` MEDIUMTEXT NOT NULL
		) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci ");
}

function update_1023() {
	q("ALTER TABLE `user` ADD `register_date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `timezone` ,
	ADD `login_date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `register_date` ");
}

function update_1024() {
	q("ALTER TABLE `profile` ADD `keywords` TEXT NOT NULL AFTER `religion` ");
}

function update_1025() {
	q("ALTER TABLE `user` ADD `maxreq` int(11) NOT NULL DEFAULT '10' AFTER `pwdreset` ");
}

function update_1026() {
	q("CREATE TABLE IF NOT EXISTS `hook` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`hook` CHAR( 255 ) NOT NULL ,
	`file` CHAR( 255 ) NOT NULL ,
	`function` CHAR( 255 ) NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}


function update_1027() {
	q("CREATE TABLE IF NOT EXISTS `addon` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` CHAR( 255 ) NOT NULL ,
	`version` CHAR( 255 ) NOT NULL ,
	`installed` TINYINT( 1 ) NOT NULL DEFAULT '0'
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
}

function update_1028() {
	q("ALTER TABLE `user` ADD `openidserver` text NOT NULL AFTER `deny_gid` ");
}

function update_1029() {
	q("ALTER TABLE `contact` ADD `info` MEDIUMTEXT NOT NULL AFTER `reason` ");
}

function update_1030() {
	q("ALTER TABLE `contact` ADD `bdyear` CHAR( 4 ) NOT NULL COMMENT 'birthday notify flag' AFTER `profile-id` ");

	q("CREATE TABLE IF NOT EXISTS `event` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`cid` INT NOT NULL ,
	`created` DATETIME NOT NULL ,
	`edited` DATETIME NOT NULL ,
	`start` DATETIME NOT NULL ,
	`finish` DATETIME NOT NULL ,
	`desc` TEXT NOT NULL ,
	`location` TEXT NOT NULL ,
	`type` CHAR( 255 ) NOT NULL ,
	`adjust` TINYINT( 1 ) NOT NULL DEFAULT '1',
	`allow_cid` MEDIUMTEXT NOT NULL ,
	`allow_gid` MEDIUMTEXT NOT NULL ,
	`deny_cid` MEDIUMTEXT NOT NULL ,
	`deny_gid` MEDIUMTEXT NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");


}

function update_1031() {
	// Repair any bad links that slipped into the item table
	$r = q("SELECT `id`, `object` FROM `item` WHERE `object` != '' ");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			if (strstr($rr['object'],'type=&quot;http')) {
				q("UPDATE `item` SET `object` = '%s' WHERE `id` = %d",
					dbesc(str_replace('type=&quot;http','href=&quot;http',$rr['object'])),
					intval($rr['id'])
				);
			}
		}
	}
}

function update_1032() {
	q("ALTER TABLE `profile` ADD `pdesc` CHAR( 255 ) NOT NULL AFTER `name` ");
}

function update_1033() {
	q("CREATE TABLE IF NOT EXISTS `cache` (
		`k` CHAR( 255 ) NOT NULL PRIMARY KEY ,
		`v` TEXT NOT NULL,
		`updated` DATETIME NOT NULL
		) DEFAULT CHARSET=utf8 ");
}


function update_1034() {

	/*
	 * If you have any of these parent-less posts they can cause problems, and
	 * we need to delete them. You can't see them anyway.
	 * Legitimate items will usually get re-created on the next
	 * pull from the hub.
	 * But don't get rid of a post that may have just come in
	 * and may not yet have the parent id set.
	 */
	q("DELETE FROM `item` WHERE `parent` = 0 AND `created` < UTC_TIMESTAMP() - INTERVAL 2 MINUTE");

}


function update_1035() {

	q("ALTER TABLE `contact` ADD `success_update` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `last-update` ");

}

function update_1036() {

	$r = dbq("SELECT * FROM `contact` WHERE `network` = 'dfrn' && `photo` LIKE '%include/photo%' ");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			q("UPDATE `contact` SET `photo` = '%s', `thumb` = '%s', `micro` = '%s' WHERE `id` = %d",
				dbesc(str_replace('include/photo','photo',$rr['photo'])),
				dbesc(str_replace('include/photo','photo',$rr['thumb'])),
				dbesc(str_replace('include/photo','photo',$rr['micro'])),
				intval($rr['id']));
		}
	}
}

function update_1037() {

	q("ALTER TABLE `contact` CHANGE `lrdd` `alias` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");

}

function update_1038() {
 q("ALTER TABLE `item` ADD `plink` CHAR( 255 ) NOT NULL AFTER `target` ");
}

function update_1039() {
	q("ALTER TABLE `addon` ADD `timestamp` BIGINT NOT NULL DEFAULT '0'");
}


function update_1040() {

	q("CREATE TABLE IF NOT EXISTS `fcontact` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`url` CHAR( 255 ) NOT NULL ,
	`name` CHAR( 255 ) NOT NULL ,
	`photo` CHAR( 255 ) NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

	q("CREATE TABLE IF NOT EXISTS `ffinder` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT UNSIGNED NOT NULL ,
	`cid` INT UNSIGNED NOT NULL ,
	`fid` INT UNSIGNED NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");

}

function update_1041() {
	q("ALTER TABLE `profile` CHANGE `keywords` `prv_keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");
	q("ALTER TABLE `profile` ADD `pub_keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `religion` ");
}

function update_1042() {
	q("ALTER TABLE `user` ADD `expire` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `maxreq` ");
}


function update_1043() {
	q("ALTER TABLE `user` ADD `blockwall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `blocked` ");
}

function update_1044() {
	q("ALTER TABLE `profile` ADD FULLTEXT ( `pub_keywords` ) ");
	q("ALTER TABLE `profile` ADD FULLTEXT ( `prv_keywords` ) ");
}

function update_1045() {
	q("ALTER TABLE `user` ADD `language` CHAR( 16 ) NOT NULL DEFAULT 'en' AFTER `timezone` ");
}

function update_1046() {
	q("ALTER TABLE `item` ADD `attach` MEDIUMTEXT NOT NULL AFTER `tag` ");
}

function update_1047() {
	q("ALTER TABLE `contact` ADD `writable` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `readonly` ");
}

function update_1048() {
	q("UPDATE `contact` SET `writable` = 1 WHERE `network` = 'stat' AND `notify` != '' ");
}

function update_1049() {
	q("CREATE TABLE `mailacct` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL,
	`server` CHAR( 255 ) NOT NULL ,
	`user` CHAR( 255 ) NOT NULL ,
	`pass` CHAR( 255 ) NOT NULL ,
	`reply_to` CHAR( 255 ) NOT NULL ,
	`last_check` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00'
	) ENGINE = MYISAM ");
}

function update_1050() {
	q("CREATE TABLE `attach` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`filetype` CHAR( 64 ) NOT NULL ,
	`filesize` INT NOT NULL ,
	`data` LONGBLOB NOT NULL ,
	`created` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
	`edited` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
	`allow_cid` MEDIUMTEXT NOT NULL ,
	`allow_gid` MEDIUMTEXT NOT NULL ,
	`deny_cid` MEDIUMTEXT NOT NULL ,
	`deny_gid` MEDIUMTEXT NOT NULL
	) ENGINE = MYISAM ");

}

function update_1051() {
	q("ALTER TABLE `mailacct` ADD `port` INT NOT NULL AFTER `server` ,
		ADD `ssltype` CHAR( 16 ) NOT NULL AFTER `port` ,
		ADD `mailbox` CHAR( 255 ) NOT NULL AFTER `ssltype` ");

	q("ALTER TABLE `contact` ADD `addr` CHAR( 255 ) NOT NULL AFTER `url` ");
}

function update_1052() {
	q("ALTER TABLE `mailacct` CHANGE `pass` `pass` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
	q("ALTER TABLE `mailacct` ADD `pubmail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `reply_to` ");
	q("ALTER TABLE `item` ADD `pubmail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `private` ");
}


function update_1053() {
	q("ALTER TABLE `item` ADD `extid` CHAR( 255 ) NOT NULL AFTER `parent-uri` , ADD INDEX ( `extid` ) ");
}

function update_1054() {
	q("ALTER TABLE `register` ADD `language` CHAR( 16 ) NOT NULL AFTER `password` ");
}

function update_1055() {
	q("ALTER TABLE `profile` ADD `hidewall` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `hide-friends` ");
}

function update_1056() {
	q("ALTER TABLE `attach` ADD `hash` CHAR( 64 ) NOT NULL AFTER `uid` ");
}

function update_1057() {
	q("ALTER TABLE `attach` ADD `filename` CHAR( 255 ) NOT NULL AFTER `hash` ");
}

function update_1058() {
	q("ALTER TABLE `item` ADD `event-id` INT NOT NULL AFTER `resource-id` ");
}

function update_1059() {
	q("ALTER TABLE `queue` ADD `network` CHAR( 32 ) NOT NULL AFTER `cid` ");
}

function update_1060() {
	q("ALTER TABLE `event` ADD `uri` CHAR( 255 ) NOT NULL AFTER `cid` ");
}

function update_1061() {
	q("ALTER TABLE `event` ADD `nofinish` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type` ");
}

function update_1062() {
	q("ALTER TABLE `user` ADD `prvnets` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `page-flags` ");
}
function update_1063() {
	q("ALTER TABLE `addon` ADD `plugin_admin` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `timestamp` ");
}

function update_1064() {
	q("ALTER TABLE `item` ADD `app` CHAR( 255 ) NOT NULL AFTER `body` ");
}

function update_1065() {
	q("ALTER TABLE `intro` ADD `fid` INT NOT NULL DEFAULT '0' AFTER `uid`");
}

function update_1066() {
	$r = q("ALTER TABLE `item` ADD `received` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `edited` ");
	if($r)
		q("ALTER TABLE `item` ADD INDEX ( `received` ) ");

	$r = q("UPDATE `item` SET `received` = `edited` WHERE 1");
}

function update_1067() {
	q("ALTER TABLE `ffinder` ADD `type` CHAR( 16 ) NOT NULL AFTER `id` ,
	ADD `note` TEXT NOT NULL AFTER `type` ");
}

function update_1068() {
	// 1067 was short-sighted. Undo it.
	q("ALTER TABLE `ffinder` DROP `type` , DROP `note` ");

	// and do this instead.

	q("CREATE TABLE IF NOT EXISTS `fsuggest` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`cid` INT NOT NULL ,
	`name` CHAR( 255 ) NOT NULL ,
	`url` CHAR( 255 ) NOT NULL ,
	`photo` CHAR( 255 ) NOT NULL ,
	`note` TEXT NOT NULL ,
	`created` DATETIME NOT NULL
	) ENGINE = MYISAM DEFAULT CHARSET=utf8");

}

function update_1069() {
	q("ALTER TABLE `fsuggest` ADD `request` CHAR( 255 ) NOT NULL AFTER `url` ");
	q("ALTER TABLE `fcontact` ADD `request` CHAR( 255 ) NOT NULL AFTER `photo` ");
}

// mail body needs to accomodate private photos

function update_1070() {
	q("ALTER TABLE `mail` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ");
}

function update_1071() {
	q("ALTER TABLE `photo` ADD INDEX ( `uid` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `resource-id` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `album` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `scale` ) ");
	q("ALTER TABLE `photo` ADD INDEX ( `profile` ) ");

}

function update_1072() {
	q("ALTER TABLE `item` ADD `starred` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `visible` ");
	q("ALTER TABLE `item` ADD INDEX ( `starred` ) ");
}

function update_1073() {
	q("ALTER TABLE `contact` ADD `remote_self` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `self` ");
}

function update_1074() {
	q("ALTER TABLE `user` ADD `hidewall` TINYINT( 1) NOT NULL DEFAULT '0' AFTER `blockwall` ");
	$r = q("SELECT `uid` FROM `profile` WHERE `is-default` = 1 AND `hidewall` = 1");
	if (dbm::is_result($r)) {
		foreach($r as $rr)
			q("UPDATE `user` SET `hidewall` = 1 WHERE `uid` = %d",
				intval($rr['uid'])
			);
	}
	q("ALTER TABLE `profile` DROP `hidewall`");
}

function update_1075() {
	q("ALTER TABLE `user` ADD `guid` CHAR( 16 ) NOT NULL AFTER `uid` ");
	$r = q("SELECT `uid` FROM `user` WHERE 1");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$found = true;
			do {
				$guid = substr(random_string(),0,16);
				$x = q("SELECT `uid` FROM `user` WHERE `guid` = '%s' LIMIT 1",
					dbesc($guid)
				);
				if(! count($x))
					$found = false;
			} while ($found == true );

			q("UPDATE `user` SET `guid` = '%s' WHERE `uid` = %d",
				dbesc($guid),
				intval($rr['uid'])
			);
		}
	}
}

function update_1076() {
	q("CREATE TABLE `guid` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`guid` CHAR( 16 ) NOT NULL , INDEX ( `guid` ) ) ENGINE = MYISAM ");

}

// There was a typo in 1076 so we'll try again in 1077 to make sure
// We'll also make it big enough to allow for future growth, I seriously
// doubt Diaspora will be able to leave guids at 16 bytes,
// and we can also use the same structure for our own larger guids

function update_1077() {
	q("CREATE TABLE IF NOT EXISTS `guid` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`guid` CHAR( 16 ) NOT NULL , INDEX ( `guid` ) ) ENGINE = MYISAM ");

	q("ALTER TABLE `guid` CHANGE `guid` `guid` CHAR( 64 ) NOT NULL");
}

function update_1078() {
	q("ALTER TABLE `item` ADD `guid` CHAR( 64 ) NOT NULL AFTER `id` , ADD INDEX ( `guid` ) ");
}

function update_1079() {
	q("CREATE TABLE IF NOT EXISTS `sign` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`iid` INT UNSIGNED NOT NULL ,
	`signed_text` MEDIUMTEXT NOT NULL ,
	`signature` TEXT NOT NULL ,
	`signer` CHAR( 255 ) NOT NULL ,
	INDEX ( `iid` )
	) ENGINE = MYISAM ");

	q("ALTER TABLE `fcontact` ADD `nick` CHAR( 255 ) NOT NULL ,
	ADD `addr` CHAR( 255 ) NOT NULL ,
	ADD `notify` CHAR( 255 ) NOT NULL ,
	ADD `poll` CHAR( 255 ) NOT NULL ,
	ADD `confirm` CHAR( 255 ) NOT NULL ,
	ADD `priority` TINYINT( 1 ) NOT NULL ,
	ADD `network` CHAR( 32 ) NOT NULL ,
	ADD `alias` CHAR( 255 ) NOT NULL ,
	ADD `pubkey` TEXT NOT NULL ,
	ADD INDEX ( `addr` ) ,
	ADD INDEX ( `network` ) ");

}

function update_1080() {
	q("ALTER TABLE `fcontact` ADD `updated` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00'");
}

function update_1081() {
	// there was a typo in update 1081 so it was corrected and moved up to 1082
}

function update_1082() {
	q("ALTER TABLE `photo` ADD `guid` CHAR( 64 ) NOT NULL AFTER `contact-id`,
		ADD INDEX ( `guid` )  ");
	// make certain the following code is only executed once
	$r = q("select `id` from `photo` where `guid` != '' limit 1");
	if (dbm::is_result($r))
		return;
	$r = q("SELECT distinct(`resource-id`) FROM `photo` WHERE 1 group by `id`");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$guid = get_guid();
			q("update `photo` set `guid` = '%s' where `resource-id` = '%s'",
				dbesc($guid),
				dbesc($rr['resource-id'])
			);
		}
	}
}

function update_1083() {
	q("CREATE TABLE IF NOT EXISTS `deliverq` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`cmd` CHAR( 32 ) NOT NULL ,
	`item` INT NOT NULL ,
	`contact` INT NOT NULL
	) ENGINE = MYISAM ");

}

function update_1084() {
	q("ALTER TABLE `contact` ADD `attag` CHAR( 255 ) NOT NULL AFTER `nick` ");
}

function update_1085() {
	q("CREATE TABLE IF NOT EXISTS `search` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`term` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	INDEX ( `uid` ),
	INDEX ( `term` )
	) ENGINE = MYISAM ");
}

function update_1086() {
	q("ALTER TABLE `item` ADD `bookmark` tinyint(1) NOT NULL DEFAULT '0' AFTER `starred` ");
}

function update_1087() {
	q("ALTER TABLE `item` ADD `commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `edited` ");

	$r = q("SELECT `id` FROM `item` WHERE `parent` = `id` ");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$x = q("SELECT max(`created`) AS `cdate` FROM `item` WHERE `parent` = %d LIMIT 1",
				intval($rr['id'])
			);
			if(count($x))
				q("UPDATE `item` SET `commented` = '%s' WHERE `id` = %d",
					dbesc($x[0]['cdate']),
					intval($rr['id'])
				);
		}
	}
}

function update_1088() {
	q("ALTER TABLE `user` ADD `account_expired` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `expire` ,
		ADD `account_expires_on` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `account_expired` ,
		ADD `expire_notification_sent` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `account_expires_on` ");
}

function update_1089() {
	q("ALTER TABLE `user` ADD `blocktags` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `hidewall` ");
}

function update_1090() {
	q("ALTER TABLE `contact` ADD `batch` char(255) NOT NULL AFTER `prvkey` ");

	q("UPDATE `contact` SET `batch` = concat(substring_index(`url`,'/',3),'/receive/public') WHERE `network` = 'dspr' ");

}

function update_1091() {

	// catch a few stragglers that may have crept in before we added this on remote connects
	q("UPDATE `contact` SET `batch` = concat(substring_index(`url`,'/',3),'/receive/public') WHERE `network` = 'dspr' AND `batch` = '' ");
	q("ALTER TABLE `queue` ADD `batch` TINYINT( 1 ) NOT NULL DEFAULT '0' ");
	q("ALTER TABLE `fcontact` ADD `batch` char(255) NOT NULL AFTER `addr` ");

}

function update_1092() {
	q("ALTER TABLE `user` ADD INDEX ( `login_date` ) ");
	q("ALTER TABLE `user` ADD INDEX ( `account_expired` ) ");
}

function update_1093() {
	q("CREATE TABLE IF NOT EXISTS `fserver` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`server` CHAR( 255 ) NOT NULL ,
	`posturl` CHAR( 255 ) NOT NULL ,
	`key` TEXT NOT NULL,
	INDEX ( `server` )
	) ENGINE = MYISAM ");

	q("ALTER TABLE `group` ADD `visible` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `uid` ");

}

function update_1094() {
	q("ALTER TABLE `item` ADD `postopts` TEXT NOT NULL AFTER `target` ");
}

function update_1095() {
	q("ALTER TABLE `contact` ADD `bd` DATE NOT NULL AFTER `bdyear` ");
}

function update_1096() {
	q("ALTER TABLE `item` ADD `origin` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `deleted` , ADD INDEX ( `origin` ) ");
}

function update_1097() {
	q("ALTER TABLE `queue`
		ADD INDEX (`cid`),
		ADD INDEX (`created`),
		ADD INDEX (`last`),
		ADD INDEX (`network`),
		ADD INDEX (`batch`)
	");
}

function update_1098() {
	q("ALTER TABLE `contact`
		ADD INDEX (`network`),
		ADD INDEX (`name`),
		ADD INDEX (`nick`),
		ADD INDEX (`attag`),
		ADD INDEX (`url`),
		ADD INDEX (`addr`),
		ADD INDEX (`batch`)
	");
}

function update_1099() {
	q("CREATE TABLE IF NOT EXISTS `gcontact` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`name` CHAR( 255 ) NOT NULL ,
	`url` CHAR( 255 ) NOT NULL ,
	`nurl` CHAR( 255 ) NOT NULL ,
	`photo` CHAR( 255 ) NOT NULL
	) ENGINE = MYISAM ");

	q("CREATE TABLE IF NOT EXISTS `glink` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`cid` INT NOT NULL ,
	`uid` INT NOT NULL ,
	`gcid` INT NOT NULL,
	`updated` DATETIME NOT NULL
	) ENGINE = MYISAM ");

	q("ALTER TABLE `gcontact` ADD INDEX (`nurl`) ");
	q("ALTER TABLE `glink` ADD INDEX (`cid`), ADD INDEX (`uid`), ADD INDEX (`gcid`), ADD INDEX (`updated`) ");

	q("ALTER TABLE `contact` ADD `poco` TEXT NOT NULL AFTER `confirm` ");

}

function update_1100() {
	q("ALTER TABLE `contact` ADD `nurl` CHAR( 255 ) NOT NULL AFTER `url` ");
	q("alter table contact add index (`nurl`) ");

	require_once('include/text.php');

	$r = q("select id, url from contact where url != '' and nurl = '' ");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			q("update contact set nurl = '%s' where id = %d",
				dbesc(normalise_link($rr['url'])),
				intval($rr['id'])
			);
		}
	}
}


function update_1101() {
	q("CREATE TABLE IF NOT EXISTS `gcign` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`gcid` INT NOT NULL
	) ENGINE = MYISAM ");

	q("ALTER TABLE `gcign` ADD INDEX (`uid`), ADD INDEX (`gcid`) ");
}

function update_1102() {
	q("ALTER TABLE `clients` ADD `name` TEXT NULL DEFAULT NULL AFTER `redirect_uri` ");
	q("ALTER TABLE `clients` ADD `icon` TEXT NULL DEFAULT NULL AFTER `name` ");
	q("ALTER TABLE `clients` ADD `uid` INT NOT NULL DEFAULT 0 AFTER `icon` ");

	q("ALTER TABLE `tokens` ADD `secret` TEXT NOT NULL AFTER `id` ");
	q("ALTER TABLE `tokens` ADD `uid` INT NOT NULL AFTER `scope` ");
}


function update_1103() {
//	q("ALTER TABLE `item` ADD INDEX ( `wall` ) ");
	q("ALTER TABLE `item` ADD FULLTEXT ( `tag` ) ");
	q("ALTER TABLE `contact` ADD INDEX ( `pending` ) ");
	q("ALTER TABLE `user` ADD INDEX ( `hidewall` ) ");
	q("ALTER TABLE `user` ADD INDEX ( `blockwall` ) ");
	q("ALTER TABLE `user` ADD INDEX ( `blocked` ) ");
	q("ALTER TABLE `user` ADD INDEX ( `verified` ) ");

}

function update_1104() {
	q("ALTER TABLE `item` ADD `forum_mode` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `origin` , ADD INDEX ( `forum_mode` ) ");

}

function update_1105() {
	q("ALTER TABLE `mail` ADD `convid` INT NOT NULL AFTER `contact-id` ");
	q("ALTER TABLE `mail` ADD `guid` CHAR( 64 ) NOT NULL AFTER `uid` ");

	q("CREATE TABLE IF NOT EXISTS `conv` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`guid` CHAR( 64 ) NOT NULL ,
	`recips` MEDIUMTEXT NOT NULL ,
	`uid` INT NOT NULL
	) ENGINE = MYISAM ");
}


function update_1106() {
	q("ALTER TABLE `item` ADD INDEX ( `author-link` ) ");

}

function update_1107() {
	q("ALTER TABLE `item` ADD INDEX ( `bookmark` ) ");

}

function update_1108() {
	q("ALTER TABLE `contact` ADD `hidden` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `writable` ,
ADD INDEX ( `hidden` ) ");

}

function update_1109() {
	q("ALTER TABLE `conv` ADD `creator` CHAR( 255 ) NOT NULL ,
		ADD `created` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
		ADD `updated` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00',
		ADD `subject` MEDIUMTEXT NOT NULL,
		ADD INDEX ( `created` ), ADD INDEX ( `updated` ) ");
}

function update_1110() {
	q("ALTER TABLE `mail` ADD `reply` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `seen`,
		ADD INDEX ( `reply` ), ADD INDEX ( `uid` ), ADD INDEX ( `guid` ), ADD INDEX ( `seen` ),
		ADD INDEX ( `uri` ), ADD INDEX ( `parent-uri`), ADD INDEX ( `created` ), ADD INDEX ( `convid` ) ");

}

function update_1111() {
	q("ALTER TABLE `gcontact` ADD `connect` CHAR( 255 ) NOT NULL ");
}


function update_1112() {

	q("CREATE TABLE IF NOT EXISTS `notify` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`type` INT( 11 ) NOT NULL ,
`name` CHAR( 255 ) NOT NULL ,
`url` CHAR( 255 ) NOT NULL ,
`photo` CHAR( 255 ) NOT NULL ,
`date` DATETIME NOT NULL ,
`msg` MEDIUMTEXT NOT NULL ,
`uid` INT NOT NULL ,
`link` CHAR( 255 ) NOT NULL ,
`seen` TINYINT( 1 ) NOT NULL DEFAULT '0'
) ENGINE = MYISAM ");

	q("ALTER TABLE `notify` ADD INDEX ( `type` ), ADD INDEX ( `uid`), ADD INDEX (`seen`), ADD INDEX (`date`) ");

}

function update_1113() {
	q("ALTER TABLE `notify` ADD `verb` CHAR( 255 ) NOT NULL ,
ADD `otype` CHAR( 16 ) NOT NULL");
}

function update_1114() {
	q("CREATE TABLE IF NOT EXISTS `item_id` (
`iid` INT NOT NULL ,
`uid` INT NOT NULL ,
`face` CHAR( 255 ) NOT NULL ,
`dspr` CHAR( 255 ) NOT NULL ,
`twit` CHAR( 255 ) NOT NULL ,
`stat` CHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `iid` ),
INDEX ( `uid` ),
INDEX ( `face` ),
INDEX ( `dspr` ),
INDEX ( `twit` ),
INDEX ( `stat` )
) ENGINE = MYISAM ");

}

function update_1115() {
	q("ALTER TABLE `item` ADD `moderated`
		TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `pubmail`,
		ADD INDEX (`moderated`) ");
}

function update_1116() {
	//typo! corrected update was rolled forward
}

function update_1117() {
q("create table if not exists `manage` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`uid` INT NOT NULL ,
`mid` INT NOT NULL,
INDEX ( `uid` ),
INDEX ( `mid` )
) ENGINE = MYISAM ");

}

function update_1118() {
	// rolled forward
}

function update_1119() {
q("ALTER TABLE `contact` ADD `closeness` TINYINT( 2 ) NOT NULL DEFAULT '99' AFTER `reason` , ADD INDEX (`closeness`) ");
q("update contact set closeness = 0 where self = 1");
q("ALTER TABLE `item` ADD `spam` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `visible` , ADD INDEX (`spam`) ");
}


function update_1120() {

	// item table update from 1119 did not get into database.sql file.
	// might be missing on new installs. We'll check.

	$r = q("describe item");
	if (dbm::is_result($r)) {
		foreach($r as $rr)
			if($rr['Field'] == 'spam')
				return;
	}
	q("ALTER TABLE `item` ADD `spam` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `visible` , ADD INDEX (`spam`) ");

}

function update_1121() {
	q("CREATE TABLE IF NOT EXISTS `poll_result` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`poll_id` INT NOT NULL ,
	`choice` INT NOT NULL ,
	INDEX ( `poll_id` ),
	INDEX ( `choice` )
	) ENGINE = MYISAM ");

	q("CREATE TABLE IF NOT EXISTS `poll` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`uid` INT NOT NULL ,
	`q0` MEDIUMTEXT NOT NULL ,
	`q1` MEDIUMTEXT NOT NULL ,
	`q2` MEDIUMTEXT NOT NULL ,
	`q3` MEDIUMTEXT NOT NULL ,
	`q4` MEDIUMTEXT NOT NULL ,
	`q5` MEDIUMTEXT NOT NULL ,
	`q6` MEDIUMTEXT NOT NULL ,
	`q7` MEDIUMTEXT NOT NULL ,
	`q8` MEDIUMTEXT NOT NULL ,
	`q9` MEDIUMTEXT NOT NULL ,
	INDEX ( `uid` )
	) ENGINE = MYISAM ");

}

function update_1122() {
q("ALTER TABLE `notify` ADD `hash` CHAR( 64 ) NOT NULL AFTER `id` ,
ADD INDEX ( `hash` ) ");
}

function update_1123() {
set_config('system','allowed_themes','dispy,quattro,testbubble,vier,darkbubble,darkzero,duepuntozero,greenzero,purplezero,quattro-green,slackr');
}

function update_1124() {
q("alter table item add index (`author-name`) ");
}

function update_1125() {
  q("CREATE TABLE IF NOT EXISTS `notify-threads` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `notify-id` INT NOT NULL,
  `master-parent-item` INT( 10 ) unsigned NOT NULL DEFAULT '0',
  `parent-item` INT( 10 ) unsigned NOT NULL DEFAULT '0',
  `receiver-uid` INT NOT NULL,
  INDEX ( `master-parent-item` ),
  INDEX ( `receiver-uid` )
  ) ENGINE = MyISAM DEFAULT CHARSET=utf8");
}

function update_1126() {
	q("ALTER TABLE `mailacct` ADD `action` INT NOT NULL AFTER `pass`,
		ADD `movetofolder` CHAR(255) NOT NULL AFTER `action`");
}

function update_1127() {
	q("CREATE TABLE IF NOT EXISTS `spam` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  `uid` INT NOT NULL,
  `spam` INT NOT NULL DEFAULT '0',
  `ham` INT NOT NULL DEFAULT '0',
  `term` CHAR(255) NOT NULL,
  INDEX ( `uid` ),
  INDEX ( `spam` ),
  INDEX ( `ham` ),
  INDEX ( `term` )
  ) ENGINE = MyISAM DEFAULT CHARSET=utf8");
}


function update_1128() {
	q("alter table spam add `date` DATETIME NOT NULL DEFAULT '0001-01-01 00:00:00' AFTER `term` ");
}

function update_1129() {
	q("ALTER TABLE `notify` ADD `parent` INT NOT NULL AFTER `link` , ADD INDEX ( `parent` ), ADD INDEX ( `link` ), ADD INDEX ( `otype` ) ");
}

function update_1130() {
	q("ALTER TABLE `item` ADD `file` MEDIUMTEXT NOT NULL AFTER `inform`, ADD FULLTEXT KEY (`file`) ");
}

function update_1131() {
	q("ALTER TABLE `contact` ADD `forum` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `writable` , ADD INDEX ( `forum` ) ");
}


function update_1132() {
	q("CREATE TABLE IF NOT EXISTS `userd` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`username` CHAR( 255 ) NOT NULL,
INDEX ( `username` )
) ENGINE = MYISAM ");

}

function update_1133() {
q("ALTER TABLE `user` ADD `unkmail` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `blocktags` , ADD INDEX ( `unkmail` ) ");
q("ALTER TABLE `user` ADD `cntunkmail` INT NOT NULL DEFAULT '10' AFTER `unkmail` , ADD INDEX ( `cntunkmail` ) ");
q("ALTER TABLE `mail` ADD `unknown` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `replied` , ADD INDEX ( `unknown` ) ");
}

function update_1134() {
	// faulty update merged forward
	// had a hardwired tablename of 'friendica' which isn't the right name on most systems
}

function update_1135() {
	//there can't be indexes with more than 1000 bytes in mysql,
	//so change charset to be smaller
	q("ALTER TABLE `config` CHANGE `cat` `cat` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL ,
CHANGE `k` `k` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL");

	//same thing for pconfig
	q("ALTER TABLE `pconfig` CHANGE `cat` `cat` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL ,
	CHANGE `k` `k` CHAR( 255 ) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL");
	// faulty update merged forward. Bad update in 1134 caused duplicate k,cat pairs
	// these have to be cleared before the unique keys can be added.
}

function update_1136() {

	$arr = array();

	// order in reverse so that we save the newest entry

	$r = q("select * from config where 1 order by id desc");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$found = false;
			foreach($arr as $x) {
				if($x['cat'] == $rr['cat'] && $x['k'] == $rr['k']) {
					$found = true;
					q("delete from config where id = %d",
						intval($rr['id'])
					);
				}
			}
			if(! $found) {
				$arr[] = $rr;
			}
		}
	}

	$arr = array();
	$r = q("select * from pconfig where 1 order by id desc");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$found = false;
			foreach($arr as $x) {
				if($x['uid'] == $rr['uid'] && $x['cat'] == $rr['cat'] && $x['k'] == $rr['k']) {
					$found = true;
					q("delete from pconfig where id = %d",
						intval($rr['id'])
					);
				}
			}
			if(! $found) {
				$arr[] = $rr;
			}
		}
	}
	q("ALTER TABLE `config` ADD UNIQUE `access` ( `cat` , `k` ) ");
	q("ALTER TABLE `pconfig` ADD UNIQUE `access` ( `uid` , `cat` , `k` )");

}


function update_1137() {
	q("alter table item_id DROP `face` , DROP `dspr` , DROP `twit` , DROP `stat` ");
	q("ALTER TABLE `item_id` ADD `sid` CHAR( 255 ) NOT NULL AFTER `uid` , ADD `service` CHAR( 255 ) NOT NULL AFTER `sid` , add index (`sid`), add index ( `service`) ");
}

function update_1138() {
	q("alter table contact add archive tinyint(1) not null default '0' after hidden, add index (archive)");
}

function update_1139() {
	$r = q("alter table user add account_removed tinyint(1) not null default '0' after expire, add index(account_removed) ");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1140() {
	$r = q("alter table addon add hidden tinyint(1) not null default '0' after installed, add index(hidden) ");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1141() {
	$r = q("alter table glink add zcid int(11) not null after gcid, add index(zcid) ");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}


function update_1142() {
	$r = q("alter table user add service_class char(32) not null after expire_notification_sent, add index(service_class) ");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1143() {
	$r = q("alter table user add def_gid int(11) not null default '0' after service_class");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1144() {
	$r = q("alter table contact add prv tinyint(1) not null default '0' after forum");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1145() {
	$r = q("alter table profile add howlong datetime not null default '0001-01-01 00:00:00' after `with`");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1146() {
	$r = q("alter table profile add hometown char(255) not null after `country-name`, add index ( `hometown` ) ");
	if(! $r)
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1147() {
	$r1 = q("ALTER TABLE `sign` ALTER `iid` SET DEFAULT '0'");
	$r2 = q("ALTER TABLE `sign` ADD `retract_iid` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `iid`");
	$r3 = q("ALTER TABLE `sign` ADD INDEX ( `retract_iid` )");
	if((! $r1) || (! $r2) || (! $r3))
		return UPDATE_FAILED ;
	return UPDATE_SUCCESS ;
}

function update_1148() {
	$r = q("ALTER TABLE photo ADD type CHAR(128) NOT NULL DEFAULT 'image/jpeg' AFTER filename");
	if (!$r)
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}


function update_1149() {
	$r1 = q("ALTER TABLE profile ADD likes text NOT NULL after prv_keywords");
	$r2 = q("ALTER TABLE profile ADD dislikes text NOT NULL after likes");
	if (! ($r1 && $r2))
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}


function update_1150() {
	$r = q("ALTER TABLE event ADD summary text NOT NULL after finish, add index ( uid ), add index ( cid ), add index ( uri ), add index ( `start` ), add index ( finish ), add index ( `type` ), add index ( adjust ) ");
	if(! $r)
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}


function update_1151() {
	$r = q("CREATE TABLE IF NOT EXISTS locks (
			id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			name CHAR( 128 ) NOT NULL ,
			locked TINYINT( 1 ) NOT NULL DEFAULT '0'
		  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
	if (!$r)
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}

function update_1152() {
	$r = q("CREATE TABLE IF NOT EXISTS `term` (
		`tid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`oid` INT UNSIGNED NOT NULL ,
		`otype` TINYINT( 3 ) UNSIGNED NOT NULL ,
		`type` TINYINT( 3 ) UNSIGNED NOT NULL ,
		`term` CHAR( 255 ) NOT NULL ,
		`url` CHAR( 255 ) NOT NULL,
		KEY `oid` ( `oid` ),
		KEY `otype` ( `otype` ),
		KEY `type`  ( `type` ),
		KEY `term`  ( `term` )
		) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
	if (!$r)
		return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}

function update_1153() {
	$r = q("ALTER TABLE `hook` ADD `priority` INT(11) UNSIGNED NOT NULL DEFAULT '0'");

	if(!$r) return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}

function update_1154() {
	$r = q("ALTER TABLE `event` ADD `ignore` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `adjust` , ADD INDEX ( `ignore` )");

	if(!$r) return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}

function update_1155() {
	$r1 = q("ALTER TABLE `item_id` DROP PRIMARY KEY");
	$r2 = q("ALTER TABLE `item_id` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
	$r3 = q("ALTER TABLE `item_id` ADD INDEX ( `iid` ) ");

	if($r1 && $r2 && $r3)
		return UPDATE_SUCCESS;

	return UPDATE_FAILED;
}

function update_1156() {
	$r = q("ALTER TABLE `photo` ADD `datasize` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `width` ,
ADD INDEX ( `datasize` ) ");

	if(!$r) return UPDATE_FAILED;
	return UPDATE_SUCCESS;
}

function update_1157() {
	$r = q("CREATE TABLE  IF NOT EXISTS `dsprphotoq` (
	  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `uid` int(11) NOT NULL,
	  `msg` mediumtext NOT NULL,
	  `attempt` tinyint(4) NOT NULL,
	  PRIMARY KEY (`id`)
	  ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
	);

	if($r)
		return UPDATE_SUCCESS;
}

function update_1158() {
	set_config('system', 'maintenance', 1);

	// Wait for 15 seconds for current requests to
	// clear before locking up the database
	sleep(15);

	$r = q("CREATE INDEX event_id ON item(`event-id`)");
	set_config('system', 'maintenance', 0);

	if($r)
		return UPDATE_SUCCESS;

	return UPDATE_FAILED;
}

function update_1159() {
	$r = q("ALTER TABLE `term` ADD `aid` int(10) unsigned NOT NULL DEFAULT '0',
		ADD `uid` int(10) unsigned NOT NULL DEFAULT '0',
		ADD INDEX (`uid`),
		ADD INDEX (`aid`)");

	if(!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1160() {
	set_config('system', 'maintenance', 1);

	// Wait for 15 seconds for current requests to
	// clear before locking up the database
	sleep(15);

	$r = q("ALTER TABLE `item` ADD `mention` TINYINT(1) NOT NULL DEFAULT '0', ADD INDEX (`mention`)");
	set_config('system', 'maintenance', 0);

	if(!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1161() {
	$r = q("ALTER TABLE `pconfig` ADD INDEX (`cat`)");

	if(!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1162() {
	require_once('include/tags.php');
	update_items();

	return UPDATE_SUCCESS;
}

function update_1163() {
	set_config('system', 'maintenance', 1);

	$r = q("ALTER TABLE `item` ADD `network` char(32) NOT NULL");

	set_config('system', 'maintenance', 0);
	if(!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}
function update_1164() {
	set_config('system', 'maintenance', 1);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_DFRN);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_DFRN, NETWORK_DFRN);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_ZOT, NETWORK_ZOT);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_OSTATUS, NETWORK_OSTATUS);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_FEED, NETWORK_FEED);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_DIASPORA, NETWORK_DIASPORA);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_MAIL, NETWORK_MAIL);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_MAIL2, NETWORK_MAIL2);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_FACEBOOK, NETWORK_FACEBOOK);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_LINKEDIN, NETWORK_LINKEDIN);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_XMPP, NETWORK_XMPP);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_MYSPACE, NETWORK_MYSPACE);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_GPLUS, NETWORK_GPLUS);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_PUMPIO, NETWORK_PUMPIO);

	$r = q("UPDATE `item` SET `network`='%s' WHERE `contact-id` IN (SELECT `id` FROM`contact` WHERE `network` = '%s' AND `contact`.`uid` = `item`.`uid`)",
		NETWORK_TWITTER, NETWORK_TWITTER);

	set_config('system', 'maintenance', 0);

	return UPDATE_SUCCESS;
}

function update_1165() {
	$r = q("CREATE TABLE IF NOT EXISTS `push_subscriber` (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		    `uid` INT NOT NULL,
	        `callback_url` CHAR( 255 ) NOT NULL,
            `topic` CHAR( 255 ) NOT NULL,
            `nickname` CHAR( 255 ) NOT NULL,
            `push` INT NOT NULL,
            `last_update` DATETIME NOT NULL,
            `secret` CHAR( 255 ) NOT NULL
		  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
	if (!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1166() {
	$r = q("CREATE TABLE IF NOT EXISTS `unique_contacts` (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`url` CHAR(255) NOT NULL,
			`nick` CHAR(255) NOT NULL,
			`name` CHAR(255) NOT NULL,
			`avatar` CHAR(255) NOT NULL,
			INDEX (`url`)
		  ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ");
	if (!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1167() {
	$r = q("ALTER TABLE `contact` ADD `notify_new_posts` TINYINT(1) NOT NULL DEFAULT '0'");
	if (!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1168() {
	$r = q("ALTER TABLE `contact` ADD `fetch_further_information` TINYINT(1) NOT NULL DEFAULT '0'");
	if (!$r)
		return UPDATE_FAILED;

	return UPDATE_SUCCESS;
}

function update_1169() {
	$r = q("CREATE TABLE IF NOT EXISTS `thread` (
		  `iid` int(10) unsigned NOT NULL DEFAULT '0',
		  `uid` int(10) unsigned NOT NULL DEFAULT '0',
		  `contact-id` int(11) unsigned NOT NULL DEFAULT '0',
		  `created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
		  `edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
		  `commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
		  `received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
		  `changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00',
		  `wall` tinyint(1) NOT NULL DEFAULT '0',
		  `private` tinyint(1) NOT NULL DEFAULT '0',
		  `pubmail` tinyint(1) NOT NULL DEFAULT '0',
		  `moderated` tinyint(1) NOT NULL DEFAULT '0',
		  `visible` tinyint(1) NOT NULL DEFAULT '0',
		  `spam` tinyint(1) NOT NULL DEFAULT '0',
		  `starred` tinyint(1) NOT NULL DEFAULT '0',
		  `bookmark` tinyint(1) NOT NULL DEFAULT '0',
		  `unseen` tinyint(1) NOT NULL DEFAULT '1',
		  `deleted` tinyint(1) NOT NULL DEFAULT '0',
		  `origin` tinyint(1) NOT NULL DEFAULT '0',
		  `forum_mode` tinyint(1) NOT NULL DEFAULT '0',
		  `mention` tinyint(1) NOT NULL DEFAULT '0',
		  `network` char(32) NOT NULL,
		  PRIMARY KEY (`iid`),
		  KEY `created` (`created`),
		  KEY `commented` (`commented`),
		  KEY `uid_network_commented` (`uid`,`network`,`commented`),
		  KEY `uid_network_created` (`uid`,`network`,`created`),
		  KEY `uid_contactid_commented` (`uid`,`contact-id`,`commented`),
		  KEY `uid_contactid_created` (`uid`,`contact-id`,`created`),
		  KEY `wall_private_received` (`wall`,`private`,`received`),
		  KEY `uid_created` (`uid`,`created`),
		  KEY `uid_commented` (`uid`,`commented`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
	if (!$r)
		return UPDATE_FAILED;

	proc_run(PRIORITY_LOW, "include/threadupdate.php");

	return UPDATE_SUCCESS;
}

/*
==========
ATTENTION!
==========

All following update functions are ONLY for jobs that need to run AFTER the database changes are applied.

Database changes are ONLY applied in the file include/dbstructure.php.
*/

function update_1177() {
	require_once("mod/profiles.php");

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

function update_1178() {
	if (get_config('system','no_community_page'))
		set_config('system','community_page_style', CP_NO_COMMUNITY_PAGE);

	// Update the central item storage with uid=0
	proc_run(PRIORITY_LOW, "include/threadupdate.php");

	return UPDATE_SUCCESS;
}

function update_1180() {

	// Fill the new fields in the term table.
	proc_run(PRIORITY_LOW, "include/tagupdate.php");

	return UPDATE_SUCCESS;
}

function update_1188() {

	if (strlen(get_config('system','directory_submit_url')) AND
		!strlen(get_config('system','directory'))) {
		set_config('system','directory', dirname(get_config('system','directory_submit_url')));
		del_config('system','directory_submit_url');
	}

	return UPDATE_SUCCESS;
}

function update_1190() {

	require_once('include/plugin.php');

	set_config('system', 'maintenance', 1);

	if (plugin_enabled('forumlist')) {
		$plugin = 'forumlist';
		$plugins = get_config('system','addon');
		$plugins_arr = array();

		if ($plugins) {
			$plugins_arr = explode(",",str_replace(" ", "",$plugins));

			$idx = array_search($plugin, $plugins_arr);
			if ($idx !== false){
				unset($plugins_arr[$idx]);
				//delete forumlist manually from addon and hook table
				// since uninstall_plugin() don't work here
				q("DELETE FROM `addon` WHERE `name` = 'forumlist' ");
				q("DELETE FROM `hook` WHERE `file` = 'addon/forumlist/forumlist.php' ");
				set_config('system','addon', implode(", ",$plugins_arr));
			}
		}
	}

	// select old formlist addon entries
	$r = q("SELECT `uid`, `cat`, `k`, `v` FROM `pconfig` WHERE `cat` = '%s' ",
		dbesc('forumlist')
	);

	// convert old forumlist addon entries in new config entries
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$uid = $rr['uid'];
			$family = $rr['cat'];
			$key = $rr['k'];
			$value = $rr['v'];

			if ($key === 'randomise')
				del_pconfig($uid,$family,$key);

			if ($key === 'show_on_profile') {
				if ($value)
					set_pconfig($uid,feature,forumlist_profile,$value);

				del_pconfig($uid,$family,$key);
			}

			if ($key === 'show_on_network') {
				if ($value)
					set_pconfig($uid,feature,forumlist_widget,$value);

				del_pconfig($uid,$family,$key);
			}
		}
	}

	set_config('system', 'maintenance', 0);

	return UPDATE_SUCCESS;

}

function update_1202() {
	$r = q("UPDATE `user` SET `account-type` = %d WHERE `page-flags` IN (%d, %d)",
		dbesc(ACCOUNT_TYPE_COMMUNITY), dbesc(PAGE_COMMUNITY), dbesc(PAGE_PRVGROUP));
}

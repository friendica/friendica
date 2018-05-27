-- ------------------------------------------
-- Friendica 2018.05-rc (The Tazmans Flax-lily)
-- DB_UPDATE_VERSION 1266
-- ------------------------------------------


--
-- TABLE addon
--
CREATE TABLE IF NOT EXISTS `addon` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`name` varchar(50) NOT NULL DEFAULT '' COMMENT '',
	`version` varchar(50) NOT NULL DEFAULT '' COMMENT '',
	`installed` boolean NOT NULL DEFAULT '0' COMMENT '',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT '',
	`timestamp` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`plugin_admin` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `name` (`name`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE attach
--
CREATE TABLE IF NOT EXISTS `attach` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`hash` varchar(64) NOT NULL DEFAULT '' COMMENT '',
	`filename` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`filetype` varchar(64) NOT NULL DEFAULT '' COMMENT '',
	`filesize` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`data` longblob NOT NULL COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`allow_cid` mediumtext COMMENT '',
	`allow_gid` mediumtext COMMENT '',
	`deny_cid` mediumtext COMMENT '',
	`deny_gid` mediumtext COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE auth_codes
--
CREATE TABLE IF NOT EXISTS `auth_codes` (
	`id` varchar(40) NOT NULL COMMENT '',
	`client_id` varchar(20) NOT NULL DEFAULT '' COMMENT '',
	`redirect_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '',
	`expires` int NOT NULL DEFAULT 0 COMMENT '',
	`scope` varchar(250) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE cache
--
CREATE TABLE IF NOT EXISTS `cache` (
	`k` varbinary(255) NOT NULL COMMENT 'cache key',
	`v` mediumtext COMMENT 'cached serialized value',
	`expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of cache expiration',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of cache insertion',
	 PRIMARY KEY(`k`),
	 INDEX `k_expires` (`k`,`expires`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE challenge
--
CREATE TABLE IF NOT EXISTS `challenge` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`challenge` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`dfrn-id` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`type` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`last_update` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE clients
--
CREATE TABLE IF NOT EXISTS `clients` (
	`client_id` varchar(20) NOT NULL COMMENT '',
	`pw` varchar(20) NOT NULL DEFAULT '' COMMENT '',
	`redirect_uri` varchar(200) NOT NULL DEFAULT '' COMMENT '',
	`name` text COMMENT '',
	`icon` text COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`client_id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE config
--
CREATE TABLE IF NOT EXISTS `config` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`cat` varbinary(50) NOT NULL DEFAULT '' COMMENT '',
	`k` varbinary(50) NOT NULL DEFAULT '' COMMENT '',
	`v` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `cat_k` (`cat`,`k`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE contact
--
CREATE TABLE IF NOT EXISTS `contact` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`self` boolean NOT NULL DEFAULT '0' COMMENT '',
	`remote_self` boolean NOT NULL DEFAULT '0' COMMENT '',
	`rel` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`duplex` boolean NOT NULL DEFAULT '0' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`location` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`about` text COMMENT '',
	`keywords` text COMMENT '',
	`gender` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`xmpp` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`attag` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) DEFAULT '' COMMENT '',
	`thumb` varchar(255) DEFAULT '' COMMENT '',
	`micro` varchar(255) DEFAULT '' COMMENT '',
	`site-pubkey` text COMMENT '',
	`issued-id` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`dfrn-id` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nurl` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`addr` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`alias` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pubkey` text COMMENT '',
	`prvkey` text COMMENT '',
	`batch` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`request` varchar(255) COMMENT '',
	`notify` varchar(255) COMMENT '',
	`poll` varchar(255) COMMENT '',
	`confirm` varchar(255) COMMENT '',
	`poco` varchar(255) COMMENT '',
	`aes_allow` boolean NOT NULL DEFAULT '0' COMMENT '',
	`ret-aes` boolean NOT NULL DEFAULT '0' COMMENT '',
	`usehub` boolean NOT NULL DEFAULT '0' COMMENT '',
	`subhub` boolean NOT NULL DEFAULT '0' COMMENT '',
	`hub-verify` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`last-update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`success_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`failure_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`name-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`uri-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`avatar-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`term-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last-item` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`blocked` boolean NOT NULL DEFAULT '1' COMMENT '',
	`readonly` boolean NOT NULL DEFAULT '0' COMMENT '',
	`writable` boolean NOT NULL DEFAULT '0' COMMENT '',
	`forum` boolean NOT NULL DEFAULT '0' COMMENT '',
	`prv` boolean NOT NULL DEFAULT '0' COMMENT '',
	`contact-type` tinyint NOT NULL DEFAULT 0 COMMENT '',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT '',
	`archive` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pending` boolean NOT NULL DEFAULT '1' COMMENT '',
	`rating` tinyint NOT NULL DEFAULT 0 COMMENT '',
	`reason` text COMMENT '',
	`closeness` tinyint unsigned NOT NULL DEFAULT 99 COMMENT '',
	`info` mediumtext COMMENT '',
	`profile-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`bdyear` varchar(4) NOT NULL DEFAULT '' COMMENT '',
	`bd` date NOT NULL DEFAULT '0001-01-01' COMMENT '',
	`notify_new_posts` boolean NOT NULL DEFAULT '0' COMMENT '',
	`fetch_further_information` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`ffi_keyword_blacklist` text COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid_name` (`uid`,`name`(190)),
	 INDEX `self_uid` (`self`,`uid`),
	 INDEX `alias_uid` (`alias`(32),`uid`),
	 INDEX `pending_uid` (`pending`,`uid`),
	 INDEX `blocked_uid` (`blocked`,`uid`),
	 INDEX `uid_rel_network_poll` (`uid`,`rel`,`network`,`poll`(64),`archive`),
	 INDEX `uid_network_batch` (`uid`,`network`,`batch`(64)),
	 INDEX `addr_uid` (`addr`(32),`uid`),
	 INDEX `nurl_uid` (`nurl`(32),`uid`),
	 INDEX `nick_uid` (`nick`(32),`uid`),
	 INDEX `dfrn-id` (`dfrn-id`(64)),
	 INDEX `issued-id` (`issued-id`(64))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE conv
--
CREATE TABLE IF NOT EXISTS `conv` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`recips` text COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`creator` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`subject` text COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE conversation
--
CREATE TABLE IF NOT EXISTS `conversation` (
	`item-uri` varbinary(255) NOT NULL COMMENT '',
	`reply-to-uri` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`conversation-uri` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`conversation-href` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`protocol` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`source` mediumtext COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`item-uri`),
	 INDEX `conversation-uri` (`conversation-uri`),
	 INDEX `received` (`received`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE event
--
CREATE TABLE IF NOT EXISTS `event` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`uri` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`start` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`finish` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`summary` text COMMENT '',
	`desc` text COMMENT '',
	`location` text COMMENT '',
	`type` varchar(20) NOT NULL DEFAULT '' COMMENT '',
	`nofinish` boolean NOT NULL DEFAULT '0' COMMENT '',
	`adjust` boolean NOT NULL DEFAULT '1' COMMENT '',
	`ignore` boolean NOT NULL DEFAULT '0' COMMENT '',
	`allow_cid` mediumtext COMMENT '',
	`allow_gid` mediumtext COMMENT '',
	`deny_cid` mediumtext COMMENT '',
	`deny_gid` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid_start` (`uid`,`start`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE fcontact
--
CREATE TABLE IF NOT EXISTS `fcontact` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`request` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`addr` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`batch` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`notify` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`poll` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`confirm` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`alias` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pubkey` text COMMENT '',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `addr` (`addr`(32)),
	 UNIQUE INDEX `url` (`url`(190))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE fsuggest
--
CREATE TABLE IF NOT EXISTS `fsuggest` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`request` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`note` text COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE gcign
--
CREATE TABLE IF NOT EXISTS `gcign` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`gcid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	 INDEX `gcid` (`gcid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE gcontact
--
CREATE TABLE IF NOT EXISTS `gcontact` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nurl` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`connect` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`updated` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_contact` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_failure` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`location` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`about` text COMMENT '',
	`keywords` text COMMENT '',
	`gender` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`birthday` varchar(32) NOT NULL DEFAULT '0001-01-01' COMMENT '',
	`community` boolean NOT NULL DEFAULT '0' COMMENT '',
	`contact-type` tinyint NOT NULL DEFAULT -1 COMMENT '',
	`hide` boolean NOT NULL DEFAULT '0' COMMENT '',
	`nsfw` boolean NOT NULL DEFAULT '0' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`addr` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`notify` varchar(255) COMMENT '',
	`alias` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`generation` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`server_url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `nurl` (`nurl`(190)),
	 INDEX `name` (`name`(64)),
	 INDEX `nick` (`nick`(32)),
	 INDEX `addr` (`addr`(64)),
	 INDEX `hide_network_updated` (`hide`,`network`,`updated`),
	 INDEX `updated` (`updated`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE glink
--
CREATE TABLE IF NOT EXISTS `glink` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`gcid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`zcid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `cid_uid_gcid_zcid` (`cid`,`uid`,`gcid`,`zcid`),
	 INDEX `gcid` (`gcid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE group
--
CREATE TABLE IF NOT EXISTS `group` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE group_member
--
CREATE TABLE IF NOT EXISTS `group_member` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`gid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `contactid` (`contact-id`),
	 UNIQUE INDEX `gid_contactid` (`gid`,`contact-id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE gserver
--
CREATE TABLE IF NOT EXISTS `gserver` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nurl` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`version` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`site_name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`info` text COMMENT '',
	`register_policy` tinyint NOT NULL DEFAULT 0 COMMENT '',
	`registered-users` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`poco` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`noscrape` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`platform` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`relay-subscribe` boolean NOT NULL DEFAULT '0' COMMENT 'Has the server subscribed to the relay system',
	`relay-scope` varchar(10) NOT NULL DEFAULT '' COMMENT 'The scope of messages that the server wants to get',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_poco_query` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_contact` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_failure` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `nurl` (`nurl`(190))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE gserver-tag
--
CREATE TABLE IF NOT EXISTS `gserver-tag` (
	`gserver-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'The id of the gserver',
	`tag` varchar(100) NOT NULL DEFAULT '' COMMENT 'Tag that the server has subscribed',
	 PRIMARY KEY(`gserver-id`,`tag`),
	 INDEX `tag` (`tag`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE hook
--
CREATE TABLE IF NOT EXISTS `hook` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`hook` varbinary(100) NOT NULL DEFAULT '' COMMENT '',
	`file` varbinary(200) NOT NULL DEFAULT '' COMMENT '',
	`function` varbinary(200) NOT NULL DEFAULT '' COMMENT '',
	`priority` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `hook_file_function` (`hook`,`file`,`function`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE intro
--
CREATE TABLE IF NOT EXISTS `intro` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`fid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`knowyou` boolean NOT NULL DEFAULT '0' COMMENT '',
	`duplex` boolean NOT NULL DEFAULT '0' COMMENT '',
	`note` text COMMENT '',
	`hash` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`datetime` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`blocked` boolean NOT NULL DEFAULT '1' COMMENT '',
	`ignore` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE item
--
CREATE TABLE IF NOT EXISTS `item` (
	`id` int unsigned NOT NULL auto_increment,
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`uri` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`type` varchar(20) NOT NULL DEFAULT '' COMMENT '',
	`wall` boolean NOT NULL DEFAULT '0' COMMENT '',
	`gravity` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`parent` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`parent-uri` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`extid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`thr-parent` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`owner-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`owner-link` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`owner-avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`author-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`author-link` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`author-avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`content-warning` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`body` mediumtext COMMENT '',
	`app` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`verb` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	`object-type` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	`object` text COMMENT '',
	`target-type` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	`target` text COMMENT '',
	`postopts` text COMMENT '',
	`plink` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`resource-id` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`event-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`tag` mediumtext COMMENT '',
	`attach` mediumtext COMMENT '',
	`inform` mediumtext COMMENT '',
	`file` mediumtext COMMENT '',
	`location` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`coord` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`allow_cid` mediumtext COMMENT '',
	`allow_gid` mediumtext COMMENT '',
	`deny_cid` mediumtext COMMENT '',
	`deny_gid` mediumtext COMMENT '',
	`private` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pubmail` boolean NOT NULL DEFAULT '0' COMMENT '',
	`moderated` boolean NOT NULL DEFAULT '0' COMMENT '',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '',
	`spam` boolean NOT NULL DEFAULT '0' COMMENT '',
	`starred` boolean NOT NULL DEFAULT '0' COMMENT '',
	`bookmark` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unseen` boolean NOT NULL DEFAULT '1' COMMENT '',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT '',
	`origin` boolean NOT NULL DEFAULT '0' COMMENT '',
	`forum_mode` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`mention` boolean NOT NULL DEFAULT '0' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`rendered-hash` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`rendered-html` mediumtext COMMENT '',
	`global` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `guid` (`guid`(191)),
	 INDEX `uri` (`uri`(191)),
	 INDEX `parent` (`parent`),
	 INDEX `parent-uri` (`parent-uri`(191)),
	 INDEX `extid` (`extid`(191)),
	 INDEX `uid_id` (`uid`,`id`),
	 INDEX `uid_contactid_id` (`uid`,`contact-id`,`id`),
	 INDEX `uid_created` (`uid`,`created`),
	 INDEX `uid_commented` (`uid`,`commented`),
	 INDEX `uid_unseen_contactid` (`uid`,`unseen`,`contact-id`),
	 INDEX `uid_network_received` (`uid`,`network`,`received`),
	 INDEX `uid_network_commented` (`uid`,`network`,`commented`),
	 INDEX `uid_thrparent` (`uid`,`thr-parent`(190)),
	 INDEX `uid_parenturi` (`uid`,`parent-uri`(190)),
	 INDEX `uid_contactid_created` (`uid`,`contact-id`,`created`),
	 INDEX `authorid_created` (`author-id`,`created`),
	 INDEX `ownerid` (`owner-id`),
	 INDEX `uid_uri` (`uid`,`uri`(190)),
	 INDEX `resource-id` (`resource-id`),
	 INDEX `contactid_allowcid_allowpid_denycid_denygid` (`contact-id`,`allow_cid`(10),`allow_gid`(10),`deny_cid`(10),`deny_gid`(10)),
	 INDEX `uid_type_changed` (`uid`,`type`,`changed`),
	 INDEX `contactid_verb` (`contact-id`,`verb`),
	 INDEX `deleted_changed` (`deleted`,`changed`),
	 INDEX `uid_wall_changed` (`uid`,`wall`,`changed`),
	 INDEX `uid_eventid` (`uid`,`event-id`),
	 INDEX `uid_authorlink` (`uid`,`author-link`(190)),
	 INDEX `uid_ownerlink` (`uid`,`owner-link`(190))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE locks
--
CREATE TABLE IF NOT EXISTS `locks` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`name` varchar(128) NOT NULL DEFAULT '' COMMENT '',
	`locked` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE mail
--
CREATE TABLE IF NOT EXISTS `mail` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`from-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`from-photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`from-url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`contact-id` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`convid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`body` mediumtext COMMENT '',
	`seen` boolean NOT NULL DEFAULT '0' COMMENT '',
	`reply` boolean NOT NULL DEFAULT '0' COMMENT '',
	`replied` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unknown` boolean NOT NULL DEFAULT '0' COMMENT '',
	`uri` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`parent-uri` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid_seen` (`uid`,`seen`),
	 INDEX `convid` (`convid`),
	 INDEX `uri` (`uri`(64)),
	 INDEX `parent-uri` (`parent-uri`(64)),
	 INDEX `contactid` (`contact-id`(32))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE mailacct
--
CREATE TABLE IF NOT EXISTS `mailacct` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`server` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`port` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`ssltype` varchar(16) NOT NULL DEFAULT '' COMMENT '',
	`mailbox` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`user` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pass` text COMMENT '',
	`reply_to` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`action` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`movetofolder` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pubmail` boolean NOT NULL DEFAULT '0' COMMENT '',
	`last_check` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE manage
--
CREATE TABLE IF NOT EXISTS `manage` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`mid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_mid` (`uid`,`mid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE notify
--
CREATE TABLE IF NOT EXISTS `notify` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`hash` varchar(64) NOT NULL DEFAULT '' COMMENT '',
	`type` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`msg` mediumtext COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`link` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`iid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`parent` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`seen` boolean NOT NULL DEFAULT '0' COMMENT '',
	`verb` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	`otype` varchar(10) NOT NULL DEFAULT '' COMMENT '',
	`name_cache` tinytext COMMENT '',
	`msg_cache` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `hash_uid` (`hash`,`uid`),
	 INDEX `seen_uid_date` (`seen`,`uid`,`date`),
	 INDEX `uid_date` (`uid`,`date`),
	 INDEX `uid_type_link` (`uid`,`type`,`link`(190))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE notify-threads
--
CREATE TABLE IF NOT EXISTS `notify-threads` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`notify-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`master-parent-item` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`parent-item` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`receiver-uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE oembed
--
CREATE TABLE IF NOT EXISTS `oembed` (
	`url` varbinary(255) NOT NULL COMMENT '',
	`maxwidth` mediumint unsigned NOT NULL COMMENT '',
	`content` mediumtext COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`url`,`maxwidth`),
	 INDEX `created` (`created`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE parsed_url
--
CREATE TABLE IF NOT EXISTS `parsed_url` (
	`url` varbinary(255) NOT NULL COMMENT '',
	`guessing` boolean NOT NULL DEFAULT '0' COMMENT '',
	`oembed` boolean NOT NULL DEFAULT '0' COMMENT '',
	`content` mediumtext COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`url`,`guessing`,`oembed`),
	 INDEX `created` (`created`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE participation
--
CREATE TABLE IF NOT EXISTS `participation` (
	`iid` int unsigned NOT NULL COMMENT '',
	`server` varchar(60) NOT NULL COMMENT '',
	`cid` int unsigned NOT NULL COMMENT '',
	`fid` int unsigned NOT NULL COMMENT '',
	 PRIMARY KEY(`iid`,`server`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE pconfig
--
CREATE TABLE IF NOT EXISTS `pconfig` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cat` varbinary(50) NOT NULL DEFAULT '' COMMENT '',
	`k` varbinary(100) NOT NULL DEFAULT '' COMMENT '',
	`v` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_cat_k` (`uid`,`cat`,`k`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE photo
--
CREATE TABLE IF NOT EXISTS `photo` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`guid` char(16) NOT NULL DEFAULT '' COMMENT '',
	`resource-id` char(32) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`desc` text COMMENT '',
	`album` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`filename` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`type` varchar(30) NOT NULL DEFAULT 'image/jpeg',
	`height` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`width` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`datasize` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`data` mediumblob NOT NULL COMMENT '',
	`scale` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`profile` boolean NOT NULL DEFAULT '0' COMMENT '',
	`allow_cid` mediumtext COMMENT '',
	`allow_gid` mediumtext COMMENT '',
	`deny_cid` mediumtext COMMENT '',
	`deny_gid` mediumtext COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `contactid` (`contact-id`),
	 INDEX `uid_contactid` (`uid`,`contact-id`),
	 INDEX `uid_profile` (`uid`,`profile`),
	 INDEX `uid_album_scale_created` (`uid`,`album`(32),`scale`,`created`),
	 INDEX `uid_album_resource-id_created` (`uid`,`album`(32),`resource-id`,`created`),
	 INDEX `resource-id` (`resource-id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE poll
--
CREATE TABLE IF NOT EXISTS `poll` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`q0` text COMMENT '',
	`q1` text COMMENT '',
	`q2` text COMMENT '',
	`q3` text COMMENT '',
	`q4` text COMMENT '',
	`q5` text COMMENT '',
	`q6` text COMMENT '',
	`q7` text COMMENT '',
	`q8` text COMMENT '',
	`q9` text COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE poll_result
--
CREATE TABLE IF NOT EXISTS `poll_result` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`poll_id` int unsigned NOT NULL DEFAULT 0,
	`choice` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `poll_id` (`poll_id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE process
--
CREATE TABLE IF NOT EXISTS `process` (
	`pid` int unsigned NOT NULL COMMENT '',
	`command` varbinary(32) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`pid`),
	 INDEX `command` (`command`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE profile
--
CREATE TABLE IF NOT EXISTS `profile` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`profile-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`is-default` boolean NOT NULL DEFAULT '0' COMMENT '',
	`hide-friends` boolean NOT NULL DEFAULT '0' COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pdesc` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`dob` varchar(32) NOT NULL DEFAULT '0000-00-00' COMMENT '',
	`address` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`locality` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`region` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`postal-code` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`country-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`hometown` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`gender` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`marital` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`with` text COMMENT '',
	`howlong` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`sexual` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`politic` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`religion` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pub_keywords` text COMMENT '',
	`prv_keywords` text COMMENT '',
	`likes` text COMMENT '',
	`dislikes` text COMMENT '',
	`about` text COMMENT '',
	`summary` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`music` text COMMENT '',
	`book` text COMMENT '',
	`tv` text COMMENT '',
	`film` text COMMENT '',
	`interest` text COMMENT '',
	`romance` text COMMENT '',
	`work` text COMMENT '',
	`education` text COMMENT '',
	`contact` text COMMENT '',
	`homepage` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`xmpp` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`publish` boolean NOT NULL DEFAULT '0' COMMENT '',
	`net-publish` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid_is-default` (`uid`,`is-default`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE profile_check
--
CREATE TABLE IF NOT EXISTS `profile_check` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`dfrn_id` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`sec` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE push_subscriber
--
CREATE TABLE IF NOT EXISTS `push_subscriber` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`callback_url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`topic` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`push` tinyint NOT NULL DEFAULT 0 COMMENT 'Retrial counter',
	`last_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last successful trial',
	`next_try` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Next retrial date',
	`renewed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last subscription renewal',
	`secret` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `next_try` (`next_try`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE queue
--
CREATE TABLE IF NOT EXISTS `queue` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Message receiver',
	`network` char(4) NOT NULL DEFAULT '' COMMENT 'Receiver\'s network',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT 'Unique GUID of the message',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date, when the message was created',
	`last` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last trial',
	`next` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Next retrial date',
	`retrial` tinyint NOT NULL DEFAULT 0 COMMENT 'Retrial counter',
	`content` mediumtext COMMENT '',
	`batch` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `last` (`last`),
	 INDEX `next` (`next`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE register
--
CREATE TABLE IF NOT EXISTS `register` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`hash` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`password` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`language` varchar(16) NOT NULL DEFAULT '' COMMENT '',
	`note` text COMMENT '',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE search
--
CREATE TABLE IF NOT EXISTS `search` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`term` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE session
--
CREATE TABLE IF NOT EXISTS `session` (
	`id` bigint unsigned NOT NULL auto_increment COMMENT '',
	`sid` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`data` text COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `sid` (`sid`(64)),
	 INDEX `expire` (`expire`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE sign
--
CREATE TABLE IF NOT EXISTS `sign` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`iid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`signed_text` mediumtext COMMENT '',
	`signature` text COMMENT '',
	`signer` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `iid` (`iid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE term
--
CREATE TABLE IF NOT EXISTS `term` (
	`tid` int unsigned NOT NULL auto_increment COMMENT '',
	`oid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`otype` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`term` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`guid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`global` boolean NOT NULL DEFAULT '0' COMMENT '',
	`aid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`tid`),
	 INDEX `oid_otype_type_term` (`oid`,`otype`,`type`,`term`(32)),
	 INDEX `uid_otype_type_term_global_created` (`uid`,`otype`,`type`,`term`(32),`global`,`created`),
	 INDEX `uid_otype_type_url` (`uid`,`otype`,`type`,`url`(64)),
	 INDEX `guid` (`guid`(64))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE thread
--
CREATE TABLE IF NOT EXISTS `thread` (
	`iid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`wall` boolean NOT NULL DEFAULT '0' COMMENT '',
	`private` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pubmail` boolean NOT NULL DEFAULT '0' COMMENT '',
	`moderated` boolean NOT NULL DEFAULT '0' COMMENT '',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '',
	`spam` boolean NOT NULL DEFAULT '0' COMMENT '',
	`starred` boolean NOT NULL DEFAULT '0' COMMENT '',
	`ignored` boolean NOT NULL DEFAULT '0' COMMENT '',
	`bookmark` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unseen` boolean NOT NULL DEFAULT '1' COMMENT '',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT '',
	`origin` boolean NOT NULL DEFAULT '0' COMMENT '',
	`forum_mode` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`mention` boolean NOT NULL DEFAULT '0' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`iid`),
	 INDEX `uid_network_commented` (`uid`,`network`,`commented`),
	 INDEX `uid_network_created` (`uid`,`network`,`created`),
	 INDEX `uid_contactid_commented` (`uid`,`contact-id`,`commented`),
	 INDEX `uid_contactid_created` (`uid`,`contact-id`,`created`),
	 INDEX `contactid` (`contact-id`),
	 INDEX `ownerid` (`owner-id`),
	 INDEX `authorid` (`author-id`),
	 INDEX `uid_created` (`uid`,`created`),
	 INDEX `uid_commented` (`uid`,`commented`),
	 INDEX `uid_wall_created` (`uid`,`wall`,`created`),
	 INDEX `private_wall_origin_commented` (`private`,`wall`,`origin`,`commented`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE tokens
--
CREATE TABLE IF NOT EXISTS `tokens` (
	`id` varchar(40) NOT NULL COMMENT '',
	`secret` text COMMENT '',
	`client_id` varchar(20) NOT NULL DEFAULT '',
	`expires` int NOT NULL DEFAULT 0 COMMENT '',
	`scope` varchar(200) NOT NULL DEFAULT '' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE user
--
CREATE TABLE IF NOT EXISTS `user` (
	`uid` mediumint unsigned NOT NULL auto_increment COMMENT '',
	`parent-uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'The parent user that has full control about this user',
	`guid` varchar(64) NOT NULL DEFAULT '' COMMENT '',
	`username` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`password` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`legacy_password` boolean NOT NULL DEFAULT '0' COMMENT 'Is the password hash double-hashed?',
	`nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`email` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`openid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`timezone` varchar(128) NOT NULL DEFAULT '' COMMENT '',
	`language` varchar(32) NOT NULL DEFAULT 'en' COMMENT '',
	`register_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`login_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`default-location` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`allow_location` boolean NOT NULL DEFAULT '0' COMMENT '',
	`theme` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pubkey` text COMMENT '',
	`prvkey` text COMMENT '',
	`spubkey` text COMMENT '',
	`sprvkey` text COMMENT '',
	`verified` boolean NOT NULL DEFAULT '0' COMMENT '',
	`blocked` boolean NOT NULL DEFAULT '0' COMMENT '',
	`blockwall` boolean NOT NULL DEFAULT '0' COMMENT '',
	`hidewall` boolean NOT NULL DEFAULT '0' COMMENT '',
	`blocktags` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unkmail` boolean NOT NULL DEFAULT '0' COMMENT '',
	`cntunkmail` int unsigned NOT NULL DEFAULT 10 COMMENT '',
	`notify-flags` smallint unsigned NOT NULL DEFAULT 65535 COMMENT '',
	`page-flags` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`account-type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`prvnets` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pwdreset` varchar(255) COMMENT 'Password reset request token',
	`pwdreset_time` datetime COMMENT 'Timestamp of the last password reset request',
	`maxreq` int unsigned NOT NULL DEFAULT 10 COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`account_removed` boolean NOT NULL DEFAULT '0' COMMENT '',
	`account_expired` boolean NOT NULL DEFAULT '0' COMMENT '',
	`account_expires_on` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`expire_notification_sent` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`def_gid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`allow_cid` mediumtext COMMENT '',
	`allow_gid` mediumtext COMMENT '',
	`deny_cid` mediumtext COMMENT '',
	`deny_gid` mediumtext COMMENT '',
	`openidserver` text COMMENT '',
	 PRIMARY KEY(`uid`),
	 INDEX `nickname` (`nickname`(32))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE userd
--
CREATE TABLE IF NOT EXISTS `userd` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`username` varchar(255) NOT NULL COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `username` (`username`(32))
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE user-item
--
CREATE TABLE IF NOT EXISTS `user-item` (
	`iid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Item id',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT 'Hidden marker',
	 PRIMARY KEY(`uid`,`iid`)
) DEFAULT COLLATE utf8mb4_general_ci;

--
-- TABLE workerqueue
--
CREATE TABLE IF NOT EXISTS `workerqueue` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'Auto incremented worker task id',
	`parameter` mediumblob COMMENT 'Task command',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Task priority',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation date',
	`pid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Process id of the worker',
	`executed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Execution date',
	`done` boolean NOT NULL DEFAULT '0' COMMENT 'Marked when the task was done, will be deleted later',
	 PRIMARY KEY(`id`),
	 INDEX `pid` (`pid`),
	 INDEX `parameter` (`parameter`(64)),
	 INDEX `priority_created` (`priority`,`created`),
	 INDEX `done_executed` (`done`,`executed`)
) DEFAULT COLLATE utf8mb4_general_ci;



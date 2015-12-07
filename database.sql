-- ------------------------------------------
-- Friendica 3.4.2 (Lily of the valley)
-- DB_UPDATE_VERSION 1190
-- ------------------------------------------


--
-- TABLE addon
--
CREATE TABLE IF NOT EXISTS `addon` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`name` varchar(255) NOT NULL DEFAULT '',
	`version` varchar(255) NOT NULL DEFAULT '',
	`installed` tinyint(1) NOT NULL DEFAULT 0,
	`hidden` tinyint(1) NOT NULL DEFAULT 0,
	`timestamp` bigint(20) NOT NULL DEFAULT 0,
	`plugin_admin` tinyint(1) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE attach
--
CREATE TABLE IF NOT EXISTS `attach` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`hash` varchar(64) NOT NULL DEFAULT '',
	`filename` varchar(255) NOT NULL DEFAULT '',
	`filetype` varchar(64) NOT NULL DEFAULT '',
	`filesize` int(11) NOT NULL DEFAULT 0,
	`data` longblob NOT NULL,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`allow_cid` mediumtext NOT NULL,
	`allow_gid` mediumtext NOT NULL,
	`deny_cid` mediumtext NOT NULL,
	`deny_gid` mediumtext NOT NULL
) DEFAULT CHARSET=utf8;

--
-- TABLE auth_codes
--
CREATE TABLE IF NOT EXISTS `auth_codes` (
	`id` varchar(40) NOT NULL PRIMARY KEY,
	`client_id` varchar(20) NOT NULL DEFAULT '',
	`redirect_uri` varchar(200) NOT NULL DEFAULT '',
	`expires` int(11) NOT NULL DEFAULT 0,
	`scope` varchar(250) NOT NULL DEFAULT ''
) DEFAULT CHARSET=utf8;

--
-- TABLE cache
--
CREATE TABLE IF NOT EXISTS `cache` (
	`k` varchar(255) NOT NULL PRIMARY KEY,
	`v` text NOT NULL,
	`expire_mode` int(11) NOT NULL DEFAULT 0,
	`updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `updated` (`updated`)
) DEFAULT CHARSET=utf8;

--
-- TABLE challenge
--
CREATE TABLE IF NOT EXISTS `challenge` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`challenge` varchar(255) NOT NULL DEFAULT '',
	`dfrn-id` varchar(255) NOT NULL DEFAULT '',
	`expire` int(11) NOT NULL DEFAULT 0,
	`type` varchar(255) NOT NULL DEFAULT '',
	`last_update` varchar(255) NOT NULL DEFAULT ''
) DEFAULT CHARSET=utf8;

--
-- TABLE clients
--
CREATE TABLE IF NOT EXISTS `clients` (
	`client_id` varchar(20) NOT NULL PRIMARY KEY,
	`pw` varchar(20) NOT NULL DEFAULT '',
	`redirect_uri` varchar(200) NOT NULL DEFAULT '',
	`name` text,
	`icon` text,
	`uid` int(11) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE config
--
CREATE TABLE IF NOT EXISTS `config` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`cat` varchar(255) NOT NULL DEFAULT '',
	`k` varchar(255) NOT NULL DEFAULT '',
	`v` text NOT NULL,
	 INDEX `cat_k` (`cat`(30),`k`(30))
) DEFAULT CHARSET=utf8;

--
-- TABLE contact
--
CREATE TABLE IF NOT EXISTS `contact` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`self` tinyint(1) NOT NULL DEFAULT 0,
	`remote_self` tinyint(1) NOT NULL DEFAULT 0,
	`rel` tinyint(1) NOT NULL DEFAULT 0,
	`duplex` tinyint(1) NOT NULL DEFAULT 0,
	`network` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL DEFAULT '',
	`nick` varchar(255) NOT NULL DEFAULT '',
	`location` varchar(255) NOT NULL DEFAULT '',
	`about` text NOT NULL,
	`keywords` text NOT NULL,
	`gender` varchar(32) NOT NULL DEFAULT '',
	`attag` varchar(255) NOT NULL DEFAULT '',
	`photo` text NOT NULL,
	`thumb` text NOT NULL,
	`micro` text NOT NULL,
	`site-pubkey` text NOT NULL,
	`issued-id` varchar(255) NOT NULL DEFAULT '',
	`dfrn-id` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	`nurl` varchar(255) NOT NULL DEFAULT '',
	`addr` varchar(255) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`pubkey` text NOT NULL,
	`prvkey` text NOT NULL,
	`batch` varchar(255) NOT NULL DEFAULT '',
	`request` text NOT NULL,
	`notify` text NOT NULL,
	`poll` text NOT NULL,
	`confirm` text NOT NULL,
	`poco` text NOT NULL,
	`aes_allow` tinyint(1) NOT NULL DEFAULT 0,
	`ret-aes` tinyint(1) NOT NULL DEFAULT 0,
	`usehub` tinyint(1) NOT NULL DEFAULT 0,
	`subhub` tinyint(1) NOT NULL DEFAULT 0,
	`hub-verify` varchar(255) NOT NULL DEFAULT '',
	`last-update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`success_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`failure_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`name-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`uri-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`avatar-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`term-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`last-item` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`priority` tinyint(3) NOT NULL DEFAULT 0,
	`blocked` tinyint(1) NOT NULL DEFAULT 1,
	`readonly` tinyint(1) NOT NULL DEFAULT 0,
	`writable` tinyint(1) NOT NULL DEFAULT 0,
	`forum` tinyint(1) NOT NULL DEFAULT 0,
	`prv` tinyint(1) NOT NULL DEFAULT 0,
	`hidden` tinyint(1) NOT NULL DEFAULT 0,
	`archive` tinyint(1) NOT NULL DEFAULT 0,
	`pending` tinyint(1) NOT NULL DEFAULT 1,
	`rating` tinyint(1) NOT NULL DEFAULT 0,
	`reason` text NOT NULL,
	`closeness` tinyint(2) NOT NULL DEFAULT 99,
	`info` mediumtext NOT NULL,
	`profile-id` int(11) NOT NULL DEFAULT 0,
	`bdyear` varchar(4) NOT NULL DEFAULT '',
	`bd` date NOT NULL DEFAULT '0000-00-00',
	`notify_new_posts` tinyint(1) NOT NULL DEFAULT 0,
	`fetch_further_information` tinyint(1) NOT NULL DEFAULT 0,
	`ffi_keyword_blacklist` mediumtext NOT NULL,
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE conv
--
CREATE TABLE IF NOT EXISTS `conv` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`guid` varchar(64) NOT NULL DEFAULT '',
	`recips` mediumtext NOT NULL,
	`uid` int(11) NOT NULL DEFAULT 0,
	`creator` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`subject` mediumtext NOT NULL,
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE deliverq
--
CREATE TABLE IF NOT EXISTS `deliverq` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`cmd` varchar(32) NOT NULL DEFAULT '',
	`item` int(11) NOT NULL DEFAULT 0,
	`contact` int(11) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE dsprphotoq
--
CREATE TABLE IF NOT EXISTS `dsprphotoq` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`msg` mediumtext NOT NULL,
	`attempt` tinyint(4) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE event
--
CREATE TABLE IF NOT EXISTS `event` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`cid` int(11) NOT NULL DEFAULT 0,
	`uri` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`finish` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`summary` text NOT NULL,
	`desc` text NOT NULL,
	`location` text NOT NULL,
	`type` varchar(255) NOT NULL DEFAULT '',
	`nofinish` tinyint(1) NOT NULL DEFAULT 0,
	`adjust` tinyint(1) NOT NULL DEFAULT 1,
	`ignore` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`allow_cid` mediumtext NOT NULL,
	`allow_gid` mediumtext NOT NULL,
	`deny_cid` mediumtext NOT NULL,
	`deny_gid` mediumtext NOT NULL,
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE fcontact
--
CREATE TABLE IF NOT EXISTS `fcontact` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`url` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL DEFAULT '',
	`photo` varchar(255) NOT NULL DEFAULT '',
	`request` varchar(255) NOT NULL DEFAULT '',
	`nick` varchar(255) NOT NULL DEFAULT '',
	`addr` varchar(255) NOT NULL DEFAULT '',
	`batch` varchar(255) NOT NULL DEFAULT '',
	`notify` varchar(255) NOT NULL DEFAULT '',
	`poll` varchar(255) NOT NULL DEFAULT '',
	`confirm` varchar(255) NOT NULL DEFAULT '',
	`priority` tinyint(1) NOT NULL DEFAULT 0,
	`network` varchar(32) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`pubkey` text NOT NULL,
	`updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `addr` (`addr`)
) DEFAULT CHARSET=utf8;

--
-- TABLE ffinder
--
CREATE TABLE IF NOT EXISTS `ffinder` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`cid` int(10) unsigned NOT NULL DEFAULT 0,
	`fid` int(10) unsigned NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE fserver
--
CREATE TABLE IF NOT EXISTS `fserver` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`server` varchar(255) NOT NULL DEFAULT '',
	`posturl` varchar(255) NOT NULL DEFAULT '',
	`key` text NOT NULL,
	 INDEX `server` (`server`)
) DEFAULT CHARSET=utf8;

--
-- TABLE fsuggest
--
CREATE TABLE IF NOT EXISTS `fsuggest` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`cid` int(11) NOT NULL DEFAULT 0,
	`name` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	`request` varchar(255) NOT NULL DEFAULT '',
	`photo` varchar(255) NOT NULL DEFAULT '',
	`note` text NOT NULL,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) DEFAULT CHARSET=utf8;

--
-- TABLE gcign
--
CREATE TABLE IF NOT EXISTS `gcign` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`gcid` int(11) NOT NULL DEFAULT 0,
	 INDEX `uid` (`uid`),
	 INDEX `gcid` (`gcid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE gcontact
--
CREATE TABLE IF NOT EXISTS `gcontact` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`name` varchar(255) NOT NULL DEFAULT '',
	`nick` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	`nurl` varchar(255) NOT NULL DEFAULT '',
	`photo` varchar(255) NOT NULL DEFAULT '',
	`connect` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`updated` datetime DEFAULT '0000-00-00 00:00:00',
	`last_contact` datetime DEFAULT '0000-00-00 00:00:00',
	`last_failure` datetime DEFAULT '0000-00-00 00:00:00',
	`location` varchar(255) NOT NULL DEFAULT '',
	`about` text NOT NULL,
	`keywords` text NOT NULL,
	`gender` varchar(32) NOT NULL DEFAULT '',
	`community` tinyint(1) NOT NULL DEFAULT 0,
	`network` varchar(255) NOT NULL DEFAULT '',
	`addr` varchar(255) NOT NULL DEFAULT '',
	`generation` tinyint(3) NOT NULL DEFAULT 0,
	`server_url` varchar(255) NOT NULL DEFAULT '',
	 INDEX `nurl` (`nurl`),
	 INDEX `updated` (`updated`)
) DEFAULT CHARSET=utf8;

--
-- TABLE glink
--
CREATE TABLE IF NOT EXISTS `glink` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`cid` int(11) NOT NULL DEFAULT 0,
	`uid` int(11) NOT NULL DEFAULT 0,
	`gcid` int(11) NOT NULL DEFAULT 0,
	`zcid` int(11) NOT NULL DEFAULT 0,
	`updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `cid_uid_gcid_zcid` (`cid`,`uid`,`gcid`,`zcid`),
	 INDEX `gcid` (`gcid`),
	 INDEX `zcid` (`zcid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE group
--
CREATE TABLE IF NOT EXISTS `group` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`visible` tinyint(1) NOT NULL DEFAULT 0,
	`deleted` tinyint(1) NOT NULL DEFAULT 0,
	`name` varchar(255) NOT NULL DEFAULT '',
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE group_member
--
CREATE TABLE IF NOT EXISTS `group_member` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`gid` int(10) unsigned NOT NULL DEFAULT 0,
	`contact-id` int(10) unsigned NOT NULL DEFAULT 0,
	 INDEX `uid_gid_contactid` (`uid`,`gid`,`contact-id`)
) DEFAULT CHARSET=utf8;

--
-- TABLE gserver
--
CREATE TABLE IF NOT EXISTS `gserver` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`url` varchar(255) NOT NULL DEFAULT '',
	`nurl` varchar(255) NOT NULL DEFAULT '',
	`version` varchar(255) NOT NULL DEFAULT '',
	`site_name` varchar(255) NOT NULL DEFAULT '',
	`info` text NOT NULL,
	`register_policy` tinyint(1) NOT NULL DEFAULT 0,
	`poco` varchar(255) NOT NULL DEFAULT '',
	`noscrape` varchar(255) NOT NULL DEFAULT '',
	`network` varchar(32) NOT NULL DEFAULT '',
	`platform` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`last_poco_query` datetime DEFAULT '0000-00-00 00:00:00',
	`last_contact` datetime DEFAULT '0000-00-00 00:00:00',
	`last_failure` datetime DEFAULT '0000-00-00 00:00:00',
	 INDEX `nurl` (`nurl`)
) DEFAULT CHARSET=utf8;

--
-- TABLE guid
--
CREATE TABLE IF NOT EXISTS `guid` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`guid` varchar(255) NOT NULL DEFAULT '',
	`plink` varchar(255) NOT NULL DEFAULT '',
	`uri` varchar(255) NOT NULL DEFAULT '',
	`network` varchar(32) NOT NULL DEFAULT '',
	 INDEX `guid` (`guid`),
	 INDEX `plink` (`plink`),
	 INDEX `uri` (`uri`)
) DEFAULT CHARSET=utf8;

--
-- TABLE hook
--
CREATE TABLE IF NOT EXISTS `hook` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`hook` varchar(255) NOT NULL DEFAULT '',
	`file` varchar(255) NOT NULL DEFAULT '',
	`function` varchar(255) NOT NULL DEFAULT '',
	`priority` int(11) unsigned NOT NULL DEFAULT 0,
	 INDEX `hook_file_function` (`hook`(30),`file`(60),`function`(30))
) DEFAULT CHARSET=utf8;

--
-- TABLE intro
--
CREATE TABLE IF NOT EXISTS `intro` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`fid` int(11) NOT NULL DEFAULT 0,
	`contact-id` int(11) NOT NULL DEFAULT 0,
	`knowyou` tinyint(1) NOT NULL DEFAULT 0,
	`duplex` tinyint(1) NOT NULL DEFAULT 0,
	`note` text NOT NULL,
	`hash` varchar(255) NOT NULL DEFAULT '',
	`datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`blocked` tinyint(1) NOT NULL DEFAULT 1,
	`ignore` tinyint(1) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE item
--
CREATE TABLE IF NOT EXISTS `item` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`guid` varchar(255) NOT NULL DEFAULT '',
	`uri` varchar(255) NOT NULL DEFAULT '',
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`contact-id` int(11) NOT NULL DEFAULT 0,
	`type` varchar(255) NOT NULL DEFAULT '',
	`wall` tinyint(1) NOT NULL DEFAULT 0,
	`gravity` tinyint(1) NOT NULL DEFAULT 0,
	`parent` int(10) unsigned NOT NULL DEFAULT 0,
	`parent-uri` varchar(255) NOT NULL DEFAULT '',
	`extid` varchar(255) NOT NULL DEFAULT '',
	`thr-parent` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`commented` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`owner-name` varchar(255) NOT NULL DEFAULT '',
	`owner-link` varchar(255) NOT NULL DEFAULT '',
	`owner-avatar` varchar(255) NOT NULL DEFAULT '',
	`author-name` varchar(255) NOT NULL DEFAULT '',
	`author-link` varchar(255) NOT NULL DEFAULT '',
	`author-avatar` varchar(255) NOT NULL DEFAULT '',
	`title` varchar(255) NOT NULL DEFAULT '',
	`body` mediumtext NOT NULL,
	`app` varchar(255) NOT NULL DEFAULT '',
	`verb` varchar(255) NOT NULL DEFAULT '',
	`object-type` varchar(255) NOT NULL DEFAULT '',
	`object` text NOT NULL,
	`target-type` varchar(255) NOT NULL DEFAULT '',
	`target` text NOT NULL,
	`postopts` text NOT NULL,
	`plink` varchar(255) NOT NULL DEFAULT '',
	`resource-id` varchar(255) NOT NULL DEFAULT '',
	`event-id` int(11) NOT NULL DEFAULT 0,
	`tag` mediumtext NOT NULL,
	`attach` mediumtext NOT NULL,
	`inform` mediumtext NOT NULL,
	`file` mediumtext NOT NULL,
	`location` varchar(255) NOT NULL DEFAULT '',
	`coord` varchar(255) NOT NULL DEFAULT '',
	`allow_cid` mediumtext NOT NULL,
	`allow_gid` mediumtext NOT NULL,
	`deny_cid` mediumtext NOT NULL,
	`deny_gid` mediumtext NOT NULL,
	`private` tinyint(1) NOT NULL DEFAULT 0,
	`pubmail` tinyint(1) NOT NULL DEFAULT 0,
	`moderated` tinyint(1) NOT NULL DEFAULT 0,
	`visible` tinyint(1) NOT NULL DEFAULT 0,
	`spam` tinyint(1) NOT NULL DEFAULT 0,
	`starred` tinyint(1) NOT NULL DEFAULT 0,
	`bookmark` tinyint(1) NOT NULL DEFAULT 0,
	`unseen` tinyint(1) NOT NULL DEFAULT 1,
	`deleted` tinyint(1) NOT NULL DEFAULT 0,
	`origin` tinyint(1) NOT NULL DEFAULT 0,
	`forum_mode` tinyint(1) NOT NULL DEFAULT 0,
	`last-child` tinyint(1) unsigned NOT NULL DEFAULT 1,
	`mention` tinyint(1) NOT NULL DEFAULT 0,
	`network` varchar(32) NOT NULL DEFAULT '',
	`rendered-hash` varchar(32) NOT NULL DEFAULT '',
	`rendered-html` mediumtext NOT NULL,
	`global` tinyint(1) NOT NULL DEFAULT 0,
	 INDEX `guid` (`guid`),
	 INDEX `uri` (`uri`),
	 INDEX `parent` (`parent`),
	 INDEX `parent-uri` (`parent-uri`),
	 INDEX `extid` (`extid`),
	 INDEX `uid_id` (`uid`,`id`),
	 INDEX `uid_created` (`uid`,`created`),
	 INDEX `uid_unseen` (`uid`,`unseen`),
	 INDEX `uid_network_received` (`uid`,`network`,`received`),
	 INDEX `uid_received` (`uid`,`received`),
	 INDEX `uid_network_commented` (`uid`,`network`,`commented`),
	 INDEX `uid_commented` (`uid`,`commented`),
	 INDEX `uid_title` (`uid`,`title`),
	 INDEX `uid_thrparent` (`uid`,`thr-parent`),
	 INDEX `uid_parenturi` (`uid`,`parent-uri`),
	 INDEX `uid_contactid_created` (`uid`,`contact-id`,`created`),
	 INDEX `wall_body` (`wall`,`body`(6)),
	 INDEX `uid_visible_moderated_created` (`uid`,`visible`,`moderated`,`created`),
	 INDEX `uid_uri` (`uid`,`uri`),
	 INDEX `uid_wall_created` (`uid`,`wall`,`created`),
	 INDEX `resource-id` (`resource-id`),
	 INDEX `uid_type` (`uid`,`type`),
	 INDEX `uid_starred` (`uid`,`starred`),
	 INDEX `contactid_allowcid_allowpid_denycid_denygid` (`contact-id`,`allow_cid`(10),`allow_gid`(10),`deny_cid`(10),`deny_gid`(10)),
	 INDEX `uid_wall_parent_created` (`uid`,`wall`,`parent`,`created`),
	 INDEX `uid_type_changed` (`uid`,`type`,`changed`),
	 INDEX `contactid_verb` (`contact-id`,`verb`),
	 INDEX `deleted_changed` (`deleted`,`changed`),
	 INDEX `uid_wall_changed` (`uid`,`wall`,`changed`),
	 INDEX `uid_eventid` (`uid`,`event-id`),
	 INDEX `uid_authorlink` (`uid`,`author-link`),
	 INDEX `uid_ownerlink` (`uid`,`owner-link`)
) DEFAULT CHARSET=utf8;

--
-- TABLE item_id
--
CREATE TABLE IF NOT EXISTS `item_id` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`iid` int(11) NOT NULL DEFAULT 0,
	`uid` int(11) NOT NULL DEFAULT 0,
	`sid` varchar(255) NOT NULL DEFAULT '',
	`service` varchar(255) NOT NULL DEFAULT '',
	 INDEX `uid` (`uid`),
	 INDEX `sid` (`sid`),
	 INDEX `service` (`service`),
	 INDEX `iid` (`iid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE locks
--
CREATE TABLE IF NOT EXISTS `locks` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`name` varchar(128) NOT NULL DEFAULT '',
	`locked` tinyint(1) NOT NULL DEFAULT 0,
	`created` datetime DEFAULT '0000-00-00 00:00:00'
) DEFAULT CHARSET=utf8;

--
-- TABLE mail
--
CREATE TABLE IF NOT EXISTS `mail` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`guid` varchar(64) NOT NULL DEFAULT '',
	`from-name` varchar(255) NOT NULL DEFAULT '',
	`from-photo` varchar(255) NOT NULL DEFAULT '',
	`from-url` varchar(255) NOT NULL DEFAULT '',
	`contact-id` varchar(255) NOT NULL DEFAULT '',
	`convid` int(11) unsigned NOT NULL DEFAULT 0,
	`title` varchar(255) NOT NULL DEFAULT '',
	`body` mediumtext NOT NULL,
	`seen` tinyint(1) NOT NULL DEFAULT 0,
	`reply` tinyint(1) NOT NULL DEFAULT 0,
	`replied` tinyint(1) NOT NULL DEFAULT 0,
	`unknown` tinyint(1) NOT NULL DEFAULT 0,
	`uri` varchar(255) NOT NULL DEFAULT '',
	`parent-uri` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `uid` (`uid`),
	 INDEX `guid` (`guid`),
	 INDEX `convid` (`convid`),
	 INDEX `reply` (`reply`),
	 INDEX `uri` (`uri`),
	 INDEX `parent-uri` (`parent-uri`)
) DEFAULT CHARSET=utf8;

--
-- TABLE mailacct
--
CREATE TABLE IF NOT EXISTS `mailacct` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`server` varchar(255) NOT NULL DEFAULT '',
	`port` int(11) NOT NULL DEFAULT 0,
	`ssltype` varchar(16) NOT NULL DEFAULT '',
	`mailbox` varchar(255) NOT NULL DEFAULT '',
	`user` varchar(255) NOT NULL DEFAULT '',
	`pass` text NOT NULL,
	`reply_to` varchar(255) NOT NULL DEFAULT '',
	`action` int(11) NOT NULL DEFAULT 0,
	`movetofolder` varchar(255) NOT NULL DEFAULT '',
	`pubmail` tinyint(1) NOT NULL DEFAULT 0,
	`last_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) DEFAULT CHARSET=utf8;

--
-- TABLE manage
--
CREATE TABLE IF NOT EXISTS `manage` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`mid` int(11) NOT NULL DEFAULT 0,
	 INDEX `uid_mid` (`uid`,`mid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE notify
--
CREATE TABLE IF NOT EXISTS `notify` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`hash` varchar(64) NOT NULL DEFAULT '',
	`type` int(11) NOT NULL DEFAULT 0,
	`name` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	`photo` varchar(255) NOT NULL DEFAULT '',
	`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`msg` mediumtext NOT NULL,
	`uid` int(11) NOT NULL DEFAULT 0,
	`link` varchar(255) NOT NULL DEFAULT '',
	`iid` int(11) NOT NULL DEFAULT 0,
	`parent` int(11) NOT NULL DEFAULT 0,
	`seen` tinyint(1) NOT NULL DEFAULT 0,
	`verb` varchar(255) NOT NULL DEFAULT '',
	`otype` varchar(16) NOT NULL DEFAULT '',
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE notify-threads
--
CREATE TABLE IF NOT EXISTS `notify-threads` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`notify-id` int(11) NOT NULL DEFAULT 0,
	`master-parent-item` int(10) unsigned NOT NULL DEFAULT 0,
	`parent-item` int(10) unsigned NOT NULL DEFAULT 0,
	`receiver-uid` int(11) NOT NULL DEFAULT 0,
	 INDEX `master-parent-item` (`master-parent-item`),
	 INDEX `receiver-uid` (`receiver-uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE pconfig
--
CREATE TABLE IF NOT EXISTS `pconfig` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`cat` varchar(255) NOT NULL DEFAULT '',
	`k` varchar(255) NOT NULL DEFAULT '',
	`v` mediumtext NOT NULL,
	 INDEX `uid_cat_k` (`uid`,`cat`(30),`k`(30))
) DEFAULT CHARSET=utf8;

--
-- TABLE photo
--
CREATE TABLE IF NOT EXISTS `photo` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`contact-id` int(10) unsigned NOT NULL DEFAULT 0,
	`guid` varchar(64) NOT NULL DEFAULT '',
	`resource-id` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`title` varchar(255) NOT NULL DEFAULT '',
	`desc` text NOT NULL,
	`album` varchar(255) NOT NULL DEFAULT '',
	`filename` varchar(255) NOT NULL DEFAULT '',
	`type` varchar(128) NOT NULL DEFAULT 'image/jpeg',
	`height` smallint(6) NOT NULL DEFAULT 0,
	`width` smallint(6) NOT NULL DEFAULT 0,
	`datasize` int(10) unsigned NOT NULL DEFAULT 0,
	`data` mediumblob NOT NULL,
	`scale` tinyint(3) NOT NULL DEFAULT 0,
	`profile` tinyint(1) NOT NULL DEFAULT 0,
	`allow_cid` mediumtext NOT NULL,
	`allow_gid` mediumtext NOT NULL,
	`deny_cid` mediumtext NOT NULL,
	`deny_gid` mediumtext NOT NULL,
	 INDEX `uid` (`uid`),
	 INDEX `resource-id` (`resource-id`),
	 INDEX `guid` (`guid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE poll
--
CREATE TABLE IF NOT EXISTS `poll` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`q0` mediumtext NOT NULL,
	`q1` mediumtext NOT NULL,
	`q2` mediumtext NOT NULL,
	`q3` mediumtext NOT NULL,
	`q4` mediumtext NOT NULL,
	`q5` mediumtext NOT NULL,
	`q6` mediumtext NOT NULL,
	`q7` mediumtext NOT NULL,
	`q8` mediumtext NOT NULL,
	`q9` mediumtext NOT NULL,
	 INDEX `uid` (`uid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE poll_result
--
CREATE TABLE IF NOT EXISTS `poll_result` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`poll_id` int(11) NOT NULL DEFAULT 0,
	`choice` int(11) NOT NULL DEFAULT 0,
	 INDEX `poll_id` (`poll_id`),
	 INDEX `choice` (`choice`)
) DEFAULT CHARSET=utf8;

--
-- TABLE profile
--
CREATE TABLE IF NOT EXISTS `profile` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`profile-name` varchar(255) NOT NULL DEFAULT '',
	`is-default` tinyint(1) NOT NULL DEFAULT 0,
	`hide-friends` tinyint(1) NOT NULL DEFAULT 0,
	`name` varchar(255) NOT NULL DEFAULT '',
	`pdesc` varchar(255) NOT NULL DEFAULT '',
	`dob` varchar(32) NOT NULL DEFAULT '0000-00-00',
	`address` varchar(255) NOT NULL DEFAULT '',
	`locality` varchar(255) NOT NULL DEFAULT '',
	`region` varchar(255) NOT NULL DEFAULT '',
	`postal-code` varchar(32) NOT NULL DEFAULT '',
	`country-name` varchar(255) NOT NULL DEFAULT '',
	`hometown` varchar(255) NOT NULL DEFAULT '',
	`gender` varchar(32) NOT NULL DEFAULT '',
	`marital` varchar(255) NOT NULL DEFAULT '',
	`with` text NOT NULL,
	`howlong` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`sexual` varchar(255) NOT NULL DEFAULT '',
	`politic` varchar(255) NOT NULL DEFAULT '',
	`religion` varchar(255) NOT NULL DEFAULT '',
	`pub_keywords` text NOT NULL,
	`prv_keywords` text NOT NULL,
	`likes` text NOT NULL,
	`dislikes` text NOT NULL,
	`about` text NOT NULL,
	`summary` varchar(255) NOT NULL DEFAULT '',
	`music` text NOT NULL,
	`book` text NOT NULL,
	`tv` text NOT NULL,
	`film` text NOT NULL,
	`interest` text NOT NULL,
	`romance` text NOT NULL,
	`work` text NOT NULL,
	`education` text NOT NULL,
	`contact` text NOT NULL,
	`homepage` varchar(255) NOT NULL DEFAULT '',
	`photo` varchar(255) NOT NULL DEFAULT '',
	`thumb` varchar(255) NOT NULL DEFAULT '',
	`publish` tinyint(1) NOT NULL DEFAULT 0,
	`net-publish` tinyint(1) NOT NULL DEFAULT 0,
	 INDEX `hometown` (`hometown`)
) DEFAULT CHARSET=utf8;

--
-- TABLE profile_check
--
CREATE TABLE IF NOT EXISTS `profile_check` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`cid` int(10) unsigned NOT NULL DEFAULT 0,
	`dfrn_id` varchar(255) NOT NULL DEFAULT '',
	`sec` varchar(255) NOT NULL DEFAULT '',
	`expire` int(11) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE push_subscriber
--
CREATE TABLE IF NOT EXISTS `push_subscriber` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`callback_url` varchar(255) NOT NULL DEFAULT '',
	`topic` varchar(255) NOT NULL DEFAULT '',
	`nickname` varchar(255) NOT NULL DEFAULT '',
	`push` int(11) NOT NULL DEFAULT 0,
	`last_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`secret` varchar(255) NOT NULL DEFAULT ''
) DEFAULT CHARSET=utf8;

--
-- TABLE queue
--
CREATE TABLE IF NOT EXISTS `queue` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`cid` int(11) NOT NULL DEFAULT 0,
	`network` varchar(32) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`last` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`content` mediumtext NOT NULL,
	`batch` tinyint(1) NOT NULL DEFAULT 0,
	 INDEX `cid` (`cid`),
	 INDEX `created` (`created`),
	 INDEX `last` (`last`),
	 INDEX `network` (`network`),
	 INDEX `batch` (`batch`)
) DEFAULT CHARSET=utf8;

--
-- TABLE register
--
CREATE TABLE IF NOT EXISTS `register` (
	`id` int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
	`hash` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`uid` int(11) unsigned NOT NULL DEFAULT 0,
	`password` varchar(255) NOT NULL DEFAULT '',
	`language` varchar(16) NOT NULL DEFAULT ''
) DEFAULT CHARSET=utf8;

--
-- TABLE search
--
CREATE TABLE IF NOT EXISTS `search` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`term` varchar(255) NOT NULL DEFAULT '',
	 INDEX `uid` (`uid`),
	 INDEX `term` (`term`)
) DEFAULT CHARSET=utf8;

--
-- TABLE session
--
CREATE TABLE IF NOT EXISTS `session` (
	`id` bigint(20) unsigned NOT NULL auto_increment PRIMARY KEY,
	`sid` varchar(255) NOT NULL DEFAULT '',
	`data` text NOT NULL,
	`expire` int(10) unsigned NOT NULL DEFAULT 0,
	 INDEX `sid` (`sid`),
	 INDEX `expire` (`expire`)
) DEFAULT CHARSET=utf8;

--
-- TABLE sign
--
CREATE TABLE IF NOT EXISTS `sign` (
	`id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`iid` int(10) unsigned NOT NULL DEFAULT 0,
	`retract_iid` int(10) unsigned NOT NULL DEFAULT 0,
	`signed_text` mediumtext NOT NULL,
	`signature` text NOT NULL,
	`signer` varchar(255) NOT NULL DEFAULT '',
	 INDEX `iid` (`iid`),
	 INDEX `retract_iid` (`retract_iid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE spam
--
CREATE TABLE IF NOT EXISTS `spam` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`uid` int(11) NOT NULL DEFAULT 0,
	`spam` int(11) NOT NULL DEFAULT 0,
	`ham` int(11) NOT NULL DEFAULT 0,
	`term` varchar(255) NOT NULL DEFAULT '',
	`date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `uid` (`uid`),
	 INDEX `spam` (`spam`),
	 INDEX `ham` (`ham`),
	 INDEX `term` (`term`)
) DEFAULT CHARSET=utf8;

--
-- TABLE term
--
CREATE TABLE IF NOT EXISTS `term` (
	`tid` int(10) unsigned NOT NULL auto_increment PRIMARY KEY,
	`oid` int(10) unsigned NOT NULL DEFAULT 0,
	`otype` tinyint(3) unsigned NOT NULL DEFAULT 0,
	`type` tinyint(3) unsigned NOT NULL DEFAULT 0,
	`term` varchar(255) NOT NULL DEFAULT '',
	`url` varchar(255) NOT NULL DEFAULT '',
	`guid` varchar(255) NOT NULL DEFAULT '',
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`global` tinyint(1) NOT NULL DEFAULT 0,
	`aid` int(10) unsigned NOT NULL DEFAULT 0,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	 INDEX `oid_otype_type_term` (`oid`,`otype`,`type`,`term`),
	 INDEX `uid_term_tid` (`uid`,`term`,`tid`),
	 INDEX `type_term` (`type`,`term`),
	 INDEX `uid_otype_type_term_global_created` (`uid`,`otype`,`type`,`term`,`global`,`created`),
	 INDEX `otype_type_term_tid` (`otype`,`type`,`term`,`tid`),
	 INDEX `guid` (`guid`)
) DEFAULT CHARSET=utf8;

--
-- TABLE thread
--
CREATE TABLE IF NOT EXISTS `thread` (
	`iid` int(10) unsigned NOT NULL DEFAULT 0 PRIMARY KEY,
	`uid` int(10) unsigned NOT NULL DEFAULT 0,
	`contact-id` int(11) unsigned NOT NULL DEFAULT 0,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`commented` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`changed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`wall` tinyint(1) NOT NULL DEFAULT 0,
	`private` tinyint(1) NOT NULL DEFAULT 0,
	`pubmail` tinyint(1) NOT NULL DEFAULT 0,
	`moderated` tinyint(1) NOT NULL DEFAULT 0,
	`visible` tinyint(1) NOT NULL DEFAULT 0,
	`spam` tinyint(1) NOT NULL DEFAULT 0,
	`starred` tinyint(1) NOT NULL DEFAULT 0,
	`ignored` tinyint(1) NOT NULL DEFAULT 0,
	`bookmark` tinyint(1) NOT NULL DEFAULT 0,
	`unseen` tinyint(1) NOT NULL DEFAULT 1,
	`deleted` tinyint(1) NOT NULL DEFAULT 0,
	`origin` tinyint(1) NOT NULL DEFAULT 0,
	`forum_mode` tinyint(1) NOT NULL DEFAULT 0,
	`mention` tinyint(1) NOT NULL DEFAULT 0,
	`network` varchar(32) NOT NULL DEFAULT '',
	 INDEX `created` (`created`),
	 INDEX `commented` (`commented`),
	 INDEX `uid_network_commented` (`uid`,`network`,`commented`),
	 INDEX `uid_network_created` (`uid`,`network`,`created`),
	 INDEX `uid_contactid_commented` (`uid`,`contact-id`,`commented`),
	 INDEX `uid_contactid_created` (`uid`,`contact-id`,`created`),
	 INDEX `wall_private_received` (`wall`,`private`,`received`),
	 INDEX `uid_created` (`uid`,`created`),
	 INDEX `uid_commented` (`uid`,`commented`)
) DEFAULT CHARSET=utf8;

--
-- TABLE tokens
--
CREATE TABLE IF NOT EXISTS `tokens` (
	`id` varchar(40) NOT NULL PRIMARY KEY,
	`secret` text NOT NULL,
	`client_id` varchar(20) NOT NULL DEFAULT '',
	`expires` int(11) NOT NULL DEFAULT 0,
	`scope` varchar(200) NOT NULL DEFAULT '',
	`uid` int(11) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

--
-- TABLE unique_contacts
--
CREATE TABLE IF NOT EXISTS `unique_contacts` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`url` varchar(255) NOT NULL DEFAULT '',
	`nick` varchar(255) NOT NULL DEFAULT '',
	`name` varchar(255) NOT NULL DEFAULT '',
	`avatar` varchar(255) NOT NULL DEFAULT '',
	`location` varchar(255) NOT NULL DEFAULT '',
	`about` text NOT NULL,
	 INDEX `url` (`url`)
) DEFAULT CHARSET=utf8;

--
-- TABLE user
--
CREATE TABLE IF NOT EXISTS `user` (
	`uid` int(11) NOT NULL auto_increment PRIMARY KEY,
	`guid` varchar(64) NOT NULL DEFAULT '',
	`username` varchar(255) NOT NULL DEFAULT '',
	`password` varchar(255) NOT NULL DEFAULT '',
	`nickname` varchar(255) NOT NULL DEFAULT '',
	`email` varchar(255) NOT NULL DEFAULT '',
	`openid` varchar(255) NOT NULL DEFAULT '',
	`timezone` varchar(128) NOT NULL DEFAULT '',
	`language` varchar(32) NOT NULL DEFAULT 'en',
	`register_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`login_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`default-location` varchar(255) NOT NULL DEFAULT '',
	`allow_location` tinyint(1) NOT NULL DEFAULT 0,
	`theme` varchar(255) NOT NULL DEFAULT '',
	`pubkey` text NOT NULL,
	`prvkey` text NOT NULL,
	`spubkey` text NOT NULL,
	`sprvkey` text NOT NULL,
	`verified` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`blocked` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`blockwall` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`hidewall` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`blocktags` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`unkmail` tinyint(1) NOT NULL DEFAULT 0,
	`cntunkmail` int(11) NOT NULL DEFAULT 10,
	`notify-flags` int(11) unsigned NOT NULL DEFAULT 65535,
	`page-flags` int(11) unsigned NOT NULL DEFAULT 0,
	`prvnets` tinyint(1) NOT NULL DEFAULT 0,
	`pwdreset` varchar(255) NOT NULL DEFAULT '',
	`maxreq` int(11) NOT NULL DEFAULT 10,
	`expire` int(11) unsigned NOT NULL DEFAULT 0,
	`account_removed` tinyint(1) NOT NULL DEFAULT 0,
	`account_expired` tinyint(1) NOT NULL DEFAULT 0,
	`account_expires_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`expire_notification_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`service_class` varchar(32) NOT NULL DEFAULT '',
	`def_gid` int(11) NOT NULL DEFAULT 0,
	`allow_cid` mediumtext NOT NULL,
	`allow_gid` mediumtext NOT NULL,
	`deny_cid` mediumtext NOT NULL,
	`deny_gid` mediumtext NOT NULL,
	`openidserver` text NOT NULL,
	 INDEX `nickname` (`nickname`)
) DEFAULT CHARSET=utf8;

--
-- TABLE userd
--
CREATE TABLE IF NOT EXISTS `userd` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`username` varchar(255) NOT NULL,
	 INDEX `username` (`username`)
) DEFAULT CHARSET=utf8;

--
-- TABLE workerqueue
--
CREATE TABLE IF NOT EXISTS `workerqueue` (
	`id` int(11) NOT NULL auto_increment PRIMARY KEY,
	`parameter` text NOT NULL,
	`priority` tinyint(3) unsigned NOT NULL DEFAULT 0,
	`created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`pid` int(11) NOT NULL DEFAULT 0,
	`executed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	 INDEX `created` (`created`)
) DEFAULT CHARSET=utf8;


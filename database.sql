-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `addon`
--

CREATE TABLE IF NOT EXISTS `addon` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  `version` char(255) NOT NULL,
  `installed` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` bigint(20) NOT NULL DEFAULT '0',
  `plugin_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hidden` (`hidden`)  
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `attach`
--

CREATE TABLE IF NOT EXISTS `attach` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `hash` char(64) NOT NULL,
  `filename` char(255) NOT NULL,
  `filetype` char(64) NOT NULL,
  `filesize` int(11) NOT NULL,
  `data` longblob NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `auth_codes`
--

CREATE TABLE IF NOT EXISTS `auth_codes` (
  `id` varchar(40) NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE IF NOT EXISTS `cache` (
  `k` char(255) NOT NULL,
  `v` text NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `challenge`
--

CREATE TABLE IF NOT EXISTS `challenge` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `challenge` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  `type` char(255) NOT NULL,
  `last_update` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE IF NOT EXISTS `clients` (
  `client_id` varchar(20) NOT NULL,
  `pw` varchar(20) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL,
  `name` text,
  `icon` text,
  `uid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat` char(255) CHARACTER SET ascii NOT NULL,
  `k` char(255) CHARACTER SET ascii NOT NULL,
  `v` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`cat`,`k`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE IF NOT EXISTS `contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT 'owner uid',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `self` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'boolean 1 == info for local UID, primarily name and photo to use in item displays.',
  `remote_self` tinyint(1) NOT NULL DEFAULT '0',
  `rel` tinyint(1) NOT NULL DEFAULT '0',
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `network` char(255) NOT NULL,
  `name` char(255) NOT NULL,
  `nick` char(255) NOT NULL,
  `attag` char(255) NOT NULL,
  `photo` text NOT NULL COMMENT 'remote photo URL initially until approved',
  `thumb` text NOT NULL,
  `micro` text NOT NULL,
  `site-pubkey` text NOT NULL,
  `issued-id` char(255) NOT NULL,
  `dfrn-id` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `nurl` char(255) NOT NULL,
  `addr` char(255) NOT NULL,
  `alias` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `batch` char(255) NOT NULL,
  `request` text NOT NULL,
  `notify` text NOT NULL,
  `poll` text NOT NULL,
  `confirm` text NOT NULL,
  `poco` text NOT NULL,
  `aes_allow` tinyint(1) NOT NULL DEFAULT '0',
  `ret-aes` tinyint(1) NOT NULL DEFAULT '0',
  `usehub` tinyint(1) NOT NULL DEFAULT '0',
  `subhub` tinyint(1) NOT NULL DEFAULT '0',
  `hub-verify` char(255) NOT NULL,
  `last-update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `success_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uri-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `avatar-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `term-date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `priority` tinyint(3) NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `readonly` tinyint(1) NOT NULL DEFAULT '0',
  `writable` tinyint(1) NOT NULL DEFAULT '0',
  `forum` tinyint(1) NOT NULL DEFAULT '0',
  `prv` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `archive` tinyint(1) NOT NULL DEFAULT '0',
  `pending` tinyint(1) NOT NULL DEFAULT '1',
  `rating` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-5 reputation, 0 unknown, 1 call police, 5 inscrutable',
  `reason` text NOT NULL COMMENT 'why a rating was given - will help friends decide to make friends or not',
  `closeness` tinyint(2) NOT NULL DEFAULT '99',
  `info` mediumtext NOT NULL,
  `profile-id` int(11) NOT NULL DEFAULT '0' COMMENT 'which profile to display - 0 is public default',
  `bdyear` char(4) NOT NULL COMMENT 'birthday notify flag',
  `bd` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `self` (`self`),
  KEY `issued-id` (`issued-id`),
  KEY `dfrn-id` (`dfrn-id`),
  KEY `blocked` (`blocked`),
  KEY `readonly` (`readonly`),
  KEY `network` (`network`),
  KEY `name` (`name`),
  KEY `nick` (`nick`),
  KEY `attag` (`attag`),
  KEY `addr` (`addr`),
  KEY `url` (`url`),
  KEY `batch` (`batch`),
  KEY `nurl` (`nurl`),
  KEY `pending` (`pending`),
  KEY `hidden` (`hidden`),
  KEY `archive` (`archive`),
  KEY `forum` (`forum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conv`
--

CREATE TABLE IF NOT EXISTS `conv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) NOT NULL,
  `recips` mediumtext NOT NULL,
  `uid` int(11) NOT NULL,
  `creator` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `subject` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `deliverq`
--

CREATE TABLE IF NOT EXISTS `deliverq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cmd` char(32) NOT NULL,
  `item` int(11) NOT NULL,
  `contact` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE IF NOT EXISTS `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `uri` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `start` datetime NOT NULL,
  `finish` datetime NOT NULL,
  `summary` text NOT NULL,
  `desc` text NOT NULL,
  `location` text NOT NULL,
  `type` char(255) NOT NULL,
  `nofinish` tinyint(1) NOT NULL DEFAULT '0',
  `adjust` tinyint(1) NOT NULL DEFAULT '1',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` ( `uid` ),
  KEY `cid` ( `cid` ),
  KEY `uri` ( `uri` ),
  KEY `type` ( `type` ),
  KEY `start` ( `start` ),
  KEY `finish` ( `finish` ),
  KEY `adjust` ( `adjust` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fcontact`
--

CREATE TABLE IF NOT EXISTS `fcontact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` char(255) NOT NULL,
  `name` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `request` char(255) NOT NULL,
  `nick` char(255) NOT NULL,
  `addr` char(255) NOT NULL,
  `batch` char(255) NOT NULL,
  `notify` char(255) NOT NULL,
  `poll` char(255) NOT NULL,
  `confirm` char(255) NOT NULL,
  `priority` tinyint(1) NOT NULL,
  `network` char(32) NOT NULL,
  `alias` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `addr` (`addr`),
  KEY `network` (`network`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ffinder`
--

CREATE TABLE IF NOT EXISTS `ffinder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL,
  `fid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fserver`
--

CREATE TABLE IF NOT EXISTS `fserver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` char(255) NOT NULL,
  `posturl` char(255) NOT NULL,
  `key` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `server` (`server`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fsuggest`
--

CREATE TABLE IF NOT EXISTS `fsuggest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `request` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `note` text NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gcign`
--

CREATE TABLE IF NOT EXISTS `gcign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gcid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `gcid` (`gcid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gcontact`
--

CREATE TABLE IF NOT EXISTS `gcontact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `nurl` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `connect` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nurl` (`nurl`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `glink`
--

CREATE TABLE IF NOT EXISTS `glink` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `gcid` int(11) NOT NULL,
  `zcid` int(11) NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `uid` (`uid`),
  KEY `gcid` (`gcid`),
  KEY `zcid` (`zcid`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE IF NOT EXISTS `group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE IF NOT EXISTS `group_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `gid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `guid`
--

CREATE TABLE IF NOT EXISTS `guid` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `guid` (`guid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hook`
--

CREATE TABLE IF NOT EXISTS `hook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hook` char(255) NOT NULL,
  `file` char(255) NOT NULL,
  `function` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `intro`
--

CREATE TABLE IF NOT EXISTS `intro` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `fid` int(11) NOT NULL DEFAULT '0',
  `contact-id` int(11) NOT NULL,
  `knowyou` tinyint(1) NOT NULL,
  `duplex` tinyint(1) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `hash` char(255) NOT NULL,
  `datetime` datetime NOT NULL,
  `blocked` tinyint(1) NOT NULL DEFAULT '1',
  `ignore` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` char(64) NOT NULL,
  `uri` char(255) CHARACTER SET ascii NOT NULL,
  `uid` int(10) unsigned NOT NULL DEFAULT '0',
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` char(255) NOT NULL,
  `wall` tinyint(1) NOT NULL DEFAULT '0',
  `gravity` tinyint(1) NOT NULL DEFAULT '0',
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `parent-uri` char(255) CHARACTER SET ascii NOT NULL,
  `extid` char(255) NOT NULL,
  `thr-parent` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `commented` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `received` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `changed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `owner-name` char(255) NOT NULL,
  `owner-link` char(255) NOT NULL,
  `owner-avatar` char(255) NOT NULL,
  `author-name` char(255) NOT NULL,
  `author-link` char(255) NOT NULL,
  `author-avatar` char(255) NOT NULL,
  `title` char(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `app` char(255) NOT NULL,
  `verb` char(255) NOT NULL,
  `object-type` char(255) NOT NULL,
  `object` text NOT NULL,
  `target-type` char(255) NOT NULL,
  `target` text NOT NULL,
  `postopts` text NOT NULL,
  `plink` char(255) NOT NULL,
  `resource-id` char(255) NOT NULL,
  `event-id` int(11) NOT NULL,
  `tag` mediumtext NOT NULL,
  `attach` mediumtext NOT NULL,
  `inform` mediumtext NOT NULL,
  `file` mediumtext NOT NULL,
  `location` char(255) NOT NULL,
  `coord` char(255) NOT NULL,
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
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
  `last-child` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uri` (`uri`),
  KEY `uid` (`uid`),
  KEY `contact-id` (`contact-id`),
  KEY `type` (`type`),
  KEY `parent` (`parent`),
  KEY `parent-uri` (`parent-uri`),
  KEY `created` (`created`),
  KEY `edited` (`edited`),
  KEY `visible` (`visible`),
  KEY `deleted` (`deleted`),
  KEY `last-child` (`last-child`),
  KEY `unseen` (`unseen`),
  KEY `extid` (`extid`),
  KEY `received` (`received`),
  KEY `starred` (`starred`),
  KEY `guid` (`guid`),
  KEY `origin` (`origin`),
  KEY `wall` (`wall`),
  KEY `forum_mode` (`forum_mode`),
  KEY `author-link` (`author-link`),
  KEY `bookmark` (`bookmark`),
  KEY `moderated` (`moderated`),
  KEY `spam` (`spam`),
  KEY `author-name` (`author-name`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `body` (`body`),
  FULLTEXT KEY `allow_cid` (`allow_cid`),
  FULLTEXT KEY `allow_gid` (`allow_gid`),
  FULLTEXT KEY `deny_cid` (`deny_cid`),
  FULLTEXT KEY `deny_gid` (`deny_gid`),
  FULLTEXT KEY `tag` (`tag`),
  FULLTEXT KEY `file` (`file`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `item_id`
--

CREATE TABLE IF NOT EXISTS `item_id` (
  `iid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `sid` char(255) NOT NULL,
  `service` char(255) NOT NULL,
  PRIMARY KEY (`iid`),
  KEY `uid` (`uid`),
  KEY `sid` (`sid`),
  KEY `service` (`service`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `locks`
--

CREATE TABLE `locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(128) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mail`
--

CREATE TABLE IF NOT EXISTS `mail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `guid` char(64) NOT NULL,
  `from-name` char(255) NOT NULL,
  `from-photo` char(255) NOT NULL,
  `from-url` char(255) NOT NULL,
  `contact-id` char(255) NOT NULL,
  `convid` int(11) NOT NULL,
  `title` char(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `seen` tinyint(1) NOT NULL,
  `reply` tinyint(1) NOT NULL DEFAULT '0',
  `replied` tinyint(1) NOT NULL,
  `unknown` tinyint(1) NOT NULL DEFAULT '0',
  `uri` char(255) NOT NULL,
  `parent-uri` char(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `reply` (`reply`),
  KEY `uid` (`uid`),
  KEY `guid` (`guid`),
  KEY `seen` (`seen`),
  KEY `uri` (`uri`),
  KEY `parent-uri` (`parent-uri`),
  KEY `created` (`created`),
  KEY `convid` (`convid`),
  KEY `unknown` (`unknown`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mailacct`
--

CREATE TABLE IF NOT EXISTS `mailacct` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `server` char(255) NOT NULL,
  `port` int(11) NOT NULL,
  `ssltype` char(16) NOT NULL,
  `mailbox` char(255) NOT NULL,
  `user` char(255) NOT NULL,
  `pass` text NOT NULL,
  `action` int(11) NOT NULL,
  `movetofolder` char(255) NOT NULL,
  `reply_to` char(255) NOT NULL,
  `pubmail` tinyint(1) NOT NULL DEFAULT '0',
  `last_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `manage`
--

CREATE TABLE IF NOT EXISTS `manage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `mid` (`mid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notify`
--

CREATE TABLE IF NOT EXISTS `notify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` char(64) NOT NULL,
  `name` char(255) NOT NULL,
  `url` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `date` datetime NOT NULL,
  `msg` mediumtext NOT NULL,
  `uid` int(11) NOT NULL,
  `link` char(255) NOT NULL,
  `parent` int(11) NOT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL,
  `verb` char(255) NOT NULL,
  `otype` char(16) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `seen` (`seen`),
  KEY `uid` (`uid`),
  KEY `date` (`date`),
  KEY `hash` (`hash`),
  KEY `parent` (`parent`),
  KEY `link` (`link`),
  KEY `otype` (`otype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notify-threads`
--

CREATE TABLE IF NOT EXISTS `notify-threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notify-id` int(11) NOT NULL,
  `master-parent-item` int(10) unsigned NOT NULL DEFAULT '0',
  `parent-item` int(10) unsigned NOT NULL DEFAULT '0',
  `receiver-uid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `master-parent-item` (`master-parent-item`),
  KEY `receiver-uid` (`receiver-uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pconfig`
--

CREATE TABLE IF NOT EXISTS `pconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `cat` char(255) CHARACTER SET ascii NOT NULL,
  `k` char(255) CHARACTER SET ascii NOT NULL,
  `v` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access` (`uid`,`cat`,`k`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `photo`
--

CREATE TABLE IF NOT EXISTS `photo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `contact-id` int(10) unsigned NOT NULL DEFAULT '0',
  `guid` char(64) NOT NULL,
  `resource-id` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `edited` datetime NOT NULL,
  `title` char(255) NOT NULL,
  `desc` text NOT NULL,
  `album` char(255) NOT NULL,
  `filename` char(255) NOT NULL,
  `type` CHAR(128) NOT NULL DEFAULT 'image/jpeg',
  `height` smallint(6) NOT NULL,
  `width` smallint(6) NOT NULL,
  `data` mediumblob NOT NULL,
  `scale` tinyint(3) NOT NULL,
  `profile` tinyint(1) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `resource-id` (`resource-id`),
  KEY `album` (`album`),
  KEY `scale` (`scale`),
  KEY `profile` (`profile`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `poll`
--

CREATE TABLE IF NOT EXISTS `poll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
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
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `poll_result`
--

CREATE TABLE IF NOT EXISTS `poll_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `choice` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `choice` (`choice`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE IF NOT EXISTS `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `profile-name` char(255) NOT NULL,
  `is-default` tinyint(1) NOT NULL DEFAULT '0',
  `hide-friends` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  `pdesc` char(255) NOT NULL,
  `dob` char(32) NOT NULL DEFAULT '0000-00-00',
  `address` char(255) NOT NULL,
  `locality` char(255) NOT NULL,
  `region` char(255) NOT NULL,
  `postal-code` char(32) NOT NULL,
  `country-name` char(255) NOT NULL,
  `hometown` char(255) NOT NULL,
  `gender` char(32) NOT NULL,
  `marital` char(255) NOT NULL,
  `with` text NOT NULL,
  `howlong` datetime NOT NULL default '0000-00-00 00:00:00',
  `sexual` char(255) NOT NULL,
  `politic` char(255) NOT NULL,
  `religion` char(255) NOT NULL,
  `pub_keywords` text NOT NULL,
  `prv_keywords` text NOT NULL,
  `likes` text NOT NULL,
  `dislikes` text NOT NULL,
  `about` text NOT NULL,
  `summary` char(255) NOT NULL,
  `music` text NOT NULL,
  `book` text NOT NULL,
  `tv` text NOT NULL,
  `film` text NOT NULL,
  `interest` text NOT NULL,
  `romance` text NOT NULL,
  `work` text NOT NULL,
  `education` text NOT NULL,
  `contact` text NOT NULL,
  `homepage` char(255) NOT NULL,
  `photo` char(255) NOT NULL,
  `thumb` char(255) NOT NULL,
  `publish` tinyint(1) NOT NULL DEFAULT '0',
  `net-publish` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `pub_keywords` (`pub_keywords`),
  FULLTEXT KEY `prv_keywords` (`prv_keywords`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `profile_check`
--

CREATE TABLE IF NOT EXISTS `profile_check` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `dfrn_id` char(255) NOT NULL,
  `sec` char(255) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) NOT NULL,
  `network` char(32) NOT NULL,
  `created` datetime NOT NULL,
  `last` datetime NOT NULL,
  `content` mediumtext NOT NULL,
  `batch` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cid` (`cid`),
  KEY `network` (`network`),
  KEY `created` (`created`),
  KEY `last` (`last`),
  KEY `batch` (`batch`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE IF NOT EXISTS `register` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` char(255) NOT NULL,
  `created` datetime NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `password` char(255) NOT NULL,
  `language` char(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `search`
--

CREATE TABLE IF NOT EXISTS `search` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `term` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `term` (`term`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sid` char(255) NOT NULL,
  `data` text NOT NULL,
  `expire` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sid` (`sid`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sign`
--

CREATE TABLE IF NOT EXISTS `sign` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iid` int(10) unsigned NOT NULL DEFAULT '0',
  `retract_iid` int(10) unsigned NOT NULL DEFAULT '0',
  `signed_text` mediumtext NOT NULL,
  `signature` text NOT NULL,
  `signer` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `iid` (`iid`),
  KEY `retract_iid` (`retract_iid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `spam`
--

CREATE TABLE IF NOT EXISTS `spam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `spam` int(11) NOT NULL DEFAULT '0',
  `ham` int(11) NOT NULL DEFAULT '0',
  `term` char(255) NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `spam` (`spam`),
  KEY `ham` (`ham`),
  KEY `term` (`term`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` varchar(40) NOT NULL,
  `secret` text NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `expires` int(11) NOT NULL,
  `scope` varchar(200) NOT NULL,
  `uid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `guid` char(16) NOT NULL,
  `username` char(255) NOT NULL,
  `password` char(255) NOT NULL,
  `nickname` char(255) NOT NULL,
  `email` char(255) NOT NULL,
  `openid` char(255) NOT NULL,
  `timezone` char(128) NOT NULL,
  `language` char(16) NOT NULL DEFAULT 'en',
  `register_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `default-location` char(255) NOT NULL,
  `allow_location` tinyint(1) NOT NULL DEFAULT '0',
  `theme` char(255) NOT NULL,
  `pubkey` text NOT NULL,
  `prvkey` text NOT NULL,
  `spubkey` text NOT NULL,
  `sprvkey` text NOT NULL,
  `verified` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blocked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `blockwall` tinyint(1) NOT NULL DEFAULT '0',
  `hidewall` tinyint(1) NOT NULL DEFAULT '0',
  `blocktags` tinyint(1) NOT NULL DEFAULT '0',
  `unkmail` tinyint(1) NOT NULL DEFAULT '0',
  `cntunkmail` int(11) NOT NULL DEFAULT '10',
  `notify-flags` int(11) unsigned NOT NULL DEFAULT '65535',
  `page-flags` int(11) NOT NULL DEFAULT '0',
  `prvnets` tinyint(1) NOT NULL DEFAULT '0',
  `pwdreset` char(255) NOT NULL,
  `maxreq` int(11) NOT NULL DEFAULT '10',
  `expire` int(10) unsigned NOT NULL DEFAULT '0',
  `account_removed` tinyint(1) NOT NULL DEFAULT '0',
  `account_expired` tinyint(1) NOT NULL DEFAULT '0',
  `account_expires_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `expire_notification_sent` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `service_class` char(32) NOT NULL,
  `def_gid` int(11) NOT NULL DEFAULT '0',
  `allow_cid` mediumtext NOT NULL,
  `allow_gid` mediumtext NOT NULL,
  `deny_cid` mediumtext NOT NULL,
  `deny_gid` mediumtext NOT NULL,
  `openidserver` text NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `nickname` (`nickname`),
  KEY `login_date` (`login_date`),
  KEY `account_expired` (`account_expired`),
  KEY `hidewall` (`hidewall`),
  KEY `blockwall` (`blockwall`),
  KEY `blocked` (`blocked`),
  KEY `verified` (`verified`),
  KEY `unkmail` (`unkmail`),
  KEY `cntunkmail` (`cntunkmail`),
  KEY `account_removed` (`account_removed`),
  KEY `service_class` (`service_class`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `userd`
--

CREATE TABLE IF NOT EXISTS `userd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

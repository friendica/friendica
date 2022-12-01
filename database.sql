-- ------------------------------------------
-- Friendica 2022.12-dev (Giant Rhubarb)
-- DB_UPDATE_VERSION 1496
-- ------------------------------------------


--
-- TABLE gserver
--
CREATE TABLE IF NOT EXISTS `gserver` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`nurl` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`version` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`site_name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`info` text COMMENT '',
	`register_policy` tinyint NOT NULL DEFAULT 0 COMMENT '',
	`registered-users` int unsigned NOT NULL DEFAULT 0 COMMENT 'Number of registered users',
	`active-week-users` int unsigned COMMENT 'Number of active users in the last week',
	`active-month-users` int unsigned COMMENT 'Number of active users in the last month',
	`active-halfyear-users` int unsigned COMMENT 'Number of active users in the last six month',
	`local-posts` int unsigned COMMENT 'Number of local posts',
	`local-comments` int unsigned COMMENT 'Number of local comments',
	`directory-type` tinyint DEFAULT 0 COMMENT 'Type of directory service (Poco, Mastodon)',
	`poco` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`noscrape` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`protocol` tinyint unsigned COMMENT 'The protocol of the server',
	`platform` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`relay-subscribe` boolean NOT NULL DEFAULT '0' COMMENT 'Has the server subscribed to the relay system',
	`relay-scope` varchar(10) NOT NULL DEFAULT '' COMMENT 'The scope of messages that the server wants to get',
	`detection-method` tinyint unsigned COMMENT 'Method that had been used to detect that server',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_poco_query` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last_contact` datetime DEFAULT '0001-01-01 00:00:00' COMMENT 'Last successful connection request',
	`last_failure` datetime DEFAULT '0001-01-01 00:00:00' COMMENT 'Last failed connection request',
	`failed` boolean COMMENT 'Connection failed',
	`next_contact` datetime DEFAULT '0001-01-01 00:00:00' COMMENT 'Next connection request',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `nurl` (`nurl`(190)),
	 INDEX `next_contact` (`next_contact`),
	 INDEX `network` (`network`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Global servers';

--
-- TABLE user
--
CREATE TABLE IF NOT EXISTS `user` (
	`uid` mediumint unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`parent-uid` mediumint unsigned COMMENT 'The parent user that has full control about this user',
	`guid` varchar(64) NOT NULL DEFAULT '' COMMENT 'A unique identifier for this user',
	`username` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name that this user is known by',
	`password` varchar(255) NOT NULL DEFAULT '' COMMENT 'encrypted password',
	`legacy_password` boolean NOT NULL DEFAULT '0' COMMENT 'Is the password hash double-hashed?',
	`nickname` varchar(255) NOT NULL DEFAULT '' COMMENT 'nick- and user name',
	`email` varchar(255) NOT NULL DEFAULT '' COMMENT 'the users email address',
	`openid` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`timezone` varchar(128) NOT NULL DEFAULT '' COMMENT 'PHP-legal timezone',
	`language` varchar(32) NOT NULL DEFAULT 'en' COMMENT 'default language',
	`register_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'timestamp of registration',
	`login_date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'timestamp of last login',
	`last-activity` date COMMENT 'Day of the last activity',
	`default-location` varchar(255) NOT NULL DEFAULT '' COMMENT 'Default for item.location',
	`allow_location` boolean NOT NULL DEFAULT '0' COMMENT '1 allows to display the location',
	`theme` varchar(255) NOT NULL DEFAULT '' COMMENT 'user theme preference',
	`pubkey` text COMMENT 'RSA public key 4096 bit',
	`prvkey` text COMMENT 'RSA private key 4096 bit',
	`spubkey` text COMMENT '',
	`sprvkey` text COMMENT '',
	`verified` boolean NOT NULL DEFAULT '0' COMMENT 'user is verified through email',
	`blocked` boolean NOT NULL DEFAULT '0' COMMENT '1 for user is blocked',
	`blockwall` boolean NOT NULL DEFAULT '0' COMMENT 'Prohibit contacts to post to the profile page of the user',
	`hidewall` boolean NOT NULL DEFAULT '0' COMMENT 'Hide profile details from unkown viewers',
	`blocktags` boolean NOT NULL DEFAULT '0' COMMENT 'Prohibit contacts to tag the post of this user',
	`unkmail` boolean NOT NULL DEFAULT '0' COMMENT 'Permit unknown people to send private mails to this user',
	`cntunkmail` int unsigned NOT NULL DEFAULT 10 COMMENT '',
	`notify-flags` smallint unsigned NOT NULL DEFAULT 65535 COMMENT 'email notification options',
	`page-flags` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'page/profile type',
	`account-type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`prvnets` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pwdreset` varchar(255) COMMENT 'Password reset request token',
	`pwdreset_time` datetime COMMENT 'Timestamp of the last password reset request',
	`maxreq` int unsigned NOT NULL DEFAULT 10 COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT 'Delay in days before deleting user-related posts. Scope is controlled by pConfig.',
	`account_removed` boolean NOT NULL DEFAULT '0' COMMENT 'if 1 the account is removed',
	`account_expired` boolean NOT NULL DEFAULT '0' COMMENT '',
	`account_expires_on` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'timestamp when account expires and will be deleted',
	`expire_notification_sent` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'timestamp of last warning of account expiration',
	`def_gid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`allow_cid` mediumtext COMMENT 'default permission for this user',
	`allow_gid` mediumtext COMMENT 'default permission for this user',
	`deny_cid` mediumtext COMMENT 'default permission for this user',
	`deny_gid` mediumtext COMMENT 'default permission for this user',
	`openidserver` text COMMENT '',
	 PRIMARY KEY(`uid`),
	 INDEX `nickname` (`nickname`(32)),
	 INDEX `parent-uid` (`parent-uid`),
	 INDEX `guid` (`guid`),
	 INDEX `email` (`email`(64)),
	FOREIGN KEY (`parent-uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='The local users';

--
-- TABLE item-uri
--
CREATE TABLE IF NOT EXISTS `item-uri` (
	`id` int unsigned NOT NULL auto_increment,
	`uri` varbinary(383) NOT NULL COMMENT 'URI of an item',
	`guid` varbinary(255) COMMENT 'A unique identifier for an item',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uri` (`uri`),
	 INDEX `guid` (`guid`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='URI and GUID for items';

--
-- TABLE contact
--
CREATE TABLE IF NOT EXISTS `contact` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`updated` datetime DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last contact update',
	`network` char(4) NOT NULL DEFAULT '' COMMENT 'Network of the contact',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT 'Name that this contact is known by',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT 'Nick- and user name of the contact',
	`location` varchar(255) DEFAULT '' COMMENT '',
	`about` text COMMENT '',
	`keywords` text COMMENT 'public keywords (interests) of the contact',
	`xmpp` varchar(255) NOT NULL DEFAULT '' COMMENT 'XMPP address',
	`matrix` varchar(255) NOT NULL DEFAULT '' COMMENT 'Matrix address',
	`avatar` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`header` varbinary(383) COMMENT 'Header picture',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`nurl` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the contact url',
	`addr` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`alias` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`pubkey` text COMMENT 'RSA public key 4096 bit',
	`prvkey` text COMMENT 'RSA private key 4096 bit',
	`batch` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`notify` varbinary(383) COMMENT '',
	`poll` varbinary(383) COMMENT '',
	`subscribe` varbinary(383) COMMENT '',
	`last-update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last try to update the contact info',
	`next-update` datetime COMMENT 'Next connection request',
	`success_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last successful contact update',
	`failure_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last failed update',
	`failed` boolean COMMENT 'Connection failed',
	`term-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`last-item` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'date of the last post',
	`last-discovery` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'date of the last follower discovery',
	`local-data` boolean COMMENT 'Is true when there are posts with this contact on the system',
	`blocked` boolean NOT NULL DEFAULT '1' COMMENT 'Node-wide block status',
	`block_reason` text COMMENT 'Node-wide block reason',
	`readonly` boolean NOT NULL DEFAULT '0' COMMENT 'posts of the contact are readonly',
	`contact-type` tinyint NOT NULL DEFAULT 0 COMMENT 'Person, organisation, news, community, relay',
	`manually-approve` boolean COMMENT 'Contact requests have to be approved manually',
	`archive` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unsearchable` boolean NOT NULL DEFAULT '0' COMMENT 'Contact prefers to not be searchable',
	`sensitive` boolean NOT NULL DEFAULT '0' COMMENT 'Contact posts sensitive content',
	`baseurl` varbinary(383) DEFAULT '' COMMENT 'baseurl of the contact',
	`gsid` int unsigned COMMENT 'Global Server ID',
	`bd` date NOT NULL DEFAULT '0001-01-01' COMMENT '',
	`reason` text COMMENT '',
	`self` boolean NOT NULL DEFAULT '0' COMMENT '1 if the contact is the user him/her self',
	`remote_self` boolean NOT NULL DEFAULT '0' COMMENT '',
	`rel` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'The kind of the relation between the user and the contact',
	`protocol` char(4) NOT NULL DEFAULT '' COMMENT 'Protocol of the contact',
	`subhub` boolean NOT NULL DEFAULT '0' COMMENT '',
	`hub-verify` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`rating` tinyint NOT NULL DEFAULT 0 COMMENT 'Automatically detected feed poll frequency',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Feed poll priority',
	`attag` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pending` boolean NOT NULL DEFAULT '1' COMMENT 'Contact request is pending',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT 'Contact has been deleted',
	`info` mediumtext COMMENT '',
	`notify_new_posts` boolean NOT NULL DEFAULT '0' COMMENT '',
	`fetch_further_information` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`ffi_keyword_denylist` text COMMENT '',
	`photo` varbinary(383) DEFAULT '' COMMENT 'Link to the profile photo of the contact',
	`thumb` varbinary(383) DEFAULT '' COMMENT 'Link to the profile photo (thumb size)',
	`micro` varbinary(383) DEFAULT '' COMMENT 'Link to the profile photo (micro size)',
	`name-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`uri-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`avatar-date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`request` varbinary(383) COMMENT '',
	`confirm` varbinary(383) COMMENT '',
	`poco` varbinary(383) COMMENT '',
	`writable` boolean NOT NULL DEFAULT '0' COMMENT '',
	`forum` boolean NOT NULL DEFAULT '0' COMMENT 'contact is a forum. Deprecated, use \'contact-type\' = \'community\' and \'manually-approve\' = false instead',
	`prv` boolean NOT NULL DEFAULT '0' COMMENT 'contact is a private group. Deprecated, use \'contact-type\' = \'community\' and \'manually-approve\' = true instead',
	`bdyear` varchar(4) NOT NULL DEFAULT '' COMMENT '',
	`site-pubkey` text COMMENT 'Deprecated',
	`gender` varchar(32) NOT NULL DEFAULT '' COMMENT 'Deprecated',
	`duplex` boolean NOT NULL DEFAULT '0' COMMENT 'Deprecated',
	`issued-id` varbinary(383) NOT NULL DEFAULT '' COMMENT 'Deprecated',
	`dfrn-id` varbinary(383) NOT NULL DEFAULT '' COMMENT 'Deprecated',
	`aes_allow` boolean NOT NULL DEFAULT '0' COMMENT 'Deprecated',
	`ret-aes` boolean NOT NULL DEFAULT '0' COMMENT 'Deprecated',
	`usehub` boolean NOT NULL DEFAULT '0' COMMENT 'Deprecated',
	`closeness` tinyint unsigned NOT NULL DEFAULT 99 COMMENT 'Deprecated',
	`profile-id` int unsigned COMMENT 'Deprecated',
	 PRIMARY KEY(`id`),
	 INDEX `uid_name` (`uid`,`name`(190)),
	 INDEX `self_uid` (`self`,`uid`),
	 INDEX `alias_uid` (`alias`(128),`uid`),
	 INDEX `pending_uid` (`pending`,`uid`),
	 INDEX `blocked_uid` (`blocked`,`uid`),
	 INDEX `uid_rel_network_poll` (`uid`,`rel`,`network`,`poll`(64),`archive`),
	 INDEX `uid_network_batch` (`uid`,`network`,`batch`(64)),
	 INDEX `batch_contact-type` (`batch`(64),`contact-type`),
	 INDEX `addr_uid` (`addr`(128),`uid`),
	 INDEX `nurl_uid` (`nurl`(128),`uid`),
	 INDEX `nick_uid` (`nick`(128),`uid`),
	 INDEX `attag_uid` (`attag`(96),`uid`),
	 INDEX `network_uid_lastupdate` (`network`,`uid`,`last-update`),
	 INDEX `uid_network_self_lastupdate` (`uid`,`network`,`self`,`last-update`),
	 INDEX `next-update` (`next-update`),
	 INDEX `local-data-next-update` (`local-data`,`next-update`),
	 INDEX `uid_lastitem` (`uid`,`last-item`),
	 INDEX `baseurl` (`baseurl`(64)),
	 INDEX `uid_contact-type` (`uid`,`contact-type`),
	 INDEX `uid_self_contact-type` (`uid`,`self`,`contact-type`),
	 INDEX `self_network_uid` (`self`,`network`,`uid`),
	 INDEX `gsid_uid_failed` (`gsid`,`uid`,`failed`),
	 INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`gsid`) REFERENCES `gserver` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='contact table';

--
-- TABLE tag
--
CREATE TABLE IF NOT EXISTS `tag` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`name` varchar(96) NOT NULL DEFAULT '' COMMENT '',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`type` tinyint unsigned COMMENT 'Type of the tag (Unknown, General Collection, Follower Collection or Account)',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `type_name_url` (`name`,`url`),
	 INDEX `url` (`url`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='tags and mentions';

--
-- TABLE permissionset
--
CREATE TABLE IF NOT EXISTS `permissionset` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner id of this permission set',
	`allow_cid` mediumtext COMMENT 'Access Control - list of allowed contact.id \'<19><78>\'',
	`allow_gid` mediumtext COMMENT 'Access Control - list of allowed groups',
	`deny_cid` mediumtext COMMENT 'Access Control - list of denied contact.id',
	`deny_gid` mediumtext COMMENT 'Access Control - list of denied groups',
	 PRIMARY KEY(`id`),
	 INDEX `uid_allow_cid_allow_gid_deny_cid_deny_gid` (`uid`,`allow_cid`(50),`allow_gid`(30),`deny_cid`(50),`deny_gid`(30)),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE verb
--
CREATE TABLE IF NOT EXISTS `verb` (
	`id` smallint unsigned NOT NULL auto_increment,
	`name` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `name` (`name`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Activity Verbs';

--
-- TABLE 2fa_app_specific_password
--
CREATE TABLE IF NOT EXISTS `2fa_app_specific_password` (
	`id` mediumint unsigned NOT NULL auto_increment COMMENT 'Password ID for revocation',
	`uid` mediumint unsigned NOT NULL COMMENT 'User ID',
	`description` varchar(255) COMMENT 'Description of the usage of the password',
	`hashed_password` varchar(255) NOT NULL COMMENT 'Hashed password',
	`generated` datetime NOT NULL COMMENT 'Datetime the password was generated',
	`last_used` datetime COMMENT 'Datetime the password was last used',
	 PRIMARY KEY(`id`),
	 INDEX `uid_description` (`uid`,`description`(190)),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Two-factor app-specific _password';

--
-- TABLE 2fa_recovery_codes
--
CREATE TABLE IF NOT EXISTS `2fa_recovery_codes` (
	`uid` mediumint unsigned NOT NULL COMMENT 'User ID',
	`code` varchar(50) NOT NULL COMMENT 'Recovery code string',
	`generated` datetime NOT NULL COMMENT 'Datetime the code was generated',
	`used` datetime COMMENT 'Datetime the code was used',
	 PRIMARY KEY(`uid`,`code`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Two-factor authentication recovery codes';

--
-- TABLE 2fa_trusted_browser
--
CREATE TABLE IF NOT EXISTS `2fa_trusted_browser` (
	`cookie_hash` varchar(80) NOT NULL COMMENT 'Trusted cookie hash',
	`uid` mediumint unsigned NOT NULL COMMENT 'User ID',
	`user_agent` text COMMENT 'User agent string',
	`trusted` boolean NOT NULL DEFAULT '1' COMMENT 'Whenever this browser should be trusted or not',
	`created` datetime NOT NULL COMMENT 'Datetime the trusted browser was recorded',
	`last_used` datetime COMMENT 'Datetime the trusted browser was last used',
	 PRIMARY KEY(`cookie_hash`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Two-factor authentication trusted browsers';

--
-- TABLE account-suggestion
--
CREATE TABLE IF NOT EXISTS `account-suggestion` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the account url',
	`uid` mediumint unsigned NOT NULL COMMENT 'User ID',
	`level` smallint unsigned COMMENT 'level of closeness',
	`ignore` boolean NOT NULL DEFAULT '0' COMMENT 'If set, this account will not be suggested again',
	 PRIMARY KEY(`uid`,`uri-id`),
	 INDEX `uri-id_uid` (`uri-id`,`uid`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Account suggestion';

--
-- TABLE account-user
--
CREATE TABLE IF NOT EXISTS `account-user` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the account url',
	`uid` mediumint unsigned NOT NULL COMMENT 'User ID',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uri-id_uid` (`uri-id`,`uid`),
	 INDEX `uid_uri-id` (`uid`,`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Remote and local accounts';

--
-- TABLE addon
--
CREATE TABLE IF NOT EXISTS `addon` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`name` varchar(50) NOT NULL DEFAULT '' COMMENT 'addon base (file)name',
	`version` varchar(50) NOT NULL DEFAULT '' COMMENT 'currently unused',
	`installed` boolean NOT NULL DEFAULT '0' COMMENT 'currently always 1',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT 'currently unused',
	`timestamp` int unsigned NOT NULL DEFAULT 0 COMMENT 'file timestamp to check for reloads',
	`plugin_admin` boolean NOT NULL DEFAULT '0' COMMENT '1 = has admin config, 0 = has no admin config',
	 PRIMARY KEY(`id`),
	 INDEX `installed_name` (`installed`,`name`),
	 UNIQUE INDEX `name` (`name`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='registered addons';

--
-- TABLE apcontact
--
CREATE TABLE IF NOT EXISTS `apcontact` (
	`url` varbinary(383) NOT NULL COMMENT 'URL of the contact',
	`uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the apcontact url',
	`uuid` varbinary(255) COMMENT '',
	`type` varchar(20) NOT NULL COMMENT '',
	`following` varbinary(383) COMMENT '',
	`followers` varbinary(383) COMMENT '',
	`inbox` varbinary(383) NOT NULL COMMENT '',
	`outbox` varbinary(383) COMMENT '',
	`sharedinbox` varbinary(383) COMMENT '',
	`featured` varbinary(383) COMMENT 'Address for the collection of featured posts',
	`featured-tags` varbinary(383) COMMENT 'Address for the collection of featured tags',
	`manually-approve` boolean COMMENT '',
	`discoverable` boolean COMMENT 'Mastodon extension: true if profile is published in their directory',
	`suspended` boolean COMMENT 'Mastodon extension: true if profile is suspended',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`name` varchar(255) COMMENT '',
	`about` text COMMENT '',
	`xmpp` varchar(255) COMMENT 'XMPP address',
	`matrix` varchar(255) COMMENT 'Matrix address',
	`photo` varbinary(383) COMMENT '',
	`header` varbinary(383) COMMENT 'Header picture',
	`addr` varchar(255) COMMENT '',
	`alias` varbinary(383) COMMENT '',
	`pubkey` text COMMENT '',
	`subscribe` varbinary(383) COMMENT '',
	`baseurl` varbinary(383) COMMENT 'baseurl of the ap contact',
	`gsid` int unsigned COMMENT 'Global Server ID',
	`generator` varchar(255) COMMENT 'Name of the contact\'s system',
	`following_count` int unsigned DEFAULT 0 COMMENT 'Number of following contacts',
	`followers_count` int unsigned DEFAULT 0 COMMENT 'Number of followers',
	`statuses_count` int unsigned DEFAULT 0 COMMENT 'Number of posts',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`url`),
	 INDEX `addr` (`addr`(32)),
	 INDEX `alias` (`alias`(190)),
	 INDEX `followers` (`followers`(190)),
	 INDEX `baseurl` (`baseurl`(190)),
	 INDEX `sharedinbox` (`sharedinbox`(190)),
	 INDEX `gsid` (`gsid`),
	 UNIQUE INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`gsid`) REFERENCES `gserver` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='ActivityPub compatible contacts - used in the ActivityPub implementation';

--
-- TABLE application
--
CREATE TABLE IF NOT EXISTS `application` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'generated index',
	`client_id` varchar(64) NOT NULL COMMENT '',
	`client_secret` varchar(64) NOT NULL COMMENT '',
	`name` varchar(255) NOT NULL COMMENT '',
	`redirect_uri` varbinary(383) NOT NULL COMMENT '',
	`website` varbinary(383) COMMENT '',
	`scopes` varchar(255) COMMENT '',
	`read` boolean COMMENT 'Read scope',
	`write` boolean COMMENT 'Write scope',
	`follow` boolean COMMENT 'Follow scope',
	`push` boolean COMMENT 'Push scope',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `client_id` (`client_id`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='OAuth application';

--
-- TABLE application-marker
--
CREATE TABLE IF NOT EXISTS `application-marker` (
	`application-id` int unsigned NOT NULL COMMENT '',
	`uid` mediumint unsigned NOT NULL COMMENT 'Owner User id',
	`timeline` varchar(64) NOT NULL COMMENT 'Marker (home, notifications)',
	`last_read_id` varbinary(383) COMMENT 'Marker id for the timeline',
	`version` smallint unsigned COMMENT 'Version number',
	`updated_at` datetime COMMENT 'creation time',
	 PRIMARY KEY(`application-id`,`uid`,`timeline`),
	 INDEX `uid_id` (`uid`),
	FOREIGN KEY (`application-id`) REFERENCES `application` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Timeline marker';

--
-- TABLE application-token
--
CREATE TABLE IF NOT EXISTS `application-token` (
	`application-id` int unsigned NOT NULL COMMENT '',
	`uid` mediumint unsigned NOT NULL COMMENT 'Owner User id',
	`code` varchar(64) NOT NULL COMMENT '',
	`access_token` varchar(64) NOT NULL COMMENT '',
	`created_at` datetime NOT NULL COMMENT 'creation time',
	`scopes` varchar(255) COMMENT '',
	`read` boolean COMMENT 'Read scope',
	`write` boolean COMMENT 'Write scope',
	`follow` boolean COMMENT 'Follow scope',
	`push` boolean COMMENT 'Push scope',
	 PRIMARY KEY(`application-id`,`uid`),
	 INDEX `uid_id` (`uid`,`application-id`),
	FOREIGN KEY (`application-id`) REFERENCES `application` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='OAuth user token';

--
-- TABLE attach
--
CREATE TABLE IF NOT EXISTS `attach` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'generated index',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`hash` varchar(64) NOT NULL DEFAULT '' COMMENT 'hash',
	`filename` varchar(255) NOT NULL DEFAULT '' COMMENT 'filename of original',
	`filetype` varchar(64) NOT NULL DEFAULT '' COMMENT 'mimetype',
	`filesize` int unsigned NOT NULL DEFAULT 0 COMMENT 'size in bytes',
	`data` longblob NOT NULL COMMENT 'file data',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation time',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'last edit time',
	`allow_cid` mediumtext COMMENT 'Access Control - list of allowed contact.id \'<19><78>',
	`allow_gid` mediumtext COMMENT 'Access Control - list of allowed groups',
	`deny_cid` mediumtext COMMENT 'Access Control - list of denied contact.id',
	`deny_gid` mediumtext COMMENT 'Access Control - list of denied groups',
	`backend-class` tinytext COMMENT 'Storage backend class',
	`backend-ref` text COMMENT 'Storage backend data reference',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='file attachments';

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
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Stores temporary data';

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
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='main configuration storage';

--
-- TABLE contact-relation
--
CREATE TABLE IF NOT EXISTS `contact-relation` (
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact the related contact had interacted with',
	`relation-cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'related contact who had interacted with the contact',
	`last-interaction` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last interaction',
	`follow-updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last update of the contact relationship',
	`follows` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`cid`,`relation-cid`),
	 INDEX `relation-cid` (`relation-cid`),
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`relation-cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Contact relations';

--
-- TABLE conv
--
CREATE TABLE IF NOT EXISTS `conv` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`guid` varbinary(255) NOT NULL DEFAULT '' COMMENT 'A unique identifier for this conversation',
	`recips` text COMMENT 'sender_handle;recipient_handle',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`creator` varchar(255) NOT NULL DEFAULT '' COMMENT 'handle of creator',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation timestamp',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'edited timestamp',
	`subject` text COMMENT 'subject of initial message',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='private messages';

--
-- TABLE workerqueue
--
CREATE TABLE IF NOT EXISTS `workerqueue` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'Auto incremented worker task id',
	`command` varchar(100) COMMENT 'Task command',
	`parameter` mediumtext COMMENT 'Task parameter',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Task priority',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation date',
	`pid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Process id of the worker',
	`executed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Execution date',
	`next_try` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Next retrial date',
	`retrial` tinyint NOT NULL DEFAULT 0 COMMENT 'Retrial counter',
	`done` boolean NOT NULL DEFAULT '0' COMMENT 'Marked 1 when the task was done - will be deleted later',
	 PRIMARY KEY(`id`),
	 INDEX `command` (`command`),
	 INDEX `done_command_parameter` (`done`,`command`,`parameter`(64)),
	 INDEX `done_executed` (`done`,`executed`),
	 INDEX `done_priority_retrial_created` (`done`,`priority`,`retrial`,`created`),
	 INDEX `done_priority_next_try` (`done`,`priority`,`next_try`),
	 INDEX `done_pid_next_try` (`done`,`pid`,`next_try`),
	 INDEX `done_pid_retrial` (`done`,`pid`,`retrial`),
	 INDEX `done_pid_priority_created` (`done`,`pid`,`priority`,`created`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Background tasks queue entries';

--
-- TABLE delayed-post
--
CREATE TABLE IF NOT EXISTS `delayed-post` (
	`id` int unsigned NOT NULL auto_increment,
	`uri` varbinary(383) COMMENT 'URI of the post that will be distributed later',
	`uid` mediumint unsigned COMMENT 'Owner User id',
	`delayed` datetime COMMENT 'delay time',
	`wid` int unsigned COMMENT 'Workerqueue id',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_uri` (`uid`,`uri`(190)),
	 INDEX `wid` (`wid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`wid`) REFERENCES `workerqueue` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Posts that are about to be distributed at a later time';

--
-- TABLE diaspora-interaction
--
CREATE TABLE IF NOT EXISTS `diaspora-interaction` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`interaction` mediumtext COMMENT 'The Diaspora interaction',
	 PRIMARY KEY(`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Signed Diaspora Interaction';

--
-- TABLE endpoint
--
CREATE TABLE IF NOT EXISTS `endpoint` (
	`url` varbinary(383) NOT NULL COMMENT 'URL of the contact',
	`type` varchar(20) NOT NULL COMMENT '',
	`owner-uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the apcontact url',
	 PRIMARY KEY(`url`),
	 UNIQUE INDEX `owner-uri-id_type` (`owner-uri-id`,`type`),
	FOREIGN KEY (`owner-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='ActivityPub endpoints - used in the ActivityPub implementation';

--
-- TABLE event
--
CREATE TABLE IF NOT EXISTS `event` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`guid` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact_id (ID of the contact in contact table)',
	`uri` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the event uri',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation time',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'last edit time',
	`start` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'event start time',
	`finish` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'event end time',
	`summary` text COMMENT 'short description or title of the event',
	`desc` text COMMENT 'event description',
	`location` text COMMENT 'event location',
	`type` varchar(20) NOT NULL DEFAULT '' COMMENT 'event or birthday',
	`nofinish` boolean NOT NULL DEFAULT '0' COMMENT 'if event does have no end this is 1',
	`ignore` boolean NOT NULL DEFAULT '0' COMMENT '0 or 1',
	`allow_cid` mediumtext COMMENT 'Access Control - list of allowed contact.id \'<19><78>\'',
	`allow_gid` mediumtext COMMENT 'Access Control - list of allowed groups',
	`deny_cid` mediumtext COMMENT 'Access Control - list of denied contact.id',
	`deny_gid` mediumtext COMMENT 'Access Control - list of denied groups',
	 PRIMARY KEY(`id`),
	 INDEX `uid_start` (`uid`,`start`),
	 INDEX `cid` (`cid`),
	 INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Events';

--
-- TABLE fcontact
--
CREATE TABLE IF NOT EXISTS `fcontact` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`guid` varbinary(255) NOT NULL DEFAULT '' COMMENT 'unique id',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the fcontact url',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`photo` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`request` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`nick` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`addr` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`batch` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`notify` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`poll` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`confirm` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`priority` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`alias` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`pubkey` text COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`interacting_count` int unsigned DEFAULT 0 COMMENT 'Number of contacts this contact interactes with',
	`interacted_count` int unsigned DEFAULT 0 COMMENT 'Number of contacts that interacted with this contact',
	`post_count` int unsigned DEFAULT 0 COMMENT 'Number of posts and comments',
	 PRIMARY KEY(`id`),
	 INDEX `addr` (`addr`(32)),
	 UNIQUE INDEX `url` (`url`(190)),
	 UNIQUE INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Diaspora compatible contacts - used in the Diaspora implementation';

--
-- TABLE fetch-entry
--
CREATE TABLE IF NOT EXISTS `fetch-entry` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`url` varbinary(383) COMMENT 'url that awaiting to be fetched',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation date of the fetch request',
	`wid` int unsigned COMMENT 'Workerqueue id',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `url` (`url`),
	 INDEX `created` (`created`),
	 INDEX `wid` (`wid`),
	FOREIGN KEY (`wid`) REFERENCES `workerqueue` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE fsuggest
--
CREATE TABLE IF NOT EXISTS `fsuggest` (
	`id` int unsigned NOT NULL auto_increment COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`request` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`photo` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`note` text COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `cid` (`cid`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='friend suggestion stuff';

--
-- TABLE group
--
CREATE TABLE IF NOT EXISTS `group` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '1 indicates the member list is not private',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT '1 indicates the group has been deleted',
	`cid` int unsigned COMMENT 'Contact id of forum. When this field is filled then the members are synced automatically.',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT 'human readable name of group',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	 INDEX `cid` (`cid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='privacy groups, group info';

--
-- TABLE group_member
--
CREATE TABLE IF NOT EXISTS `group_member` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`gid` int unsigned NOT NULL DEFAULT 0 COMMENT 'groups.id of the associated group',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact.id of the member assigned to the associated group',
	 PRIMARY KEY(`id`),
	 INDEX `contactid` (`contact-id`),
	 UNIQUE INDEX `gid_contactid` (`gid`,`contact-id`),
	FOREIGN KEY (`gid`) REFERENCES `group` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='privacy groups, member info';

--
-- TABLE gserver-tag
--
CREATE TABLE IF NOT EXISTS `gserver-tag` (
	`gserver-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'The id of the gserver',
	`tag` varchar(100) NOT NULL DEFAULT '' COMMENT 'Tag that the server has subscribed',
	 PRIMARY KEY(`gserver-id`,`tag`),
	 INDEX `tag` (`tag`),
	FOREIGN KEY (`gserver-id`) REFERENCES `gserver` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Tags that the server has subscribed';

--
-- TABLE hook
--
CREATE TABLE IF NOT EXISTS `hook` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`hook` varbinary(100) NOT NULL DEFAULT '' COMMENT 'name of hook',
	`file` varbinary(200) NOT NULL DEFAULT '' COMMENT 'relative filename of hook handler',
	`function` varbinary(200) NOT NULL DEFAULT '' COMMENT 'function name of hook handler',
	`priority` smallint unsigned NOT NULL DEFAULT 0 COMMENT 'not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order',
	 PRIMARY KEY(`id`),
	 INDEX `priority` (`priority`),
	 UNIQUE INDEX `hook_file_function` (`hook`,`file`,`function`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='addon hook registry';

--
-- TABLE inbox-entry
--
CREATE TABLE IF NOT EXISTS `inbox-entry` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`activity-id` varbinary(383) COMMENT 'id of the incoming activity',
	`object-id` varbinary(383) COMMENT '',
	`in-reply-to-id` varbinary(383) COMMENT '',
	`conversation` varbinary(383) COMMENT '',
	`type` varchar(64) COMMENT 'Type of the activity',
	`object-type` varchar(64) COMMENT 'Type of the object activity',
	`object-object-type` varchar(64) COMMENT 'Type of the object\'s object activity',
	`received` datetime COMMENT 'Receiving date',
	`activity` mediumtext COMMENT 'The JSON activity',
	`signer` varchar(255) COMMENT '',
	`push` boolean COMMENT 'Is the entry pushed or have pulled it?',
	`trust` boolean COMMENT 'Do we trust this entry?',
	`wid` int unsigned COMMENT 'Workerqueue id',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `activity-id` (`activity-id`),
	 INDEX `object-id` (`object-id`),
	 INDEX `received` (`received`),
	 INDEX `wid` (`wid`),
	FOREIGN KEY (`wid`) REFERENCES `workerqueue` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Incoming activity';

--
-- TABLE inbox-entry-receiver
--
CREATE TABLE IF NOT EXISTS `inbox-entry-receiver` (
	`queue-id` int unsigned NOT NULL COMMENT '',
	`uid` mediumint unsigned NOT NULL COMMENT 'User id',
	 PRIMARY KEY(`queue-id`,`uid`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`queue-id`) REFERENCES `inbox-entry` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Receiver for the incoming activity';

--
-- TABLE inbox-status
--
CREATE TABLE IF NOT EXISTS `inbox-status` (
	`url` varbinary(383) NOT NULL COMMENT 'URL of the inbox',
	`uri-id` int unsigned COMMENT 'Item-uri id of inbox url',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation date of this entry',
	`success` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last successful delivery',
	`failure` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of the last failed delivery',
	`previous` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Previous delivery date',
	`archive` boolean NOT NULL DEFAULT '0' COMMENT 'Is the inbox archived?',
	`shared` boolean NOT NULL DEFAULT '0' COMMENT 'Is it a shared inbox?',
	 PRIMARY KEY(`url`),
	 INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Status of ActivityPub inboxes';

--
-- TABLE intro
--
CREATE TABLE IF NOT EXISTS `intro` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`fid` int unsigned COMMENT 'deprecated',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`suggest-cid` int unsigned COMMENT 'Suggested contact',
	`knowyou` boolean NOT NULL DEFAULT '0' COMMENT '',
	`duplex` boolean NOT NULL DEFAULT '0' COMMENT 'deprecated',
	`note` text COMMENT '',
	`hash` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`datetime` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`blocked` boolean NOT NULL DEFAULT '0' COMMENT 'deprecated',
	`ignore` boolean NOT NULL DEFAULT '0' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `contact-id` (`contact-id`),
	 INDEX `suggest-cid` (`suggest-cid`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`suggest-cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE locks
--
CREATE TABLE IF NOT EXISTS `locks` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`name` varchar(128) NOT NULL DEFAULT '' COMMENT '',
	`locked` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Process ID',
	`expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of cache expiration',
	 PRIMARY KEY(`id`),
	 INDEX `name_expires` (`name`,`expires`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE mail
--
CREATE TABLE IF NOT EXISTS `mail` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`guid` varbinary(255) NOT NULL DEFAULT '' COMMENT 'A unique identifier for this private message',
	`from-name` varchar(255) NOT NULL DEFAULT '' COMMENT 'name of the sender',
	`from-photo` varbinary(383) NOT NULL DEFAULT '' COMMENT 'contact photo link of the sender',
	`from-url` varbinary(383) NOT NULL DEFAULT '' COMMENT 'profile linke of the sender',
	`contact-id` varbinary(255) COMMENT 'contact.id',
	`author-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the author of the mail',
	`convid` int unsigned COMMENT 'conv.id',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`body` mediumtext COMMENT '',
	`seen` boolean NOT NULL DEFAULT '0' COMMENT 'if message visited it is 1',
	`reply` boolean NOT NULL DEFAULT '0' COMMENT '',
	`replied` boolean NOT NULL DEFAULT '0' COMMENT '',
	`unknown` boolean NOT NULL DEFAULT '0' COMMENT 'if sender not in the contact table this is 1',
	`uri` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`uri-id` int unsigned COMMENT 'Item-uri id of the related mail',
	`parent-uri` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`parent-uri-id` int unsigned COMMENT 'Item-uri id of the parent of the related mail',
	`thr-parent` varbinary(383) COMMENT '',
	`thr-parent-id` int unsigned COMMENT 'Id of the item-uri table that contains the thread parent uri',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation time of the private message',
	 PRIMARY KEY(`id`),
	 INDEX `uid_seen` (`uid`,`seen`),
	 INDEX `convid` (`convid`),
	 INDEX `uri` (`uri`(64)),
	 INDEX `parent-uri` (`parent-uri`(64)),
	 INDEX `contactid` (`contact-id`(32)),
	 INDEX `author-id` (`author-id`),
	 INDEX `uri-id` (`uri-id`),
	 INDEX `parent-uri-id` (`parent-uri-id`),
	 INDEX `thr-parent-id` (`thr-parent-id`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`thr-parent-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='private messages';

--
-- TABLE mailacct
--
CREATE TABLE IF NOT EXISTS `mailacct` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
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
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Mail account data for fetching mails';

--
-- TABLE manage
--
CREATE TABLE IF NOT EXISTS `manage` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`mid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_mid` (`uid`,`mid`),
	 INDEX `mid` (`mid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`mid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='table of accounts that can manage each other';

--
-- TABLE notification
--
CREATE TABLE IF NOT EXISTS `notification` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned COMMENT 'Owner User id',
	`vid` smallint unsigned COMMENT 'Id of the verb table entry that contains the activity verbs',
	`type` smallint unsigned COMMENT '',
	`actor-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the actor that caused the notification',
	`target-uri-id` int unsigned COMMENT 'Item-uri id of the related post',
	`parent-uri-id` int unsigned COMMENT 'Item-uri id of the parent of the related post',
	`created` datetime COMMENT '',
	`seen` boolean DEFAULT '0' COMMENT 'Seen on the desktop',
	`dismissed` boolean DEFAULT '0' COMMENT 'Dismissed via the API',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_vid_type_actor-id_target-uri-id` (`uid`,`vid`,`type`,`actor-id`,`target-uri-id`),
	 INDEX `vid` (`vid`),
	 INDEX `actor-id` (`actor-id`),
	 INDEX `target-uri-id` (`target-uri-id`),
	 INDEX `parent-uri-id` (`parent-uri-id`),
	 INDEX `seen_uid` (`seen`,`uid`),
	 INDEX `uid_type_parent-uri-id_actor-id` (`uid`,`type`,`parent-uri-id`,`actor-id`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`vid`) REFERENCES `verb` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`actor-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`target-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='notifications';

--
-- TABLE notify
--
CREATE TABLE IF NOT EXISTS `notify` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`type` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`photo` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`date` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`msg` mediumtext COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`link` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`iid` int unsigned COMMENT '',
	`parent` int unsigned COMMENT '',
	`uri-id` int unsigned COMMENT 'Item-uri id of the related post',
	`parent-uri-id` int unsigned COMMENT 'Item-uri id of the parent of the related post',
	`seen` boolean NOT NULL DEFAULT '0' COMMENT '',
	`verb` varchar(100) NOT NULL DEFAULT '' COMMENT '',
	`otype` varchar(10) NOT NULL DEFAULT '' COMMENT '',
	`name_cache` tinytext COMMENT 'Cached bbcode parsing of name',
	`msg_cache` mediumtext COMMENT 'Cached bbcode parsing of msg',
	 PRIMARY KEY(`id`),
	 INDEX `seen_uid_date` (`seen`,`uid`,`date`),
	 INDEX `uid_date` (`uid`,`date`),
	 INDEX `uid_type_link` (`uid`,`type`,`link`(190)),
	 INDEX `uri-id` (`uri-id`),
	 INDEX `parent-uri-id` (`parent-uri-id`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='[Deprecated] User notifications';

--
-- TABLE notify-threads
--
CREATE TABLE IF NOT EXISTS `notify-threads` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`notify-id` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`master-parent-item` int unsigned COMMENT 'Deprecated',
	`master-parent-uri-id` int unsigned COMMENT 'Item-uri id of the parent of the related post',
	`parent-item` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`receiver-uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	 PRIMARY KEY(`id`),
	 INDEX `master-parent-uri-id` (`master-parent-uri-id`),
	 INDEX `receiver-uid` (`receiver-uid`),
	 INDEX `notify-id` (`notify-id`),
	FOREIGN KEY (`notify-id`) REFERENCES `notify` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`master-parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`receiver-uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE oembed
--
CREATE TABLE IF NOT EXISTS `oembed` (
	`url` varbinary(383) NOT NULL COMMENT 'page url',
	`maxwidth` mediumint unsigned NOT NULL COMMENT 'Maximum width passed to Oembed',
	`content` mediumtext COMMENT 'OEmbed data of the page',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of creation',
	 PRIMARY KEY(`url`,`maxwidth`),
	 INDEX `created` (`created`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='cache for OEmbed queries';

--
-- TABLE openwebauth-token
--
CREATE TABLE IF NOT EXISTS `openwebauth-token` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id - currently unused',
	`type` varchar(32) NOT NULL DEFAULT '' COMMENT 'Verify type',
	`token` varchar(255) NOT NULL DEFAULT '' COMMENT 'A generated token',
	`meta` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of creation',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Store OpenWebAuth token to verify contacts';

--
-- TABLE parsed_url
--
CREATE TABLE IF NOT EXISTS `parsed_url` (
	`url_hash` binary(64) NOT NULL COMMENT 'page url hash',
	`guessing` boolean NOT NULL DEFAULT '0' COMMENT 'is the \'guessing\' mode active?',
	`oembed` boolean NOT NULL DEFAULT '0' COMMENT 'is the data the result of oembed?',
	`url` text NOT NULL COMMENT 'page url',
	`content` mediumtext COMMENT 'page data',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of creation',
	`expires` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime of expiration',
	 PRIMARY KEY(`url_hash`,`guessing`,`oembed`),
	 INDEX `created` (`created`),
	 INDEX `expires` (`expires`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='cache for \'parse_url\' queries';

--
-- TABLE pconfig
--
CREATE TABLE IF NOT EXISTS `pconfig` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'Primary key',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`cat` varchar(50) NOT NULL DEFAULT '' COMMENT 'Category',
	`k` varchar(100) NOT NULL DEFAULT '' COMMENT 'Key',
	`v` mediumtext COMMENT 'Value',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_cat_k` (`uid`,`cat`,`k`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='personal (per user) configuration storage';

--
-- TABLE photo
--
CREATE TABLE IF NOT EXISTS `photo` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact.id',
	`guid` char(16) NOT NULL DEFAULT '' COMMENT 'A unique identifier for this photo',
	`resource-id` char(32) NOT NULL DEFAULT '' COMMENT '',
	`hash` char(32) COMMENT 'hash value of the photo',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation date',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'last edited date',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`desc` text COMMENT '',
	`album` varchar(255) NOT NULL DEFAULT '' COMMENT 'The name of the album to which the photo belongs',
	`photo-type` tinyint unsigned COMMENT 'User avatar, user banner, contact avatar, contact banner or default',
	`filename` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`type` varchar(30) NOT NULL DEFAULT 'image/jpeg',
	`height` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`width` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`datasize` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`data` mediumblob NOT NULL COMMENT '',
	`scale` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`profile` boolean NOT NULL DEFAULT '0' COMMENT '',
	`allow_cid` mediumtext COMMENT 'Access Control - list of allowed contact.id \'<19><78>\'',
	`allow_gid` mediumtext COMMENT 'Access Control - list of allowed groups',
	`deny_cid` mediumtext COMMENT 'Access Control - list of denied contact.id',
	`deny_gid` mediumtext COMMENT 'Access Control - list of denied groups',
	`accessible` boolean NOT NULL DEFAULT '0' COMMENT 'Make photo publicly accessible, ignoring permissions',
	`backend-class` tinytext COMMENT 'Storage backend class',
	`backend-ref` text COMMENT 'Storage backend data reference',
	`updated` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `contactid` (`contact-id`),
	 INDEX `uid_contactid` (`uid`,`contact-id`),
	 INDEX `uid_profile` (`uid`,`profile`),
	 INDEX `uid_album_scale_created` (`uid`,`album`(32),`scale`,`created`),
	 INDEX `uid_album_resource-id_created` (`uid`,`album`(32),`resource-id`,`created`),
	 INDEX `resource-id` (`resource-id`),
	 INDEX `uid_photo-type` (`uid`,`photo-type`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='photo storage';

--
-- TABLE post
--
CREATE TABLE IF NOT EXISTS `post` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`parent-uri-id` int unsigned COMMENT 'Id of the item-uri table that contains the parent uri',
	`thr-parent-id` int unsigned COMMENT 'Id of the item-uri table that contains the thread parent uri',
	`external-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the external uri',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation timestamp.',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last edit (default is created)',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime',
	`gravity` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT 'Network from where the item comes from',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Link to the contact table with uid=0 of the owner of this item',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Link to the contact table with uid=0 of the author of this item',
	`causer-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the contact that caused the item creation',
	`post-type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Post type (personal note, image, article, ...)',
	`vid` smallint unsigned COMMENT 'Id of the verb table entry that contains the activity verbs',
	`private` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '0=public, 1=private, 2=unlisted',
	`global` boolean NOT NULL DEFAULT '0' COMMENT '',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT 'item has been marked for deletion',
	 PRIMARY KEY(`uri-id`),
	 INDEX `parent-uri-id` (`parent-uri-id`),
	 INDEX `thr-parent-id` (`thr-parent-id`),
	 INDEX `external-id` (`external-id`),
	 INDEX `owner-id` (`owner-id`),
	 INDEX `author-id` (`author-id`),
	 INDEX `causer-id` (`causer-id`),
	 INDEX `vid` (`vid`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`thr-parent-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`external-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`owner-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`causer-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`vid`) REFERENCES `verb` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Structure for all posts';

--
-- TABLE post-activity
--
CREATE TABLE IF NOT EXISTS `post-activity` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`activity` mediumtext COMMENT 'Original activity',
	`received` datetime COMMENT '',
	 PRIMARY KEY(`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Original remote activity';

--
-- TABLE post-category
--
CREATE TABLE IF NOT EXISTS `post-category` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`tid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`uri-id`,`uid`,`type`,`tid`),
	 INDEX `tid` (`tid`),
	 INDEX `uid_uri-id` (`uid`,`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`tid`) REFERENCES `tag` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='post relation to categories';

--
-- TABLE post-collection
--
CREATE TABLE IF NOT EXISTS `post-collection` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '0 - Featured',
	`author-id` int unsigned COMMENT 'Author of the featured post',
	 PRIMARY KEY(`uri-id`,`type`),
	 INDEX `type` (`type`),
	 INDEX `author-id` (`author-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Collection of posts';

--
-- TABLE post-content
--
CREATE TABLE IF NOT EXISTS `post-content` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT 'item title',
	`content-warning` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`body` mediumtext COMMENT 'item body content',
	`raw-body` mediumtext COMMENT 'Body without embedded media links',
	`quote-uri-id` int unsigned COMMENT 'Id of the item-uri table that contains the quoted uri',
	`location` varchar(255) NOT NULL DEFAULT '' COMMENT 'text location where this item originated',
	`coord` varchar(255) NOT NULL DEFAULT '' COMMENT 'longitude/latitude pair representing location where this item originated',
	`language` text COMMENT 'Language information about this post',
	`app` varchar(255) NOT NULL DEFAULT '' COMMENT 'application which generated this item',
	`rendered-hash` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`rendered-html` mediumtext COMMENT 'item.body converted to html',
	`object-type` varchar(100) NOT NULL DEFAULT '' COMMENT 'ActivityStreams object type',
	`object` text COMMENT 'JSON encoded object structure unless it is an implied object (normal post)',
	`target-type` varchar(100) NOT NULL DEFAULT '' COMMENT 'ActivityStreams target type if applicable (URI)',
	`target` text COMMENT 'JSON encoded target structure if used',
	`resource-id` varchar(32) NOT NULL DEFAULT '' COMMENT 'Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type',
	`plink` varbinary(383) NOT NULL DEFAULT '' COMMENT 'permalink or URL to a displayable copy of the message at its source',
	 PRIMARY KEY(`uri-id`),
	 INDEX `plink` (`plink`(191)),
	 INDEX `resource-id` (`resource-id`),
	 FULLTEXT INDEX `title-content-warning-body` (`title`,`content-warning`,`body`),
	 INDEX `quote-uri-id` (`quote-uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`quote-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Content for all posts';

--
-- TABLE post-delivery
--
CREATE TABLE IF NOT EXISTS `post-delivery` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`inbox-id` int unsigned NOT NULL COMMENT 'Item-uri id of inbox url',
	`uid` mediumint unsigned COMMENT 'Delivering user',
	`created` datetime DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`command` varbinary(32) COMMENT '',
	`failed` tinyint DEFAULT 0 COMMENT 'Number of times the delivery has failed',
	`receivers` mediumtext COMMENT 'JSON encoded array with the receiving contacts',
	 PRIMARY KEY(`uri-id`,`inbox-id`),
	 INDEX `inbox-id_created` (`inbox-id`,`created`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`inbox-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Delivery data for posts for the batch processing';

--
-- TABLE post-delivery-data
--
CREATE TABLE IF NOT EXISTS `post-delivery-data` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`postopts` text COMMENT 'External post connectors add their network name to this comma-separated string to identify that they should be delivered to these networks during delivery',
	`inform` mediumtext COMMENT 'Additional receivers of the linked item',
	`queue_count` mediumint NOT NULL DEFAULT 0 COMMENT 'Initial number of delivery recipients, used as item.delivery_queue_count',
	`queue_done` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries, used as item.delivery_queue_done',
	`queue_failed` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of unsuccessful deliveries, used as item.delivery_queue_failed',
	`activitypub` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries via ActivityPub',
	`dfrn` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries via DFRN',
	`legacy_dfrn` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries via legacy DFRN',
	`diaspora` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries via Diaspora',
	`ostatus` mediumint NOT NULL DEFAULT 0 COMMENT 'Number of successful deliveries via OStatus',
	 PRIMARY KEY(`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Delivery data for items';

--
-- TABLE post-history
--
CREATE TABLE IF NOT EXISTS `post-history` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of edit',
	`title` varchar(255) NOT NULL DEFAULT '' COMMENT 'item title',
	`content-warning` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`body` mediumtext COMMENT 'item body content',
	`raw-body` mediumtext COMMENT 'Body without embedded media links',
	`location` varchar(255) NOT NULL DEFAULT '' COMMENT 'text location where this item originated',
	`coord` varchar(255) NOT NULL DEFAULT '' COMMENT 'longitude/latitude pair representing location where this item originated',
	`language` text COMMENT 'Language information about this post',
	`app` varchar(255) NOT NULL DEFAULT '' COMMENT 'application which generated this item',
	`rendered-hash` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`rendered-html` mediumtext COMMENT 'item.body converted to html',
	`object-type` varchar(100) NOT NULL DEFAULT '' COMMENT 'ActivityStreams object type',
	`object` text COMMENT 'JSON encoded object structure unless it is an implied object (normal post)',
	`target-type` varchar(100) NOT NULL DEFAULT '' COMMENT 'ActivityStreams target type if applicable (URI)',
	`target` text COMMENT 'JSON encoded target structure if used',
	`resource-id` varchar(32) NOT NULL DEFAULT '' COMMENT 'Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type',
	`plink` varbinary(383) NOT NULL DEFAULT '' COMMENT 'permalink or URL to a displayable copy of the message at its source',
	 PRIMARY KEY(`uri-id`,`edited`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Post history';

--
-- TABLE post-link
--
CREATE TABLE IF NOT EXISTS `post-link` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`url` varbinary(511) NOT NULL COMMENT 'External URL',
	`mimetype` varchar(60) COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uri-id-url` (`uri-id`,`url`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Post related external links';

--
-- TABLE post-media
--
CREATE TABLE IF NOT EXISTS `post-media` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`url` varbinary(1024) NOT NULL COMMENT 'Media URL',
	`media-uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the activities uri-id',
	`type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Media type',
	`mimetype` varchar(60) COMMENT '',
	`height` smallint unsigned COMMENT 'Height of the media',
	`width` smallint unsigned COMMENT 'Width of the media',
	`size` bigint unsigned COMMENT 'Media size',
	`preview` varbinary(512) COMMENT 'Preview URL',
	`preview-height` smallint unsigned COMMENT 'Height of the preview picture',
	`preview-width` smallint unsigned COMMENT 'Width of the preview picture',
	`description` text COMMENT '',
	`name` varchar(255) COMMENT 'Name of the media',
	`author-url` varbinary(383) COMMENT 'URL of the author of the media',
	`author-name` varchar(255) COMMENT 'Name of the author of the media',
	`author-image` varbinary(383) COMMENT 'Image of the author of the media',
	`publisher-url` varbinary(383) COMMENT 'URL of the publisher of the media',
	`publisher-name` varchar(255) COMMENT 'Name of the publisher of the media',
	`publisher-image` varbinary(383) COMMENT 'Image of the publisher of the media',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uri-id-url` (`uri-id`,`url`(512)),
	 INDEX `uri-id-id` (`uri-id`,`id`),
	 INDEX `media-uri-id` (`media-uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`media-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Attached media';

--
-- TABLE post-question
--
CREATE TABLE IF NOT EXISTS `post-question` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`multiple` boolean NOT NULL DEFAULT '0' COMMENT 'Multiple choice',
	`voters` int unsigned COMMENT 'Number of voters for this question',
	`end-time` datetime DEFAULT '0001-01-01 00:00:00' COMMENT 'Question end time',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Question';

--
-- TABLE post-question-option
--
CREATE TABLE IF NOT EXISTS `post-question-option` (
	`id` int unsigned NOT NULL COMMENT 'Id of the question',
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`name` varchar(255) COMMENT 'Name of the option',
	`replies` int unsigned COMMENT 'Number of replies for this question option',
	 PRIMARY KEY(`uri-id`,`id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Question option';

--
-- TABLE post-tag
--
CREATE TABLE IF NOT EXISTS `post-tag` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`tid` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Contact id of the mentioned public contact',
	 PRIMARY KEY(`uri-id`,`type`,`tid`,`cid`),
	 INDEX `tid` (`tid`),
	 INDEX `cid` (`cid`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`tid`) REFERENCES `tag` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='post relation to tags';

--
-- TABLE post-thread
--
CREATE TABLE IF NOT EXISTS `post-thread` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`conversation-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the conversation uri',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Item owner',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Item author',
	`causer-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the contact that caused the item creation',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date that something in the conversation changed, indicating clients should fetch the conversation again',
	`commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`uri-id`),
	 INDEX `conversation-id` (`conversation-id`),
	 INDEX `owner-id` (`owner-id`),
	 INDEX `author-id` (`author-id`),
	 INDEX `causer-id` (`causer-id`),
	 INDEX `received` (`received`),
	 INDEX `commented` (`commented`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`conversation-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`owner-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`causer-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Thread related data';

--
-- TABLE post-user
--
CREATE TABLE IF NOT EXISTS `post-user` (
	`id` int unsigned NOT NULL auto_increment,
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`parent-uri-id` int unsigned COMMENT 'Id of the item-uri table that contains the parent uri',
	`thr-parent-id` int unsigned COMMENT 'Id of the item-uri table that contains the thread parent uri',
	`external-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the external uri',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Creation timestamp.',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last edit (default is created)',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'datetime',
	`gravity` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`network` char(4) NOT NULL DEFAULT '' COMMENT 'Network from where the item comes from',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Link to the contact table with uid=0 of the owner of this item',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Link to the contact table with uid=0 of the author of this item',
	`causer-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the contact that caused the item creation',
	`post-type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Post type (personal note, image, article, ...)',
	`post-reason` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Reason why the post arrived at the user',
	`vid` smallint unsigned COMMENT 'Id of the verb table entry that contains the activity verbs',
	`private` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '0=public, 1=private, 2=unlisted',
	`global` boolean NOT NULL DEFAULT '0' COMMENT '',
	`visible` boolean NOT NULL DEFAULT '0' COMMENT '',
	`deleted` boolean NOT NULL DEFAULT '0' COMMENT 'item has been marked for deletion',
	`uid` mediumint unsigned NOT NULL COMMENT 'Owner id which owns this copy of the item',
	`protocol` tinyint unsigned COMMENT 'Protocol used to deliver the item for this user',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact.id',
	`event-id` int unsigned COMMENT 'Used to link to the event.id',
	`unseen` boolean NOT NULL DEFAULT '1' COMMENT 'post has not been seen',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT 'Marker to hide the post from the user',
	`notification-type` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '',
	`wall` boolean NOT NULL DEFAULT '0' COMMENT 'This item was posted to the wall of uid',
	`origin` boolean NOT NULL DEFAULT '0' COMMENT 'item originated at this site',
	`psid` int unsigned COMMENT 'ID of the permission set of this post',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `uid_uri-id` (`uid`,`uri-id`),
	 INDEX `uri-id` (`uri-id`),
	 INDEX `parent-uri-id` (`parent-uri-id`),
	 INDEX `thr-parent-id` (`thr-parent-id`),
	 INDEX `external-id` (`external-id`),
	 INDEX `owner-id` (`owner-id`),
	 INDEX `author-id` (`author-id`),
	 INDEX `causer-id` (`causer-id`),
	 INDEX `vid` (`vid`),
	 INDEX `contact-id` (`contact-id`),
	 INDEX `event-id` (`event-id`),
	 INDEX `psid` (`psid`),
	 INDEX `author-id_uid` (`author-id`,`uid`),
	 INDEX `author-id_received` (`author-id`,`received`),
	 INDEX `parent-uri-id_uid` (`parent-uri-id`,`uid`),
	 INDEX `uid_wall_received` (`uid`,`wall`,`received`),
	 INDEX `uid_contactid` (`uid`,`contact-id`),
	 INDEX `uid_unseen_contactid` (`uid`,`unseen`,`contact-id`),
	 INDEX `uid_unseen` (`uid`,`unseen`),
	 INDEX `uid_hidden_uri-id` (`uid`,`hidden`,`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`parent-uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`thr-parent-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`external-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`owner-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`causer-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`vid`) REFERENCES `verb` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`event-id`) REFERENCES `event` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`psid`) REFERENCES `permissionset` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='User specific post data';

--
-- TABLE post-thread-user
--
CREATE TABLE IF NOT EXISTS `post-thread-user` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`conversation-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the conversation uri',
	`owner-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Item owner',
	`author-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'Item author',
	`causer-id` int unsigned COMMENT 'Link to the contact table with uid=0 of the contact that caused the item creation',
	`network` char(4) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`received` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`changed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date that something in the conversation changed, indicating clients should fetch the conversation again',
	`commented` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner id which owns this copy of the item',
	`pinned` boolean NOT NULL DEFAULT '0' COMMENT 'deprecated',
	`starred` boolean NOT NULL DEFAULT '0' COMMENT '',
	`ignored` boolean NOT NULL DEFAULT '0' COMMENT 'Ignore updates for this thread',
	`wall` boolean NOT NULL DEFAULT '0' COMMENT 'This item was posted to the wall of uid',
	`mention` boolean NOT NULL DEFAULT '0' COMMENT '',
	`pubmail` boolean NOT NULL DEFAULT '0' COMMENT '',
	`forum_mode` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'Deprecated',
	`contact-id` int unsigned NOT NULL DEFAULT 0 COMMENT 'contact.id',
	`unseen` boolean NOT NULL DEFAULT '1' COMMENT 'post has not been seen',
	`hidden` boolean NOT NULL DEFAULT '0' COMMENT 'Marker to hide the post from the user',
	`origin` boolean NOT NULL DEFAULT '0' COMMENT 'item originated at this site',
	`psid` int unsigned COMMENT 'ID of the permission set of this post',
	`post-user-id` int unsigned COMMENT 'Id of the post-user table',
	 PRIMARY KEY(`uid`,`uri-id`),
	 INDEX `uri-id` (`uri-id`),
	 INDEX `conversation-id` (`conversation-id`),
	 INDEX `owner-id` (`owner-id`),
	 INDEX `author-id` (`author-id`),
	 INDEX `causer-id` (`causer-id`),
	 INDEX `uid` (`uid`),
	 INDEX `contact-id` (`contact-id`),
	 INDEX `psid` (`psid`),
	 INDEX `post-user-id` (`post-user-id`),
	 INDEX `commented` (`commented`),
	 INDEX `uid_received` (`uid`,`received`),
	 INDEX `uid_wall_received` (`uid`,`wall`,`received`),
	 INDEX `uid_commented` (`uid`,`commented`),
	 INDEX `uid_starred` (`uid`,`starred`),
	 INDEX `uid_mention` (`uid`,`mention`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`conversation-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`owner-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`author-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`causer-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`contact-id`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`psid`) REFERENCES `permissionset` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
	FOREIGN KEY (`post-user-id`) REFERENCES `post-user` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Thread related data per user';

--
-- TABLE post-user-notification
--
CREATE TABLE IF NOT EXISTS `post-user-notification` (
	`uri-id` int unsigned NOT NULL COMMENT 'Id of the item-uri table entry that contains the item uri',
	`uid` mediumint unsigned NOT NULL COMMENT 'Owner id which owns this copy of the item',
	`notification-type` smallint unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`uid`,`uri-id`),
	 INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='User post notifications';

--
-- TABLE process
--
CREATE TABLE IF NOT EXISTS `process` (
	`pid` int unsigned NOT NULL COMMENT 'The ID of the process',
	`hostname` varchar(32) NOT NULL COMMENT 'The name of the host the process is ran on',
	`command` varbinary(32) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	 PRIMARY KEY(`pid`,`hostname`),
	 INDEX `command` (`command`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Currently running system processes';

--
-- TABLE profile
--
CREATE TABLE IF NOT EXISTS `profile` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner User id',
	`profile-name` varchar(255) COMMENT 'Deprecated',
	`is-default` boolean COMMENT 'Deprecated',
	`hide-friends` boolean NOT NULL DEFAULT '0' COMMENT 'Hide friend list from viewers of this profile',
	`name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`pdesc` varchar(255) COMMENT 'Deprecated',
	`dob` varchar(32) NOT NULL DEFAULT '0000-00-00' COMMENT 'Day of birth',
	`address` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`locality` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`region` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`postal-code` varchar(32) NOT NULL DEFAULT '' COMMENT '',
	`country-name` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`hometown` varchar(255) COMMENT 'Deprecated',
	`gender` varchar(32) COMMENT 'Deprecated',
	`marital` varchar(255) COMMENT 'Deprecated',
	`with` text COMMENT 'Deprecated',
	`howlong` datetime COMMENT 'Deprecated',
	`sexual` varchar(255) COMMENT 'Deprecated',
	`politic` varchar(255) COMMENT 'Deprecated',
	`religion` varchar(255) COMMENT 'Deprecated',
	`pub_keywords` text COMMENT '',
	`prv_keywords` text COMMENT '',
	`likes` text COMMENT 'Deprecated',
	`dislikes` text COMMENT 'Deprecated',
	`about` text COMMENT 'Profile description',
	`summary` varchar(255) COMMENT 'Deprecated',
	`music` text COMMENT 'Deprecated',
	`book` text COMMENT 'Deprecated',
	`tv` text COMMENT 'Deprecated',
	`film` text COMMENT 'Deprecated',
	`interest` text COMMENT 'Deprecated',
	`romance` text COMMENT 'Deprecated',
	`work` text COMMENT 'Deprecated',
	`education` text COMMENT 'Deprecated',
	`contact` text COMMENT 'Deprecated',
	`homepage` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`homepage_verified` boolean NOT NULL DEFAULT '0' COMMENT 'was the homepage verified by a rel-me link back to the profile',
	`xmpp` varchar(255) NOT NULL DEFAULT '' COMMENT 'XMPP address',
	`matrix` varchar(255) NOT NULL DEFAULT '' COMMENT 'Matrix address',
	`photo` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`thumb` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`publish` boolean NOT NULL DEFAULT '0' COMMENT 'publish default profile in local directory',
	`net-publish` boolean NOT NULL DEFAULT '0' COMMENT 'publish profile in global directory',
	 PRIMARY KEY(`id`),
	 INDEX `uid_is-default` (`uid`,`is-default`),
	 FULLTEXT INDEX `pub_keywords` (`pub_keywords`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='user profiles data';

--
-- TABLE profile_field
--
CREATE TABLE IF NOT EXISTS `profile_field` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'Owner user id',
	`order` mediumint unsigned NOT NULL DEFAULT 1 COMMENT 'Field ordering per user',
	`psid` int unsigned COMMENT 'ID of the permission set of this profile field - 0 = public',
	`label` varchar(255) NOT NULL DEFAULT '' COMMENT 'Label of the field',
	`value` text COMMENT 'Value of the field',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'creation time',
	`edited` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'last edit time',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	 INDEX `order` (`order`),
	 INDEX `psid` (`psid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`psid`) REFERENCES `permissionset` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Custom profile fields';

--
-- TABLE push_subscriber
--
CREATE TABLE IF NOT EXISTS `push_subscriber` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`callback_url` varbinary(383) NOT NULL DEFAULT '' COMMENT '',
	`topic` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`push` tinyint NOT NULL DEFAULT 0 COMMENT 'Retrial counter',
	`last_update` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last successful trial',
	`next_try` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Next retrial date',
	`renewed` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT 'Date of last subscription renewal',
	`secret` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `next_try` (`next_try`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Used for OStatus: Contains feed subscribers';

--
-- TABLE register
--
CREATE TABLE IF NOT EXISTS `register` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`hash` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`password` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	`language` varchar(16) NOT NULL DEFAULT '' COMMENT '',
	`note` text COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='registrations requiring admin approval';

--
-- TABLE report
--
CREATE TABLE IF NOT EXISTS `report` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned COMMENT 'Reporting user',
	`cid` int unsigned NOT NULL COMMENT 'Reported contact',
	`comment` text COMMENT 'Report',
	`forward` boolean COMMENT 'Forward the report to the remote server',
	`created` datetime NOT NULL DEFAULT '0001-01-01 00:00:00' COMMENT '',
	`status` tinyint unsigned COMMENT 'Status of the report',
	 PRIMARY KEY(`id`),
	 INDEX `uid` (`uid`),
	 INDEX `cid` (`cid`),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE report-post
--
CREATE TABLE IF NOT EXISTS `report-post` (
	`rid` int unsigned NOT NULL COMMENT 'Report id',
	`uri-id` int unsigned NOT NULL COMMENT 'Uri-id of the reported post',
	`status` tinyint unsigned COMMENT 'Status of the reported post',
	 PRIMARY KEY(`rid`,`uri-id`),
	 INDEX `uri-id` (`uri-id`),
	FOREIGN KEY (`rid`) REFERENCES `report` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE search
--
CREATE TABLE IF NOT EXISTS `search` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`term` varchar(255) NOT NULL DEFAULT '' COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `uid_term` (`uid`,`term`(64)),
	 INDEX `term` (`term`(64)),
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='';

--
-- TABLE session
--
CREATE TABLE IF NOT EXISTS `session` (
	`id` bigint unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`sid` varbinary(255) NOT NULL DEFAULT '' COMMENT '',
	`data` text COMMENT '',
	`expire` int unsigned NOT NULL DEFAULT 0 COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `sid` (`sid`(64)),
	 INDEX `expire` (`expire`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='web session storage';

--
-- TABLE storage
--
CREATE TABLE IF NOT EXISTS `storage` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'Auto incremented image data id',
	`data` longblob NOT NULL COMMENT 'file data',
	 PRIMARY KEY(`id`)
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Data stored by Database storage backend';

--
-- TABLE subscription
--
CREATE TABLE IF NOT EXISTS `subscription` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'Auto incremented image data id',
	`application-id` int unsigned NOT NULL COMMENT '',
	`uid` mediumint unsigned NOT NULL COMMENT 'Owner User id',
	`endpoint` varchar(511) COMMENT 'Endpoint URL',
	`pubkey` varchar(127) COMMENT 'User agent public key',
	`secret` varchar(32) COMMENT 'Auth secret',
	`follow` boolean COMMENT '',
	`favourite` boolean COMMENT '',
	`reblog` boolean COMMENT '',
	`mention` boolean COMMENT '',
	`poll` boolean COMMENT '',
	`follow_request` boolean COMMENT '',
	`status` boolean COMMENT '',
	 PRIMARY KEY(`id`),
	 UNIQUE INDEX `application-id_uid` (`application-id`,`uid`),
	 INDEX `uid_application-id` (`uid`,`application-id`),
	FOREIGN KEY (`application-id`) REFERENCES `application` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Push Subscription for the API';

--
-- TABLE userd
--
CREATE TABLE IF NOT EXISTS `userd` (
	`id` int unsigned NOT NULL auto_increment COMMENT 'sequential ID',
	`username` varchar(255) NOT NULL COMMENT '',
	 PRIMARY KEY(`id`),
	 INDEX `username` (`username`(32))
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='Deleted usernames';

--
-- TABLE user-contact
--
CREATE TABLE IF NOT EXISTS `user-contact` (
	`cid` int unsigned NOT NULL DEFAULT 0 COMMENT 'Contact id of the linked public contact',
	`uid` mediumint unsigned NOT NULL DEFAULT 0 COMMENT 'User id',
	`uri-id` int unsigned COMMENT 'Id of the item-uri table entry that contains the contact url',
	`blocked` boolean COMMENT 'Contact is completely blocked for this user',
	`ignored` boolean COMMENT 'Posts from this contact are ignored',
	`collapsed` boolean COMMENT 'Posts from this contact are collapsed',
	`hidden` boolean COMMENT 'This contact is hidden from the others',
	`is-blocked` boolean COMMENT 'User is blocked by this contact',
	`pending` boolean COMMENT '',
	`rel` tinyint unsigned COMMENT 'The kind of the relation between the user and the contact',
	`info` mediumtext COMMENT '',
	`notify_new_posts` boolean COMMENT '',
	`remote_self` boolean COMMENT '',
	`fetch_further_information` tinyint unsigned COMMENT '',
	`ffi_keyword_denylist` text COMMENT '',
	`subhub` boolean COMMENT '',
	`hub-verify` varbinary(383) COMMENT '',
	`protocol` char(4) COMMENT 'Protocol of the contact',
	`rating` tinyint COMMENT 'Automatically detected feed poll frequency',
	`priority` tinyint unsigned COMMENT 'Feed poll priority',
	 PRIMARY KEY(`uid`,`cid`),
	 INDEX `cid` (`cid`),
	 UNIQUE INDEX `uri-id_uid` (`uri-id`,`uid`),
	FOREIGN KEY (`cid`) REFERENCES `contact` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON UPDATE RESTRICT ON DELETE CASCADE,
	FOREIGN KEY (`uri-id`) REFERENCES `item-uri` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE
) DEFAULT COLLATE utf8mb4_general_ci COMMENT='User specific public contact data';

--
-- TABLE arrived-activity
--
CREATE TABLE IF NOT EXISTS `arrived-activity` (
	`object-id` varbinary(383) NOT NULL COMMENT 'object id of the incoming activity',
	`received` datetime COMMENT 'Receiving date',
	 PRIMARY KEY(`object-id`)
) ENGINE=MEMORY DEFAULT COLLATE utf8mb4_general_ci COMMENT='Id of arrived activities';

--
-- TABLE fetched-activity
--
CREATE TABLE IF NOT EXISTS `fetched-activity` (
	`object-id` varbinary(383) NOT NULL COMMENT 'object id of fetched activity',
	`received` datetime COMMENT 'Receiving date',
	 PRIMARY KEY(`object-id`)
) ENGINE=MEMORY DEFAULT COLLATE utf8mb4_general_ci COMMENT='Id of fetched activities';

--
-- TABLE worker-ipc
--
CREATE TABLE IF NOT EXISTS `worker-ipc` (
	`key` int NOT NULL COMMENT '',
	`jobs` boolean COMMENT 'Flag for outstanding jobs',
	 PRIMARY KEY(`key`)
) ENGINE=MEMORY DEFAULT COLLATE utf8mb4_general_ci COMMENT='Inter process communication between the frontend and the worker';

--
-- VIEW application-view
--
DROP VIEW IF EXISTS `application-view`;
CREATE VIEW `application-view` AS SELECT 
	`application`.`id` AS `id`,
	`application-token`.`uid` AS `uid`,
	`application`.`name` AS `name`,
	`application`.`redirect_uri` AS `redirect_uri`,
	`application`.`website` AS `website`,
	`application`.`client_id` AS `client_id`,
	`application`.`client_secret` AS `client_secret`,
	`application-token`.`code` AS `code`,
	`application-token`.`access_token` AS `access_token`,
	`application-token`.`created_at` AS `created_at`,
	`application-token`.`scopes` AS `scopes`,
	`application-token`.`read` AS `read`,
	`application-token`.`write` AS `write`,
	`application-token`.`follow` AS `follow`,
	`application-token`.`push` AS `push`
	FROM `application-token`
			INNER JOIN `application` ON `application-token`.`application-id` = `application`.`id`;

--
-- VIEW post-user-view
--
DROP VIEW IF EXISTS `post-user-view`;
CREATE VIEW `post-user-view` AS SELECT 
	`post-user`.`id` AS `id`,
	`post-user`.`id` AS `post-user-id`,
	`post-user`.`uid` AS `uid`,
	`parent-post`.`id` AS `parent`,
	`item-uri`.`uri` AS `uri`,
	`post-user`.`uri-id` AS `uri-id`,
	`parent-item-uri`.`uri` AS `parent-uri`,
	`post-user`.`parent-uri-id` AS `parent-uri-id`,
	`thr-parent-item-uri`.`uri` AS `thr-parent`,
	`post-user`.`thr-parent-id` AS `thr-parent-id`,
	`conversation-item-uri`.`uri` AS `conversation`,
	`post-thread-user`.`conversation-id` AS `conversation-id`,
	`quote-item-uri`.`uri` AS `quote-uri`,
	`post-content`.`quote-uri-id` AS `quote-uri-id`,
	`item-uri`.`guid` AS `guid`,
	`post-user`.`wall` AS `wall`,
	`post-user`.`gravity` AS `gravity`,
	`external-item-uri`.`uri` AS `extid`,
	`post-user`.`external-id` AS `external-id`,
	`post-user`.`created` AS `created`,
	`post-user`.`edited` AS `edited`,
	`post-thread-user`.`commented` AS `commented`,
	`post-user`.`received` AS `received`,
	`post-thread-user`.`changed` AS `changed`,
	`post-user`.`post-type` AS `post-type`,
	`post-user`.`post-reason` AS `post-reason`,
	`post-user`.`private` AS `private`,
	`post-thread-user`.`pubmail` AS `pubmail`,
	`post-user`.`visible` AS `visible`,
	`post-thread-user`.`starred` AS `starred`,
	`post-user`.`unseen` AS `unseen`,
	`post-user`.`deleted` AS `deleted`,
	`post-user`.`origin` AS `origin`,
	`post-thread-user`.`origin` AS `parent-origin`,
	`post-thread-user`.`mention` AS `mention`,
	`post-user`.`global` AS `global`,
	EXISTS(SELECT `type` FROM `post-collection` WHERE `type` = 0 AND `uri-id` = `post-user`.`uri-id`) AS `featured`,
	`post-user`.`network` AS `network`,
	`post-user`.`protocol` AS `protocol`,
	`post-user`.`vid` AS `vid`,
	`post-user`.`psid` AS `psid`,
	IF (`post-user`.`vid` IS NULL, '', `verb`.`name`) AS `verb`,
	`post-content`.`title` AS `title`,
	`post-content`.`content-warning` AS `content-warning`,
	`post-content`.`raw-body` AS `raw-body`,
	IFNULL (`post-content`.`body`, '') AS `body`,
	`post-content`.`rendered-hash` AS `rendered-hash`,
	`post-content`.`rendered-html` AS `rendered-html`,
	`post-content`.`language` AS `language`,
	`post-content`.`plink` AS `plink`,
	`post-content`.`location` AS `location`,
	`post-content`.`coord` AS `coord`,
	`post-content`.`app` AS `app`,
	`post-content`.`object-type` AS `object-type`,
	`post-content`.`object` AS `object`,
	`post-content`.`target-type` AS `target-type`,
	`post-content`.`target` AS `target`,
	`post-content`.`resource-id` AS `resource-id`,
	`post-user`.`contact-id` AS `contact-id`,
	`contact`.`uri-id` AS `contact-uri-id`,
	`contact`.`url` AS `contact-link`,
	`contact`.`addr` AS `contact-addr`,
	`contact`.`name` AS `contact-name`,
	`contact`.`nick` AS `contact-nick`,
	`contact`.`thumb` AS `contact-avatar`,
	`contact`.`network` AS `contact-network`,
	`contact`.`blocked` AS `contact-blocked`,
	`contact`.`hidden` AS `contact-hidden`,
	`contact`.`readonly` AS `contact-readonly`,
	`contact`.`archive` AS `contact-archive`,
	`contact`.`pending` AS `contact-pending`,
	`contact`.`rel` AS `contact-rel`,
	`contact`.`uid` AS `contact-uid`,
	`contact`.`contact-type` AS `contact-contact-type`,
	IF (`post-user`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `contact`.`writable`) AS `writable`,
	`contact`.`self` AS `self`,
	`contact`.`id` AS `cid`,
	`contact`.`alias` AS `alias`,
	`contact`.`photo` AS `photo`,
	`contact`.`name-date` AS `name-date`,
	`contact`.`uri-date` AS `uri-date`,
	`contact`.`avatar-date` AS `avatar-date`,
	`contact`.`thumb` AS `thumb`,
	`post-user`.`author-id` AS `author-id`,
	`author`.`uri-id` AS `author-uri-id`,
	`author`.`url` AS `author-link`,
	`author`.`addr` AS `author-addr`,
	IF (`contact`.`url` = `author`.`url` AND `contact`.`name` != '', `contact`.`name`, `author`.`name`) AS `author-name`,
	`author`.`nick` AS `author-nick`,
	IF (`contact`.`url` = `author`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `author`.`thumb`) AS `author-avatar`,
	`author`.`network` AS `author-network`,
	`author`.`blocked` AS `author-blocked`,
	`author`.`hidden` AS `author-hidden`,
	`author`.`updated` AS `author-updated`,
	`author`.`gsid` AS `author-gsid`,
	`post-user`.`owner-id` AS `owner-id`,
	`owner`.`uri-id` AS `owner-uri-id`,
	`owner`.`url` AS `owner-link`,
	`owner`.`addr` AS `owner-addr`,
	IF (`contact`.`url` = `owner`.`url` AND `contact`.`name` != '', `contact`.`name`, `owner`.`name`) AS `owner-name`,
	`owner`.`nick` AS `owner-nick`,
	IF (`contact`.`url` = `owner`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `owner`.`thumb`) AS `owner-avatar`,
	`owner`.`network` AS `owner-network`,
	`owner`.`blocked` AS `owner-blocked`,
	`owner`.`hidden` AS `owner-hidden`,
	`owner`.`updated` AS `owner-updated`,
	`owner`.`contact-type` AS `owner-contact-type`,
	`post-user`.`causer-id` AS `causer-id`,
	`causer`.`uri-id` AS `causer-uri-id`,
	`causer`.`url` AS `causer-link`,
	`causer`.`addr` AS `causer-addr`,
	`causer`.`name` AS `causer-name`,
	`causer`.`nick` AS `causer-nick`,
	`causer`.`thumb` AS `causer-avatar`,
	`causer`.`network` AS `causer-network`,
	`causer`.`blocked` AS `causer-blocked`,
	`causer`.`hidden` AS `causer-hidden`,
	`causer`.`contact-type` AS `causer-contact-type`,
	`post-delivery-data`.`postopts` AS `postopts`,
	`post-delivery-data`.`inform` AS `inform`,
	`post-delivery-data`.`queue_count` AS `delivery_queue_count`,
	`post-delivery-data`.`queue_done` AS `delivery_queue_done`,
	`post-delivery-data`.`queue_failed` AS `delivery_queue_failed`,
	IF (`post-user`.`psid` IS NULL, '', `permissionset`.`allow_cid`) AS `allow_cid`,
	IF (`post-user`.`psid` IS NULL, '', `permissionset`.`allow_gid`) AS `allow_gid`,
	IF (`post-user`.`psid` IS NULL, '', `permissionset`.`deny_cid`) AS `deny_cid`,
	IF (`post-user`.`psid` IS NULL, '', `permissionset`.`deny_gid`) AS `deny_gid`,
	`post-user`.`event-id` AS `event-id`,
	`event`.`created` AS `event-created`,
	`event`.`edited` AS `event-edited`,
	`event`.`start` AS `event-start`,
	`event`.`finish` AS `event-finish`,
	`event`.`summary` AS `event-summary`,
	`event`.`desc` AS `event-desc`,
	`event`.`location` AS `event-location`,
	`event`.`type` AS `event-type`,
	`event`.`nofinish` AS `event-nofinish`,
	`event`.`ignore` AS `event-ignore`,
	`post-question`.`id` AS `question-id`,
	`post-question`.`multiple` AS `question-multiple`,
	`post-question`.`voters` AS `question-voters`,
	`post-question`.`end-time` AS `question-end-time`,
	EXISTS(SELECT `uri-id` FROM `post-category` WHERE `post-category`.`uri-id` = `post-user`.`uri-id` AND `post-category`.`uid` = `post-user`.`uid`) AS `has-categories`,
	EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post-user`.`uri-id`) AS `has-media`,
	`diaspora-interaction`.`interaction` AS `signed_text`,
	`parent-item-uri`.`guid` AS `parent-guid`,
	`parent-post`.`network` AS `parent-network`,
	`parent-post`.`author-id` AS `parent-author-id`,
	`parent-post-author`.`url` AS `parent-author-link`,
	`parent-post-author`.`name` AS `parent-author-name`,
	`parent-post-author`.`nick` AS `parent-author-nick`,
	`parent-post-author`.`network` AS `parent-author-network`
	FROM `post-user`
			STRAIGHT_JOIN `post-thread-user` ON `post-thread-user`.`uri-id` = `post-user`.`parent-uri-id` AND `post-thread-user`.`uid` = `post-user`.`uid`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `post-user`.`contact-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `post-user`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `post-user`.`owner-id`
			LEFT JOIN `contact` AS `causer` ON `causer`.`id` = `post-user`.`causer-id`
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `post-user`.`uri-id`
			LEFT JOIN `item-uri` AS `thr-parent-item-uri` ON `thr-parent-item-uri`.`id` = `post-user`.`thr-parent-id`
			LEFT JOIN `item-uri` AS `parent-item-uri` ON `parent-item-uri`.`id` = `post-user`.`parent-uri-id`
			LEFT JOIN `item-uri` AS `conversation-item-uri` ON `conversation-item-uri`.`id` = `post-thread-user`.`conversation-id`
			LEFT JOIN `item-uri` AS `external-item-uri` ON `external-item-uri`.`id` = `post-user`.`external-id`
			LEFT JOIN `verb` ON `verb`.`id` = `post-user`.`vid`
			LEFT JOIN `event` ON `event`.`id` = `post-user`.`event-id`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `post-user`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `post-user`.`uri-id`
			LEFT JOIN `item-uri` AS `quote-item-uri` ON `quote-item-uri`.`id` = `post-content`.`quote-uri-id`
			LEFT JOIN `post-delivery-data` ON `post-delivery-data`.`uri-id` = `post-user`.`uri-id` AND `post-user`.`origin`
			LEFT JOIN `post-question` ON `post-question`.`uri-id` = `post-user`.`uri-id`
			LEFT JOIN `permissionset` ON `permissionset`.`id` = `post-user`.`psid`
			LEFT JOIN `post-user` AS `parent-post` ON `parent-post`.`uri-id` = `post-user`.`parent-uri-id` AND `parent-post`.`uid` = `post-user`.`uid`
			LEFT JOIN `contact` AS `parent-post-author` ON `parent-post-author`.`id` = `parent-post`.`author-id`;

--
-- VIEW post-thread-user-view
--
DROP VIEW IF EXISTS `post-thread-user-view`;
CREATE VIEW `post-thread-user-view` AS SELECT 
	`post-user`.`id` AS `id`,
	`post-user`.`id` AS `post-user-id`,
	`post-thread-user`.`uid` AS `uid`,
	`parent-post`.`id` AS `parent`,
	`item-uri`.`uri` AS `uri`,
	`post-thread-user`.`uri-id` AS `uri-id`,
	`parent-item-uri`.`uri` AS `parent-uri`,
	`post-user`.`parent-uri-id` AS `parent-uri-id`,
	`thr-parent-item-uri`.`uri` AS `thr-parent`,
	`post-user`.`thr-parent-id` AS `thr-parent-id`,
	`conversation-item-uri`.`uri` AS `conversation`,
	`post-thread-user`.`conversation-id` AS `conversation-id`,
	`quote-item-uri`.`uri` AS `quote-uri`,
	`post-content`.`quote-uri-id` AS `quote-uri-id`,
	`item-uri`.`guid` AS `guid`,
	`post-thread-user`.`wall` AS `wall`,
	`post-user`.`gravity` AS `gravity`,
	`external-item-uri`.`uri` AS `extid`,
	`post-user`.`external-id` AS `external-id`,
	`post-thread-user`.`created` AS `created`,
	`post-user`.`edited` AS `edited`,
	`post-thread-user`.`commented` AS `commented`,
	`post-thread-user`.`received` AS `received`,
	`post-thread-user`.`changed` AS `changed`,
	`post-user`.`post-type` AS `post-type`,
	`post-user`.`post-reason` AS `post-reason`,
	`post-user`.`private` AS `private`,
	`post-thread-user`.`pubmail` AS `pubmail`,
	`post-thread-user`.`ignored` AS `ignored`,
	`post-user`.`visible` AS `visible`,
	`post-thread-user`.`starred` AS `starred`,
	`post-thread-user`.`unseen` AS `unseen`,
	`post-user`.`deleted` AS `deleted`,
	`post-thread-user`.`origin` AS `origin`,
	`post-thread-user`.`mention` AS `mention`,
	`post-user`.`global` AS `global`,
	EXISTS(SELECT `type` FROM `post-collection` WHERE `type` = 0 AND `uri-id` = `post-thread-user`.`uri-id`) AS `featured`,
	`post-thread-user`.`network` AS `network`,
	`post-user`.`vid` AS `vid`,
	`post-thread-user`.`psid` AS `psid`,
	IF (`post-user`.`vid` IS NULL, '', `verb`.`name`) AS `verb`,
	`post-content`.`title` AS `title`,
	`post-content`.`content-warning` AS `content-warning`,
	`post-content`.`raw-body` AS `raw-body`,
	`post-content`.`body` AS `body`,
	`post-content`.`rendered-hash` AS `rendered-hash`,
	`post-content`.`rendered-html` AS `rendered-html`,
	`post-content`.`language` AS `language`,
	`post-content`.`plink` AS `plink`,
	`post-content`.`location` AS `location`,
	`post-content`.`coord` AS `coord`,
	`post-content`.`app` AS `app`,
	`post-content`.`object-type` AS `object-type`,
	`post-content`.`object` AS `object`,
	`post-content`.`target-type` AS `target-type`,
	`post-content`.`target` AS `target`,
	`post-content`.`resource-id` AS `resource-id`,
	`post-thread-user`.`contact-id` AS `contact-id`,
	`contact`.`uri-id` AS `contact-uri-id`,
	`contact`.`url` AS `contact-link`,
	`contact`.`addr` AS `contact-addr`,
	`contact`.`name` AS `contact-name`,
	`contact`.`nick` AS `contact-nick`,
	`contact`.`thumb` AS `contact-avatar`,
	`contact`.`network` AS `contact-network`,
	`contact`.`blocked` AS `contact-blocked`,
	`contact`.`hidden` AS `contact-hidden`,
	`contact`.`readonly` AS `contact-readonly`,
	`contact`.`archive` AS `contact-archive`,
	`contact`.`pending` AS `contact-pending`,
	`contact`.`rel` AS `contact-rel`,
	`contact`.`uid` AS `contact-uid`,
	`contact`.`contact-type` AS `contact-contact-type`,
	IF (`post-user`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `contact`.`writable`) AS `writable`,
	`contact`.`self` AS `self`,
	`contact`.`id` AS `cid`,
	`contact`.`alias` AS `alias`,
	`contact`.`photo` AS `photo`,
	`contact`.`name-date` AS `name-date`,
	`contact`.`uri-date` AS `uri-date`,
	`contact`.`avatar-date` AS `avatar-date`,
	`contact`.`thumb` AS `thumb`,
	`post-thread-user`.`author-id` AS `author-id`,
	`author`.`uri-id` AS `author-uri-id`,
	`author`.`url` AS `author-link`,
	`author`.`addr` AS `author-addr`,
	IF (`contact`.`url` = `author`.`url` AND `contact`.`name` != '', `contact`.`name`, `author`.`name`) AS `author-name`,
	`author`.`nick` AS `author-nick`,
	IF (`contact`.`url` = `author`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `author`.`thumb`) AS `author-avatar`,
	`author`.`network` AS `author-network`,
	`author`.`blocked` AS `author-blocked`,
	`author`.`hidden` AS `author-hidden`,
	`author`.`updated` AS `author-updated`,
	`author`.`gsid` AS `author-gsid`,
	`post-thread-user`.`owner-id` AS `owner-id`,
	`owner`.`uri-id` AS `owner-uri-id`,
	`owner`.`url` AS `owner-link`,
	`owner`.`addr` AS `owner-addr`,
	IF (`contact`.`url` = `owner`.`url` AND `contact`.`name` != '', `contact`.`name`, `owner`.`name`) AS `owner-name`,
	`owner`.`nick` AS `owner-nick`,
	IF (`contact`.`url` = `owner`.`url` AND `contact`.`thumb` != '', `contact`.`thumb`, `owner`.`thumb`) AS `owner-avatar`,
	`owner`.`network` AS `owner-network`,
	`owner`.`blocked` AS `owner-blocked`,
	`owner`.`hidden` AS `owner-hidden`,
	`owner`.`updated` AS `owner-updated`,
	`owner`.`contact-type` AS `owner-contact-type`,
	`post-thread-user`.`causer-id` AS `causer-id`,
	`causer`.`uri-id` AS `causer-uri-id`,
	`causer`.`url` AS `causer-link`,
	`causer`.`addr` AS `causer-addr`,
	`causer`.`name` AS `causer-name`,
	`causer`.`nick` AS `causer-nick`,
	`causer`.`thumb` AS `causer-avatar`,
	`causer`.`network` AS `causer-network`,
	`causer`.`blocked` AS `causer-blocked`,
	`causer`.`hidden` AS `causer-hidden`,
	`causer`.`contact-type` AS `causer-contact-type`,
	`post-delivery-data`.`postopts` AS `postopts`,
	`post-delivery-data`.`inform` AS `inform`,
	`post-delivery-data`.`queue_count` AS `delivery_queue_count`,
	`post-delivery-data`.`queue_done` AS `delivery_queue_done`,
	`post-delivery-data`.`queue_failed` AS `delivery_queue_failed`,
	IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`allow_cid`) AS `allow_cid`,
	IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`allow_gid`) AS `allow_gid`,
	IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`deny_cid`) AS `deny_cid`,
	IF (`post-thread-user`.`psid` IS NULL, '', `permissionset`.`deny_gid`) AS `deny_gid`,
	`post-user`.`event-id` AS `event-id`,
	`event`.`created` AS `event-created`,
	`event`.`edited` AS `event-edited`,
	`event`.`start` AS `event-start`,
	`event`.`finish` AS `event-finish`,
	`event`.`summary` AS `event-summary`,
	`event`.`desc` AS `event-desc`,
	`event`.`location` AS `event-location`,
	`event`.`type` AS `event-type`,
	`event`.`nofinish` AS `event-nofinish`,
	`event`.`ignore` AS `event-ignore`,
	`post-question`.`id` AS `question-id`,
	`post-question`.`multiple` AS `question-multiple`,
	`post-question`.`voters` AS `question-voters`,
	`post-question`.`end-time` AS `question-end-time`,
	EXISTS(SELECT `uri-id` FROM `post-category` WHERE `post-category`.`uri-id` = `post-thread-user`.`uri-id` AND `post-category`.`uid` = `post-thread-user`.`uid`) AS `has-categories`,
	EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post-thread-user`.`uri-id`) AS `has-media`,
	`diaspora-interaction`.`interaction` AS `signed_text`,
	`parent-item-uri`.`guid` AS `parent-guid`,
	`parent-post`.`network` AS `parent-network`,
	`parent-post`.`author-id` AS `parent-author-id`,
	`parent-post-author`.`url` AS `parent-author-link`,
	`parent-post-author`.`name` AS `parent-author-name`,
	`parent-post-author`.`network` AS `parent-author-network`
	FROM `post-thread-user`
			INNER JOIN `post-user` ON `post-user`.`id` = `post-thread-user`.`post-user-id`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `post-thread-user`.`contact-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `post-thread-user`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `post-thread-user`.`owner-id`
			LEFT JOIN `contact` AS `causer` ON `causer`.`id` = `post-thread-user`.`causer-id`
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `post-thread-user`.`uri-id`
			LEFT JOIN `item-uri` AS `thr-parent-item-uri` ON `thr-parent-item-uri`.`id` = `post-user`.`thr-parent-id`
			LEFT JOIN `item-uri` AS `parent-item-uri` ON `parent-item-uri`.`id` = `post-user`.`parent-uri-id`
			LEFT JOIN `item-uri` AS `conversation-item-uri` ON `conversation-item-uri`.`id` = `post-thread-user`.`conversation-id`
			LEFT JOIN `item-uri` AS `external-item-uri` ON `external-item-uri`.`id` = `post-user`.`external-id`
			LEFT JOIN `verb` ON `verb`.`id` = `post-user`.`vid`
			LEFT JOIN `event` ON `event`.`id` = `post-user`.`event-id`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `post-thread-user`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `post-thread-user`.`uri-id`
			LEFT JOIN `item-uri` AS `quote-item-uri` ON `quote-item-uri`.`id` = `post-content`.`quote-uri-id`
			LEFT JOIN `post-delivery-data` ON `post-delivery-data`.`uri-id` = `post-thread-user`.`uri-id` AND `post-thread-user`.`origin`
			LEFT JOIN `post-question` ON `post-question`.`uri-id` = `post-thread-user`.`uri-id`
			LEFT JOIN `permissionset` ON `permissionset`.`id` = `post-thread-user`.`psid`
			LEFT JOIN `post-user` AS `parent-post` ON `parent-post`.`uri-id` = `post-user`.`parent-uri-id` AND `parent-post`.`uid` = `post-thread-user`.`uid`
			LEFT JOIN `contact` AS `parent-post-author` ON `parent-post-author`.`id` = `parent-post`.`author-id`;

--
-- VIEW post-view
--
DROP VIEW IF EXISTS `post-view`;
CREATE VIEW `post-view` AS SELECT 
	`item-uri`.`uri` AS `uri`,
	`post`.`uri-id` AS `uri-id`,
	`parent-item-uri`.`uri` AS `parent-uri`,
	`post`.`parent-uri-id` AS `parent-uri-id`,
	`thr-parent-item-uri`.`uri` AS `thr-parent`,
	`post`.`thr-parent-id` AS `thr-parent-id`,
	`conversation-item-uri`.`uri` AS `conversation`,
	`post-thread`.`conversation-id` AS `conversation-id`,
	`quote-item-uri`.`uri` AS `quote-uri`,
	`post-content`.`quote-uri-id` AS `quote-uri-id`,
	`item-uri`.`guid` AS `guid`,
	`post`.`gravity` AS `gravity`,
	`external-item-uri`.`uri` AS `extid`,
	`post`.`external-id` AS `external-id`,
	`post`.`created` AS `created`,
	`post`.`edited` AS `edited`,
	`post-thread`.`commented` AS `commented`,
	`post`.`received` AS `received`,
	`post-thread`.`changed` AS `changed`,
	`post`.`post-type` AS `post-type`,
	`post`.`private` AS `private`,
	`post`.`visible` AS `visible`,
	`post`.`deleted` AS `deleted`,
	`post`.`global` AS `global`,
	EXISTS(SELECT `type` FROM `post-collection` WHERE `type` = 0 AND `uri-id` = `post`.`uri-id`) AS `featured`,
	`post`.`network` AS `network`,
	`post`.`vid` AS `vid`,
	IF (`post`.`vid` IS NULL, '', `verb`.`name`) AS `verb`,
	`post-content`.`title` AS `title`,
	`post-content`.`content-warning` AS `content-warning`,
	`post-content`.`raw-body` AS `raw-body`,
	`post-content`.`body` AS `body`,
	`post-content`.`rendered-hash` AS `rendered-hash`,
	`post-content`.`rendered-html` AS `rendered-html`,
	`post-content`.`language` AS `language`,
	`post-content`.`plink` AS `plink`,
	`post-content`.`location` AS `location`,
	`post-content`.`coord` AS `coord`,
	`post-content`.`app` AS `app`,
	`post-content`.`object-type` AS `object-type`,
	`post-content`.`object` AS `object`,
	`post-content`.`target-type` AS `target-type`,
	`post-content`.`target` AS `target`,
	`post-content`.`resource-id` AS `resource-id`,
	`post`.`author-id` AS `contact-id`,
	`author`.`uri-id` AS `contact-uri-id`,
	`author`.`url` AS `contact-link`,
	`author`.`addr` AS `contact-addr`,
	`author`.`name` AS `contact-name`,
	`author`.`nick` AS `contact-nick`,
	`author`.`thumb` AS `contact-avatar`,
	`author`.`network` AS `contact-network`,
	`author`.`blocked` AS `contact-blocked`,
	`author`.`hidden` AS `contact-hidden`,
	`author`.`readonly` AS `contact-readonly`,
	`author`.`archive` AS `contact-archive`,
	`author`.`pending` AS `contact-pending`,
	`author`.`rel` AS `contact-rel`,
	`author`.`uid` AS `contact-uid`,
	`author`.`contact-type` AS `contact-contact-type`,
	IF (`post`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `author`.`writable`) AS `writable`,
	false AS `self`,
	`author`.`id` AS `cid`,
	`author`.`alias` AS `alias`,
	`author`.`photo` AS `photo`,
	`author`.`name-date` AS `name-date`,
	`author`.`uri-date` AS `uri-date`,
	`author`.`avatar-date` AS `avatar-date`,
	`author`.`thumb` AS `thumb`,
	`post`.`author-id` AS `author-id`,
	`author`.`uri-id` AS `author-uri-id`,
	`author`.`url` AS `author-link`,
	`author`.`addr` AS `author-addr`,
	`author`.`name` AS `author-name`,
	`author`.`nick` AS `author-nick`,
	`author`.`thumb` AS `author-avatar`,
	`author`.`network` AS `author-network`,
	`author`.`blocked` AS `author-blocked`,
	`author`.`hidden` AS `author-hidden`,
	`author`.`updated` AS `author-updated`,
	`author`.`gsid` AS `author-gsid`,
	`post`.`owner-id` AS `owner-id`,
	`owner`.`uri-id` AS `owner-uri-id`,
	`owner`.`url` AS `owner-link`,
	`owner`.`addr` AS `owner-addr`,
	`owner`.`name` AS `owner-name`,
	`owner`.`nick` AS `owner-nick`,
	`owner`.`thumb` AS `owner-avatar`,
	`owner`.`network` AS `owner-network`,
	`owner`.`blocked` AS `owner-blocked`,
	`owner`.`hidden` AS `owner-hidden`,
	`owner`.`updated` AS `owner-updated`,
	`owner`.`contact-type` AS `owner-contact-type`,
	`post`.`causer-id` AS `causer-id`,
	`causer`.`uri-id` AS `causer-uri-id`,
	`causer`.`url` AS `causer-link`,
	`causer`.`addr` AS `causer-addr`,
	`causer`.`name` AS `causer-name`,
	`causer`.`nick` AS `causer-nick`,
	`causer`.`thumb` AS `causer-avatar`,
	`causer`.`network` AS `causer-network`,
	`causer`.`blocked` AS `causer-blocked`,
	`causer`.`hidden` AS `causer-hidden`,
	`causer`.`contact-type` AS `causer-contact-type`,
	`post-question`.`id` AS `question-id`,
	`post-question`.`multiple` AS `question-multiple`,
	`post-question`.`voters` AS `question-voters`,
	`post-question`.`end-time` AS `question-end-time`,
	0 AS `has-categories`,
	EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post`.`uri-id`) AS `has-media`,
	`diaspora-interaction`.`interaction` AS `signed_text`,
	`parent-item-uri`.`guid` AS `parent-guid`,
	`parent-post`.`network` AS `parent-network`,
	`parent-post`.`author-id` AS `parent-author-id`,
	`parent-post-author`.`url` AS `parent-author-link`,
	`parent-post-author`.`name` AS `parent-author-name`,
	`parent-post-author`.`network` AS `parent-author-network`
	FROM `post`
			STRAIGHT_JOIN `post-thread` ON `post-thread`.`uri-id` = `post`.`parent-uri-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `post`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `post`.`owner-id`
			LEFT JOIN `contact` AS `causer` ON `causer`.`id` = `post`.`causer-id`
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `post`.`uri-id`
			LEFT JOIN `item-uri` AS `thr-parent-item-uri` ON `thr-parent-item-uri`.`id` = `post`.`thr-parent-id`
			LEFT JOIN `item-uri` AS `parent-item-uri` ON `parent-item-uri`.`id` = `post`.`parent-uri-id`
			LEFT JOIN `item-uri` AS `conversation-item-uri` ON `conversation-item-uri`.`id` = `post-thread`.`conversation-id`
			LEFT JOIN `item-uri` AS `external-item-uri` ON `external-item-uri`.`id` = `post`.`external-id`
			LEFT JOIN `verb` ON `verb`.`id` = `post`.`vid`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `post`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `post`.`uri-id`
			LEFT JOIN `item-uri` AS `quote-item-uri` ON `quote-item-uri`.`id` = `post-content`.`quote-uri-id`
			LEFT JOIN `post-question` ON `post-question`.`uri-id` = `post`.`uri-id`
			LEFT JOIN `post` AS `parent-post` ON `parent-post`.`uri-id` = `post`.`parent-uri-id`
			LEFT JOIN `contact` AS `parent-post-author` ON `parent-post-author`.`id` = `parent-post`.`author-id`;

--
-- VIEW post-thread-view
--
DROP VIEW IF EXISTS `post-thread-view`;
CREATE VIEW `post-thread-view` AS SELECT 
	`item-uri`.`uri` AS `uri`,
	`post-thread`.`uri-id` AS `uri-id`,
	`parent-item-uri`.`uri` AS `parent-uri`,
	`post`.`parent-uri-id` AS `parent-uri-id`,
	`thr-parent-item-uri`.`uri` AS `thr-parent`,
	`post`.`thr-parent-id` AS `thr-parent-id`,
	`conversation-item-uri`.`uri` AS `conversation`,
	`post-thread`.`conversation-id` AS `conversation-id`,
	`quote-item-uri`.`uri` AS `quote-uri`,
	`post-content`.`quote-uri-id` AS `quote-uri-id`,
	`item-uri`.`guid` AS `guid`,
	`post`.`gravity` AS `gravity`,
	`external-item-uri`.`uri` AS `extid`,
	`post`.`external-id` AS `external-id`,
	`post-thread`.`created` AS `created`,
	`post`.`edited` AS `edited`,
	`post-thread`.`commented` AS `commented`,
	`post-thread`.`received` AS `received`,
	`post-thread`.`changed` AS `changed`,
	`post`.`post-type` AS `post-type`,
	`post`.`private` AS `private`,
	`post`.`visible` AS `visible`,
	`post`.`deleted` AS `deleted`,
	`post`.`global` AS `global`,
	EXISTS(SELECT `type` FROM `post-collection` WHERE `type` = 0 AND `uri-id` = `post-thread`.`uri-id`) AS `featured`,
	`post-thread`.`network` AS `network`,
	`post`.`vid` AS `vid`,
	IF (`post`.`vid` IS NULL, '', `verb`.`name`) AS `verb`,
	`post-content`.`title` AS `title`,
	`post-content`.`content-warning` AS `content-warning`,
	`post-content`.`raw-body` AS `raw-body`,
	`post-content`.`body` AS `body`,
	`post-content`.`rendered-hash` AS `rendered-hash`,
	`post-content`.`rendered-html` AS `rendered-html`,
	`post-content`.`language` AS `language`,
	`post-content`.`plink` AS `plink`,
	`post-content`.`location` AS `location`,
	`post-content`.`coord` AS `coord`,
	`post-content`.`app` AS `app`,
	`post-content`.`object-type` AS `object-type`,
	`post-content`.`object` AS `object`,
	`post-content`.`target-type` AS `target-type`,
	`post-content`.`target` AS `target`,
	`post-content`.`resource-id` AS `resource-id`,
	`post-thread`.`author-id` AS `contact-id`,
	`author`.`uri-id` AS `contact-uri-id`,
	`author`.`url` AS `contact-link`,
	`author`.`addr` AS `contact-addr`,
	`author`.`name` AS `contact-name`,
	`author`.`nick` AS `contact-nick`,
	`author`.`thumb` AS `contact-avatar`,
	`author`.`network` AS `contact-network`,
	`author`.`blocked` AS `contact-blocked`,
	`author`.`hidden` AS `contact-hidden`,
	`author`.`readonly` AS `contact-readonly`,
	`author`.`archive` AS `contact-archive`,
	`author`.`pending` AS `contact-pending`,
	`author`.`rel` AS `contact-rel`,
	`author`.`uid` AS `contact-uid`,
	`author`.`contact-type` AS `contact-contact-type`,
	IF (`post`.`network` IN ('apub', 'dfrn', 'dspr', 'stat'), true, `author`.`writable`) AS `writable`,
	false AS `self`,
	`author`.`id` AS `cid`,
	`author`.`alias` AS `alias`,
	`author`.`photo` AS `photo`,
	`author`.`name-date` AS `name-date`,
	`author`.`uri-date` AS `uri-date`,
	`author`.`avatar-date` AS `avatar-date`,
	`author`.`thumb` AS `thumb`,
	`post-thread`.`author-id` AS `author-id`,
	`author`.`uri-id` AS `author-uri-id`,
	`author`.`url` AS `author-link`,
	`author`.`addr` AS `author-addr`,
	`author`.`name` AS `author-name`,
	`author`.`nick` AS `author-nick`,
	`author`.`thumb` AS `author-avatar`,
	`author`.`network` AS `author-network`,
	`author`.`blocked` AS `author-blocked`,
	`author`.`hidden` AS `author-hidden`,
	`author`.`updated` AS `author-updated`,
	`author`.`gsid` AS `author-gsid`,
	`post-thread`.`owner-id` AS `owner-id`,
	`owner`.`uri-id` AS `owner-uri-id`,
	`owner`.`url` AS `owner-link`,
	`owner`.`addr` AS `owner-addr`,
	`owner`.`name` AS `owner-name`,
	`owner`.`nick` AS `owner-nick`,
	`owner`.`thumb` AS `owner-avatar`,
	`owner`.`network` AS `owner-network`,
	`owner`.`blocked` AS `owner-blocked`,
	`owner`.`hidden` AS `owner-hidden`,
	`owner`.`updated` AS `owner-updated`,
	`owner`.`contact-type` AS `owner-contact-type`,
	`post-thread`.`causer-id` AS `causer-id`,
	`causer`.`uri-id` AS `causer-uri-id`,
	`causer`.`url` AS `causer-link`,
	`causer`.`addr` AS `causer-addr`,
	`causer`.`name` AS `causer-name`,
	`causer`.`nick` AS `causer-nick`,
	`causer`.`thumb` AS `causer-avatar`,
	`causer`.`network` AS `causer-network`,
	`causer`.`blocked` AS `causer-blocked`,
	`causer`.`hidden` AS `causer-hidden`,
	`causer`.`contact-type` AS `causer-contact-type`,
	`post-question`.`id` AS `question-id`,
	`post-question`.`multiple` AS `question-multiple`,
	`post-question`.`voters` AS `question-voters`,
	`post-question`.`end-time` AS `question-end-time`,
	0 AS `has-categories`,
	EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post-thread`.`uri-id`) AS `has-media`,
	(SELECT COUNT(*) FROM `post` WHERE `parent-uri-id` = `post-thread`.`uri-id` AND `gravity` = 6) AS `total-comments`,
	(SELECT COUNT(DISTINCT(`author-id`)) FROM `post` WHERE `parent-uri-id` = `post-thread`.`uri-id` AND `gravity` = 6) AS `total-actors`,
	`diaspora-interaction`.`interaction` AS `signed_text`,
	`parent-item-uri`.`guid` AS `parent-guid`,
	`parent-post`.`network` AS `parent-network`,
	`parent-post`.`author-id` AS `parent-author-id`,
	`parent-post-author`.`url` AS `parent-author-link`,
	`parent-post-author`.`name` AS `parent-author-name`,
	`parent-post-author`.`network` AS `parent-author-network`
	FROM `post-thread`
			INNER JOIN `post` ON `post`.`uri-id` = `post-thread`.`uri-id`
			STRAIGHT_JOIN `contact` AS `author` ON `author`.`id` = `post-thread`.`author-id`
			STRAIGHT_JOIN `contact` AS `owner` ON `owner`.`id` = `post-thread`.`owner-id`
			LEFT JOIN `contact` AS `causer` ON `causer`.`id` = `post-thread`.`causer-id`
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `post-thread`.`uri-id`
			LEFT JOIN `item-uri` AS `thr-parent-item-uri` ON `thr-parent-item-uri`.`id` = `post`.`thr-parent-id`
			LEFT JOIN `item-uri` AS `parent-item-uri` ON `parent-item-uri`.`id` = `post`.`parent-uri-id`
			LEFT JOIN `item-uri` AS `conversation-item-uri` ON `conversation-item-uri`.`id` = `post-thread`.`conversation-id`
			LEFT JOIN `item-uri` AS `external-item-uri` ON `external-item-uri`.`id` = `post`.`external-id`
			LEFT JOIN `verb` ON `verb`.`id` = `post`.`vid`
			LEFT JOIN `diaspora-interaction` ON `diaspora-interaction`.`uri-id` = `post-thread`.`uri-id`
			LEFT JOIN `post-content` ON `post-content`.`uri-id` = `post-thread`.`uri-id`
			LEFT JOIN `item-uri` AS `quote-item-uri` ON `quote-item-uri`.`id` = `post-content`.`quote-uri-id`
			LEFT JOIN `post-question` ON `post-question`.`uri-id` = `post-thread`.`uri-id`
			LEFT JOIN `post` AS `parent-post` ON `parent-post`.`uri-id` = `post`.`parent-uri-id`
			LEFT JOIN `contact` AS `parent-post-author` ON `parent-post-author`.`id` = `parent-post`.`author-id`;

--
-- VIEW category-view
--
DROP VIEW IF EXISTS `category-view`;
CREATE VIEW `category-view` AS SELECT 
	`post-category`.`uri-id` AS `uri-id`,
	`post-category`.`uid` AS `uid`,
	`post-category`.`type` AS `type`,
	`post-category`.`tid` AS `tid`,
	`tag`.`name` AS `name`,
	`tag`.`url` AS `url`
	FROM `post-category`
			LEFT JOIN `tag` ON `post-category`.`tid` = `tag`.`id`;

--
-- VIEW collection-view
--
DROP VIEW IF EXISTS `collection-view`;
CREATE VIEW `collection-view` AS SELECT 
	`post-collection`.`uri-id` AS `uri-id`,
	`post-collection`.`type` AS `type`,
	`post-collection`.`author-id` AS `cid`,
	`post`.`received` AS `received`,
	`post`.`created` AS `created`,
	`post-thread`.`commented` AS `commented`,
	`post`.`private` AS `private`,
	`post`.`visible` AS `visible`,
	`post`.`deleted` AS `deleted`,
	`post`.`thr-parent-id` AS `thr-parent-id`,
	`post-collection`.`author-id` AS `author-id`,
	`post`.`gravity` AS `gravity`
	FROM `post-collection`
			INNER JOIN `post` ON `post-collection`.`uri-id` = `post`.`uri-id`
			INNER JOIN `post-thread` ON `post-thread`.`uri-id` = `post`.`parent-uri-id`;

--
-- VIEW media-view
--
DROP VIEW IF EXISTS `media-view`;
CREATE VIEW `media-view` AS SELECT 
	`post-media`.`uri-id` AS `uri-id`,
	`post-media`.`type` AS `type`,
	`post`.`received` AS `received`,
	`post`.`created` AS `created`,
	`post`.`private` AS `private`,
	`post`.`visible` AS `visible`,
	`post`.`deleted` AS `deleted`,
	`post`.`thr-parent-id` AS `thr-parent-id`,
	`post`.`author-id` AS `author-id`,
	`post`.`gravity` AS `gravity`
	FROM `post-media`
			INNER JOIN `post` ON `post-media`.`uri-id` = `post`.`uri-id`;

--
-- VIEW tag-view
--
DROP VIEW IF EXISTS `tag-view`;
CREATE VIEW `tag-view` AS SELECT 
	`post-tag`.`uri-id` AS `uri-id`,
	`post-tag`.`type` AS `type`,
	`post-tag`.`tid` AS `tid`,
	`post-tag`.`cid` AS `cid`,
	CASE `cid` WHEN 0 THEN `tag`.`name` ELSE `contact`.`name` END AS `name`,
	CASE `cid` WHEN 0 THEN `tag`.`url` ELSE `contact`.`url` END AS `url`,
	CASE `cid` WHEN 0 THEN `tag`.`type` ELSE 1 END AS `tag-type`
	FROM `post-tag`
			LEFT JOIN `tag` ON `post-tag`.`tid` = `tag`.`id`
			LEFT JOIN `contact` ON `post-tag`.`cid` = `contact`.`id`;

--
-- VIEW network-item-view
--
DROP VIEW IF EXISTS `network-item-view`;
CREATE VIEW `network-item-view` AS SELECT 
	`post-user`.`uri-id` AS `uri-id`,
	`parent-post`.`id` AS `parent`,
	`post-user`.`received` AS `received`,
	`post-thread-user`.`commented` AS `commented`,
	`post-user`.`created` AS `created`,
	`post-user`.`uid` AS `uid`,
	`post-thread-user`.`starred` AS `starred`,
	`post-thread-user`.`mention` AS `mention`,
	`post-user`.`network` AS `network`,
	`post-user`.`unseen` AS `unseen`,
	`post-user`.`gravity` AS `gravity`,
	`post-user`.`contact-id` AS `contact-id`,
	`ownercontact`.`contact-type` AS `contact-type`
	FROM `post-user`
			STRAIGHT_JOIN `post-thread-user` ON `post-thread-user`.`uri-id` = `post-user`.`parent-uri-id` AND `post-thread-user`.`uid` = `post-user`.`uid`			
			INNER JOIN `contact` ON `contact`.`id` = `post-thread-user`.`contact-id`
			LEFT JOIN `user-contact` AS `author` ON `author`.`uid` = `post-thread-user`.`uid` AND `author`.`cid` = `post-thread-user`.`author-id`
			LEFT JOIN `user-contact` AS `owner` ON `owner`.`uid` = `post-thread-user`.`uid` AND `owner`.`cid` = `post-thread-user`.`owner-id`
			INNER JOIN `contact` AS `ownercontact` ON `ownercontact`.`id` = `post-thread-user`.`owner-id`
			LEFT JOIN `post-user` AS `parent-post` ON `parent-post`.`uri-id` = `post-user`.`parent-uri-id` AND `parent-post`.`uid` = `post-user`.`uid`
			WHERE `post-user`.`visible` AND NOT `post-user`.`deleted`
			AND (NOT `contact`.`readonly` AND NOT `contact`.`blocked` AND NOT `contact`.`pending`)
			AND (`post-user`.`hidden` IS NULL OR NOT `post-user`.`hidden`)
			AND (`author`.`blocked` IS NULL OR NOT `author`.`blocked`)
			AND (`owner`.`blocked` IS NULL OR NOT `owner`.`blocked`);

--
-- VIEW network-thread-view
--
DROP VIEW IF EXISTS `network-thread-view`;
CREATE VIEW `network-thread-view` AS SELECT 
	`post-thread-user`.`uri-id` AS `uri-id`,
	`parent-post`.`id` AS `parent`,
	`post-thread-user`.`received` AS `received`,
	`post-thread-user`.`commented` AS `commented`,
	`post-thread-user`.`created` AS `created`,
	`post-thread-user`.`uid` AS `uid`,
	`post-thread-user`.`starred` AS `starred`,
	`post-thread-user`.`mention` AS `mention`,
	`post-thread-user`.`network` AS `network`,
	`post-thread-user`.`contact-id` AS `contact-id`,
	`ownercontact`.`contact-type` AS `contact-type`
	FROM `post-thread-user`
			INNER JOIN `post-user` ON `post-user`.`id` = `post-thread-user`.`post-user-id`
			STRAIGHT_JOIN `contact` ON `contact`.`id` = `post-thread-user`.`contact-id`
			LEFT JOIN `user-contact` AS `author` ON `author`.`uid` = `post-thread-user`.`uid` AND `author`.`cid` = `post-thread-user`.`author-id`
			LEFT JOIN `user-contact` AS `owner` ON `owner`.`uid` = `post-thread-user`.`uid` AND `owner`.`cid` = `post-thread-user`.`owner-id`
			LEFT JOIN `contact` AS `ownercontact` ON `ownercontact`.`id` = `post-thread-user`.`owner-id`
			LEFT JOIN `post-user` AS `parent-post` ON `parent-post`.`uri-id` = `post-user`.`parent-uri-id` AND `parent-post`.`uid` = `post-user`.`uid`
			WHERE `post-user`.`visible` AND NOT `post-user`.`deleted`
			AND (NOT `contact`.`readonly` AND NOT `contact`.`blocked` AND NOT `contact`.`pending`)
			AND (`post-thread-user`.`hidden` IS NULL OR NOT `post-thread-user`.`hidden`)
			AND (`author`.`blocked` IS NULL OR NOT `author`.`blocked`)
			AND (`owner`.`blocked` IS NULL OR NOT `owner`.`blocked`);

--
-- VIEW owner-view
--
DROP VIEW IF EXISTS `owner-view`;
CREATE VIEW `owner-view` AS SELECT 
	`contact`.`id` AS `id`,
	`contact`.`uid` AS `uid`,
	`contact`.`created` AS `created`,
	`contact`.`updated` AS `updated`,
	`contact`.`self` AS `self`,
	`contact`.`remote_self` AS `remote_self`,
	`contact`.`rel` AS `rel`,
	`contact`.`network` AS `network`,
	`contact`.`protocol` AS `protocol`,
	`contact`.`name` AS `name`,
	`contact`.`nick` AS `nick`,
	`contact`.`location` AS `location`,
	`contact`.`about` AS `about`,
	`contact`.`keywords` AS `keywords`,
	`contact`.`xmpp` AS `xmpp`,
	`contact`.`matrix` AS `matrix`,
	`contact`.`attag` AS `attag`,
	`contact`.`avatar` AS `avatar`,
	`contact`.`photo` AS `photo`,
	`contact`.`thumb` AS `thumb`,
	`contact`.`micro` AS `micro`,
	`contact`.`header` AS `header`,
	`contact`.`url` AS `url`,
	`contact`.`nurl` AS `nurl`,
	`contact`.`uri-id` AS `uri-id`,
	`contact`.`addr` AS `addr`,
	`contact`.`alias` AS `alias`,
	`contact`.`pubkey` AS `pubkey`,
	`contact`.`prvkey` AS `prvkey`,
	`contact`.`batch` AS `batch`,
	`contact`.`request` AS `request`,
	`contact`.`notify` AS `notify`,
	`contact`.`poll` AS `poll`,
	`contact`.`confirm` AS `confirm`,
	`contact`.`poco` AS `poco`,
	`contact`.`subhub` AS `subhub`,
	`contact`.`hub-verify` AS `hub-verify`,
	`contact`.`last-update` AS `last-update`,
	`contact`.`success_update` AS `success_update`,
	`contact`.`failure_update` AS `failure_update`,
	`contact`.`name-date` AS `name-date`,
	`contact`.`uri-date` AS `uri-date`,
	`contact`.`avatar-date` AS `avatar-date`,
	`contact`.`avatar-date` AS `picdate`,
	`contact`.`term-date` AS `term-date`,
	`contact`.`last-item` AS `last-item`,
	`contact`.`priority` AS `priority`,
	`user`.`blocked` AS `blocked`,
	`contact`.`block_reason` AS `block_reason`,
	`contact`.`readonly` AS `readonly`,
	`contact`.`writable` AS `writable`,
	`contact`.`forum` AS `forum`,
	`contact`.`prv` AS `prv`,
	`contact`.`contact-type` AS `contact-type`,
	`contact`.`manually-approve` AS `manually-approve`,
	`contact`.`hidden` AS `hidden`,
	`contact`.`archive` AS `archive`,
	`contact`.`pending` AS `pending`,
	`contact`.`deleted` AS `deleted`,
	`contact`.`unsearchable` AS `unsearchable`,
	`contact`.`sensitive` AS `sensitive`,
	`contact`.`baseurl` AS `baseurl`,
	`contact`.`reason` AS `reason`,
	`contact`.`info` AS `info`,
	`contact`.`bdyear` AS `bdyear`,
	`contact`.`bd` AS `bd`,
	`contact`.`notify_new_posts` AS `notify_new_posts`,
	`contact`.`fetch_further_information` AS `fetch_further_information`,
	`contact`.`ffi_keyword_denylist` AS `ffi_keyword_denylist`,
	`user`.`parent-uid` AS `parent-uid`,
	`user`.`guid` AS `guid`,
	`user`.`nickname` AS `nickname`,
	`user`.`email` AS `email`,
	`user`.`openid` AS `openid`,
	`user`.`timezone` AS `timezone`,
	`user`.`language` AS `language`,
	`user`.`register_date` AS `register_date`,
	`user`.`login_date` AS `login_date`,
	`user`.`default-location` AS `default-location`,
	`user`.`allow_location` AS `allow_location`,
	`user`.`theme` AS `theme`,
	`user`.`pubkey` AS `upubkey`,
	`user`.`prvkey` AS `uprvkey`,
	`user`.`sprvkey` AS `sprvkey`,
	`user`.`spubkey` AS `spubkey`,
	`user`.`verified` AS `verified`,
	`user`.`blockwall` AS `blockwall`,
	`user`.`hidewall` AS `hidewall`,
	`user`.`blocktags` AS `blocktags`,
	`user`.`unkmail` AS `unkmail`,
	`user`.`cntunkmail` AS `cntunkmail`,
	`user`.`notify-flags` AS `notify-flags`,
	`user`.`page-flags` AS `page-flags`,
	`user`.`account-type` AS `account-type`,
	`user`.`prvnets` AS `prvnets`,
	`user`.`maxreq` AS `maxreq`,
	`user`.`expire` AS `expire`,
	`user`.`account_removed` AS `account_removed`,
	`user`.`account_expired` AS `account_expired`,
	`user`.`account_expires_on` AS `account_expires_on`,
	`user`.`expire_notification_sent` AS `expire_notification_sent`,
	`user`.`def_gid` AS `def_gid`,
	`user`.`allow_cid` AS `allow_cid`,
	`user`.`allow_gid` AS `allow_gid`,
	`user`.`deny_cid` AS `deny_cid`,
	`user`.`deny_gid` AS `deny_gid`,
	`user`.`openidserver` AS `openidserver`,
	`profile`.`publish` AS `publish`,
	`profile`.`net-publish` AS `net-publish`,
	`profile`.`hide-friends` AS `hide-friends`,
	`profile`.`prv_keywords` AS `prv_keywords`,
	`profile`.`pub_keywords` AS `pub_keywords`,
	`profile`.`address` AS `address`,
	`profile`.`locality` AS `locality`,
	`profile`.`region` AS `region`,
	`profile`.`postal-code` AS `postal-code`,
	`profile`.`country-name` AS `country-name`,
	`profile`.`homepage` AS `homepage`,
	`profile`.`homepage_verified` AS `homepage_verified`,
	`profile`.`dob` AS `dob`
	FROM `user`
			INNER JOIN `contact` ON `contact`.`uid` = `user`.`uid` AND `contact`.`self`
			INNER JOIN `profile` ON `profile`.`uid` = `user`.`uid`;

--
-- VIEW account-view
--
DROP VIEW IF EXISTS `account-view`;
CREATE VIEW `account-view` AS SELECT 
	`contact`.`id` AS `id`,
	`contact`.`url` AS `url`,
	`contact`.`nurl` AS `nurl`,
	`contact`.`uri-id` AS `uri-id`,
	`item-uri`.`guid` AS `guid`,
	`contact`.`addr` AS `addr`,
	`contact`.`alias` AS `alias`,
	`contact`.`name` AS `name`,
	`contact`.`nick` AS `nick`,
	`contact`.`about` AS `about`,
	`contact`.`keywords` AS `keywords`,
	`contact`.`xmpp` AS `xmpp`,
	`contact`.`matrix` AS `matrix`,
	`contact`.`avatar` AS `avatar`,
	`contact`.`photo` AS `photo`,
	`contact`.`thumb` AS `thumb`,
	`contact`.`micro` AS `micro`,
	`contact`.`header` AS `header`,
	`contact`.`created` AS `created`,
	`contact`.`updated` AS `updated`,
	`contact`.`network` AS `network`,
	`contact`.`protocol` AS `protocol`,
	`contact`.`location` AS `location`,
	`contact`.`attag` AS `attag`,
	`contact`.`pubkey` AS `pubkey`,
	`contact`.`prvkey` AS `prvkey`,
	`contact`.`subscribe` AS `subscribe`,
	`contact`.`last-update` AS `last-update`,
	`contact`.`success_update` AS `success_update`,
	`contact`.`failure_update` AS `failure_update`,
	`contact`.`failed` AS `failed`,
	`contact`.`last-item` AS `last-item`,
	`contact`.`last-discovery` AS `last-discovery`,
	`contact`.`contact-type` AS `contact-type`,
	`contact`.`manually-approve` AS `manually-approve`,
	`contact`.`unsearchable` AS `unsearchable`,
	`contact`.`sensitive` AS `sensitive`,
	`contact`.`baseurl` AS `baseurl`,
	`contact`.`gsid` AS `gsid`,
	`contact`.`info` AS `info`,
	`contact`.`bdyear` AS `bdyear`,
	`contact`.`bd` AS `bd`,
	`contact`.`poco` AS `poco`,
	`contact`.`name-date` AS `name-date`,
	`contact`.`uri-date` AS `uri-date`,
	`contact`.`avatar-date` AS `avatar-date`,
	`contact`.`term-date` AS `term-date`,
	`contact`.`hidden` AS `global-ignored`,
	`contact`.`blocked` AS `global-blocked`,
	`contact`.`hidden` AS `hidden`,
	`contact`.`archive` AS `archive`,
	`contact`.`deleted` AS `deleted`,
	`contact`.`blocked` AS `blocked`,
	`contact`.`notify` AS `dfrn-notify`,
	`contact`.`poll` AS `dfrn-poll`,
	`fcontact`.`guid` AS `diaspora-guid`,
	`fcontact`.`batch` AS `diaspora-batch`,
	`fcontact`.`notify` AS `diaspora-notify`,
	`fcontact`.`poll` AS `diaspora-poll`,
	`fcontact`.`alias` AS `diaspora-alias`,
	`apcontact`.`uuid` AS `ap-uuid`,
	`apcontact`.`type` AS `ap-type`,
	`apcontact`.`following` AS `ap-following`,
	`apcontact`.`followers` AS `ap-followers`,
	`apcontact`.`inbox` AS `ap-inbox`,
	`apcontact`.`outbox` AS `ap-outbox`,
	`apcontact`.`sharedinbox` AS `ap-sharedinbox`,
	`apcontact`.`generator` AS `ap-generator`,
	`apcontact`.`following_count` AS `ap-following_count`,
	`apcontact`.`followers_count` AS `ap-followers_count`,
	`apcontact`.`statuses_count` AS `ap-statuses_count`,
	`gserver`.`site_name` AS `site_name`,
	`gserver`.`platform` AS `platform`,
	`gserver`.`version` AS `version`
	FROM `contact`
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `contact`.`uri-id`
			LEFT JOIN `apcontact` ON `apcontact`.`uri-id` = `contact`.`uri-id`
			LEFT JOIN `fcontact` ON `fcontact`.`uri-id` = contact.`uri-id`
			LEFT JOIN `gserver` ON `gserver`.`id` = contact.`gsid`
			WHERE `contact`.`uid` = 0;

--
-- VIEW account-user-view
--
DROP VIEW IF EXISTS `account-user-view`;
CREATE VIEW `account-user-view` AS SELECT 
	`ucontact`.`id` AS `id`,
	`contact`.`id` AS `pid`,
	`ucontact`.`uid` AS `uid`,
	`contact`.`url` AS `url`,
	`contact`.`nurl` AS `nurl`,
	`contact`.`uri-id` AS `uri-id`,
	`item-uri`.`guid` AS `guid`,
	`contact`.`addr` AS `addr`,
	`contact`.`alias` AS `alias`,
	`contact`.`name` AS `name`,
	`contact`.`nick` AS `nick`,
	`contact`.`about` AS `about`,
	`contact`.`keywords` AS `keywords`,
	`contact`.`xmpp` AS `xmpp`,
	`contact`.`matrix` AS `matrix`,
	`contact`.`avatar` AS `avatar`,
	`contact`.`photo` AS `photo`,
	`contact`.`thumb` AS `thumb`,
	`contact`.`micro` AS `micro`,
	`contact`.`header` AS `header`,
	`contact`.`created` AS `created`,
	`contact`.`updated` AS `updated`,
	`ucontact`.`self` AS `self`,
	`ucontact`.`remote_self` AS `remote_self`,
	`ucontact`.`rel` AS `rel`,
	`contact`.`network` AS `network`,
	`ucontact`.`protocol` AS `protocol`,
	`contact`.`location` AS `location`,
	`ucontact`.`attag` AS `attag`,
	`contact`.`pubkey` AS `pubkey`,
	`contact`.`prvkey` AS `prvkey`,
	`contact`.`subscribe` AS `subscribe`,
	`contact`.`last-update` AS `last-update`,
	`contact`.`success_update` AS `success_update`,
	`contact`.`failure_update` AS `failure_update`,
	`contact`.`failed` AS `failed`,
	`contact`.`last-item` AS `last-item`,
	`contact`.`last-discovery` AS `last-discovery`,
	`contact`.`contact-type` AS `contact-type`,
	`contact`.`manually-approve` AS `manually-approve`,
	`contact`.`unsearchable` AS `unsearchable`,
	`contact`.`sensitive` AS `sensitive`,
	`contact`.`baseurl` AS `baseurl`,
	`contact`.`gsid` AS `gsid`,
	`ucontact`.`info` AS `info`,
	`contact`.`bdyear` AS `bdyear`,
	`contact`.`bd` AS `bd`,
	`contact`.`poco` AS `poco`,
	`contact`.`name-date` AS `name-date`,
	`contact`.`uri-date` AS `uri-date`,
	`contact`.`avatar-date` AS `avatar-date`,
	`contact`.`term-date` AS `term-date`,
	`contact`.`hidden` AS `global-ignored`,
	`contact`.`blocked` AS `global-blocked`,
	`ucontact`.`hidden` AS `hidden`,
	`ucontact`.`archive` AS `archive`,
	`ucontact`.`pending` AS `pending`,
	`ucontact`.`deleted` AS `deleted`,
	`ucontact`.`notify_new_posts` AS `notify_new_posts`,
	`ucontact`.`fetch_further_information` AS `fetch_further_information`,
	`ucontact`.`ffi_keyword_denylist` AS `ffi_keyword_denylist`,
	`ucontact`.`rating` AS `rating`,
	`ucontact`.`readonly` AS `readonly`,
	`ucontact`.`blocked` AS `blocked`,
	`ucontact`.`block_reason` AS `block_reason`,
	`ucontact`.`subhub` AS `subhub`,
	`ucontact`.`hub-verify` AS `hub-verify`,
	`ucontact`.`reason` AS `reason`,
	`contact`.`notify` AS `dfrn-notify`,
	`contact`.`poll` AS `dfrn-poll`,
	`fcontact`.`guid` AS `diaspora-guid`,
	`fcontact`.`batch` AS `diaspora-batch`,
	`fcontact`.`notify` AS `diaspora-notify`,
	`fcontact`.`poll` AS `diaspora-poll`,
	`fcontact`.`alias` AS `diaspora-alias`,
	`fcontact`.`interacting_count` AS `diaspora-interacting_count`,
	`fcontact`.`interacted_count` AS `diaspora-interacted_count`,
	`fcontact`.`post_count` AS `diaspora-post_count`,
	`apcontact`.`uuid` AS `ap-uuid`,
	`apcontact`.`type` AS `ap-type`,
	`apcontact`.`following` AS `ap-following`,
	`apcontact`.`followers` AS `ap-followers`,
	`apcontact`.`inbox` AS `ap-inbox`,
	`apcontact`.`outbox` AS `ap-outbox`,
	`apcontact`.`sharedinbox` AS `ap-sharedinbox`,
	`apcontact`.`generator` AS `ap-generator`,
	`apcontact`.`following_count` AS `ap-following_count`,
	`apcontact`.`followers_count` AS `ap-followers_count`,
	`apcontact`.`statuses_count` AS `ap-statuses_count`,
	`gserver`.`site_name` AS `site_name`,
	`gserver`.`platform` AS `platform`,
	`gserver`.`version` AS `version`
	FROM `contact` AS `ucontact`
			INNER JOIN `contact` ON `contact`.`uri-id` = `ucontact`.`uri-id` AND `contact`.`uid` = 0
			LEFT JOIN `item-uri` ON `item-uri`.`id` = `ucontact`.`uri-id`
			LEFT JOIN `apcontact` ON `apcontact`.`uri-id` = `ucontact`.`uri-id`
			LEFT JOIN `fcontact` ON `fcontact`.`uri-id` = `ucontact`.`uri-id` AND `fcontact`.`network` = 'dspr'
			LEFT JOIN `gserver` ON `gserver`.`id` = contact.`gsid`;

--
-- VIEW pending-view
--
DROP VIEW IF EXISTS `pending-view`;
CREATE VIEW `pending-view` AS SELECT 
	`register`.`id` AS `id`,
	`register`.`hash` AS `hash`,
	`register`.`created` AS `created`,
	`register`.`uid` AS `uid`,
	`register`.`password` AS `password`,
	`register`.`language` AS `language`,
	`register`.`note` AS `note`,
	`contact`.`self` AS `self`,
	`contact`.`name` AS `name`,
	`contact`.`url` AS `url`,
	`contact`.`micro` AS `micro`,
	`user`.`email` AS `email`,
	`contact`.`nick` AS `nick`
	FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `register`.`uid` = `user`.`uid`;

--
-- VIEW tag-search-view
--
DROP VIEW IF EXISTS `tag-search-view`;
CREATE VIEW `tag-search-view` AS SELECT 
	`post-tag`.`uri-id` AS `uri-id`,
	`post-user`.`uid` AS `uid`,
	`post-user`.`id` AS `iid`,
	`post-user`.`private` AS `private`,
	`post-user`.`wall` AS `wall`,
	`post-user`.`origin` AS `origin`,
	`post-user`.`global` AS `global`,
	`post-user`.`gravity` AS `gravity`,
	`post-user`.`received` AS `received`,
	`post-user`.`network` AS `network`,
	`post-user`.`author-id` AS `author-id`,
	`tag`.`name` AS `name`
	FROM `post-tag`
			INNER JOIN `tag` ON `tag`.`id` = `post-tag`.`tid`
			STRAIGHT_JOIN `post-user` ON `post-user`.`uri-id` = `post-tag`.`uri-id`
			WHERE `post-tag`.`type` = 1;

--
-- VIEW workerqueue-view
--
DROP VIEW IF EXISTS `workerqueue-view`;
CREATE VIEW `workerqueue-view` AS SELECT 
	`process`.`pid` AS `pid`,
	`workerqueue`.`priority` AS `priority`
	FROM `process`
			INNER JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid`
			WHERE NOT `workerqueue`.`done`;

--
-- VIEW profile_field-view
--
DROP VIEW IF EXISTS `profile_field-view`;
CREATE VIEW `profile_field-view` AS SELECT 
	`profile_field`.`id` AS `id`,
	`profile_field`.`uid` AS `uid`,
	`profile_field`.`label` AS `label`,
	`profile_field`.`value` AS `value`,
	`profile_field`.`order` AS `order`,
	`profile_field`.`psid` AS `psid`,
	`permissionset`.`allow_cid` AS `allow_cid`,
	`permissionset`.`allow_gid` AS `allow_gid`,
	`permissionset`.`deny_cid` AS `deny_cid`,
	`permissionset`.`deny_gid` AS `deny_gid`,
	`profile_field`.`created` AS `created`,
	`profile_field`.`edited` AS `edited`
	FROM `profile_field`
			INNER JOIN `permissionset` ON `permissionset`.`id` = `profile_field`.`psid`;

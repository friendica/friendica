<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Main database structure configuration file.
 *
 * Here are described all the tables, fields and indexes Friendica needs to work.
 * The entry order is mostly alphabetic - with the exception of tables that are used in foreign keys.
 *
 * Syntax (braces indicate optionale values):
 * "<table name>" => [
 *	"comment" => "Description of the table",
 *	"fields" => [
 *		"<field name>" => [
 *			"type" => "<field type>{(<field size>)} <unsigned>",
 *			"not null" => 0|1,
 *			{"extra" => "auto_increment",}
 *			{"default" => "<default value>",}
 *			{"default" => NULL_DATE,} (for datetime fields)
 *			{"primary" => "1",}
 *			{"foreign|relation" => ["<foreign key table name>" => "<foreign key field name>"],}
 *			"comment" => "Description of the fields"
 *		],
 *		...
 *	],
 *	"indexes" => [
 *		"PRIMARY" => ["<primary key field name>", ...],
 *		"<index name>" => [{"UNIQUE",} "<field name>{(<key size>)}", ...]
 *		...
 *	],
 * ],
 *
 * Whenever possible prefer "foreign" before "relation" with the foreign keys.
 * "foreign" adds true foreign keys on the database level, while "relation" is just an indicator of a table relation without any consequences
 *
 * If you need to make any change, make sure to increment the DB_UPDATE_VERSION constant value below.
 *
 */

use Friendica\Database\DBA;

// This file is required several times during the test in DbaDefinition which justifies this condition
if (!defined('DB_UPDATE_VERSION')) {
	define('DB_UPDATE_VERSION', 1577);
}

return [
	// Side tables
	"gserver" => [
		"comment" => "Global servers",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"nurl" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"version" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "The version of this server software."],
			"site_name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"info" => ["type" => "text", "comment" => ""],
			"register_policy" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
			"registered-users" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Number of registered users"],
			"active-week-users" => ["type" => "int unsigned", "comment" => "Number of active users in the last week"],
			"active-month-users" => ["type" => "int unsigned", "comment" => "Number of active users in the last month"],
			"active-halfyear-users" => ["type" => "int unsigned", "comment" => "Number of active users in the last six month"],
			"local-posts" => ["type" => "int unsigned", "comment" => "Number of local posts"],
			"local-comments" => ["type" => "int unsigned", "comment" => "Number of local comments"],
			"directory-type" => ["type" => "tinyint", "default" => "0", "comment" => "Type of directory service (Poco, Mastodon)"],
			"poco" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"openwebauth" => ["type" => "varbinary(383)", "comment" => "Path to the OpenWebAuth endpoint"],
			"authredirect" => ["type" => "varbinary(383)", "comment" => "Path to the authRedirect endpoint"],
			"noscrape" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"protocol" => ["type" => "tinyint unsigned", "comment" => "The protocol of the server"],
			"platform" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "The canonical name of this server software."],
			"repository" => ["type" => "varbinary(383)", "comment" => "The url of the source code repository of this server software."],
			"homepage" => ["type" => "varbinary(383)", "comment" => "The url of the homepage of this server software."],
			"relay-subscribe" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Has the server subscribed to the relay system"],
			"relay-scope" => ["type" => "varchar(10)", "not null" => "1", "default" => "", "comment" => "The scope of messages that the server wants to get"],
			"detection-method" => ["type" => "tinyint unsigned", "comment" => "Method that had been used to detect that server"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_poco_query" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_contact" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Last successful connection request"],
			"last_failure" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Last failed connection request"],
			"blocked" => ["type" => "boolean", "comment" => "Server is blocked"],
			"failed" => ["type" => "boolean", "comment" => "Connection failed"],
			"next_contact" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Next connection request"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"nurl" => ["UNIQUE", "nurl(190)"],
			"next_contact" => ["next_contact"],
			"network" => ["network"],
		]
	],
	"user" => [
		"comment" => "The local users",
		"fields" => [
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"parent-uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "The parent user that has full control about this user"],
			"guid" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this user"],
			"username" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this user is known by"],
			"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "encrypted password"],
			"legacy_password" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is the password hash double-hashed?"],
			"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "nick- and user name"],
			"email" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "the users email address"],
			"openid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"timezone" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => "PHP-legal timezone"],
			"language" => ["type" => "varchar(32)", "not null" => "1", "default" => "en", "comment" => "default language"],
			"register_date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "timestamp of registration"],
			"login_date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "timestamp of last login"],
			"last-activity" => ["type" => "date", "comment" => "Day of the last activity"],
			"default-location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Default for item.location"],
			"allow_location" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 allows to display the location"],
			"theme" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "user theme preference"],
			"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
			"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
			"verified" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "user is verified through email"],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 for user is blocked"],
			"blockwall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to post to the profile page of the user"],
			"hidewall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide profile details from unknown viewers"],
			"blocktags" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to tag the post of this user"],
			"notify-flags" => ["type" => "smallint unsigned", "not null" => "1", "default" => "65535", "comment" => "email notification options"],
			"page-flags" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "page/profile type"],
			"account-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"prvnets" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pwdreset" => ["type" => "varchar(255)", "comment" => "Password reset request token"],
			"pwdreset_time" => ["type" => "datetime", "comment" => "Timestamp of the last password reset request"],
			"maxreq" => ["type" => "int unsigned", "not null" => "1", "default" => "10", "comment" => ""],
			"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Delay in days before deleting user-related posts. Scope is controlled by pConfig."],
			"account_removed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if 1 the account is removed"],
			"account_expired" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"account_expires_on" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "timestamp when account expires and will be deleted"],
			"expire_notification_sent" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "timestamp of last warning of account expiration"],
			"def_gid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"allow_cid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
			"openidserver" => ["type" => "text", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uid"],
			"nickname" => ["nickname(32)"],
			"parent-uid" => ["parent-uid"],
			"guid" => ["guid"],
			"email" => ["email(64)"],
		]
	],
	"user-gserver" => [
		"comment" => "User settings about remote servers",
		"fields" => [
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "primary" => "1", "comment" => "Owner User id"],
			"gsid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["gserver" => "id"], "primary" => "1", "comment" => "Gserver id"],
			"ignored" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "server accounts are ignored for the user"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "gsid"],
			"gsid" => ["gsid"]
		],
	],
	"item-uri" => [
		"comment" => "URI and GUID for items",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri" => ["type" => "varbinary(383)", "not null" => "1", "comment" => "URI of an item"],
			"guid" => ["type" => "varbinary(255)", "comment" => "A unique identifier for an item"]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri" => ["UNIQUE", "uri"],
			"guid" => ["guid"]
		]
	],
	"contact" => [
		"comment" => "contact table",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"updated" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Date of last contact update"],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network of the contact"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this contact is known by"],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Nick- and user name of the contact"],
			"location" => ["type" => "varchar(255)", "default" => "", "comment" => ""],
			"about" => ["type" => "text", "comment" => ""],
			"keywords" => ["type" => "text", "comment" => "public keywords (interests) of the contact"],
			"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "XMPP address"],
			"matrix" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Matrix address"],
			"avatar" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"blurhash" => ["type" => "varbinary(255)", "comment" => "BlurHash representation of the avatar"],
			"header" => ["type" => "varbinary(383)", "comment" => "Header picture"],
			"url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"nurl" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the contact url"],
			"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"alias" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
			"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
			"batch" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"notify" => ["type" => "varbinary(383)", "comment" => ""],
			"poll" => ["type" => "varbinary(383)", "comment" => ""],
			"subscribe" => ["type" => "varbinary(383)", "comment" => ""],
			"last-update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last try to update the contact info"],
			"next-update" => ["type" => "datetime", "comment" => "Next connection request"],
			"success_update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last successful contact update"],
			"failure_update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last failed update"],
			"failed" => ["type" => "boolean", "comment" => "Connection failed"],
			"term-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last-item" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "date of the last post"],
			"last-discovery" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "date of the last follower discovery"],
			"local-data" => ["type" => "boolean", "comment" => "Is true when there are posts with this contact on the system"],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "Node-wide block status"],
			"block_reason" => ["type" => "text", "comment" => "Node-wide block reason"],
			"readonly" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "posts of the contact are readonly"],
			"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Person, organisation, news, community, relay"],
			"manually-approve" => ["type" => "boolean", "comment" => "Contact requests have to be approved manually"],
			"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"unsearchable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact prefers to not be searchable"],
			"sensitive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact posts sensitive content"],
			"baseurl" => ["type" => "varbinary(383)", "default" => "", "comment" => "baseurl of the contact from the gserver record, can be missing"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Global Server ID, can be missing"],
			"bd" => ["type" => "date", "not null" => "1", "default" => DBA::NULL_DATE, "comment" => ""],
			// User depending fields
			"reason" => ["type" => "text", "comment" => ""],
			"self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 if the contact is the user him/her self"],
			"remote_self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"rel" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "The kind of the relation between the user and the contact"],
			"protocol" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Protocol of the contact"],
			"hub-verify" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"rating" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Automatically detected feed poll frequency"],
			"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Feed poll priority"],
			"attag" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pending" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "Contact request is pending"],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact has been deleted"],
			"info" => ["type" => "mediumtext", "comment" => ""],
			"notify_new_posts" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"fetch_further_information" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"ffi_keyword_denylist" => ["type" => "text", "comment" => ""],
			// Deprecated, but still in use
			"photo" => ["type" => "varbinary(383)", "default" => "", "comment" => "Link to the profile photo of the contact"],
			"thumb" => ["type" => "varbinary(383)", "default" => "", "comment" => "Link to the profile photo (thumb size)"],
			"micro" => ["type" => "varbinary(383)", "default" => "", "comment" => "Link to the profile photo (micro size)"],
			"name-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"uri-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"avatar-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"request" => ["type" => "varbinary(383)", "comment" => ""],
			"confirm" => ["type" => "varbinary(383)", "comment" => ""],
			"poco" => ["type" => "varbinary(383)", "comment" => ""],
			"writable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"forum" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a group. Deprecated, use 'contact-type' = 'community' and 'manually-approve' = false instead"],
			"prv" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a private group. Deprecated, use 'contact-type' = 'community' and 'manually-approve' = true instead"],
			"bdyear" => ["type" => "varchar(4)", "not null" => "1", "default" => "", "comment" => ""],
			// Deprecated fields that aren't in use anymore
			"site-pubkey" => ["type" => "text", "comment" => "Deprecated"],
			"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Deprecated"],
			"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Deprecated"],
			"issued-id" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "Deprecated"],
			"dfrn-id" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "Deprecated"],
			"aes_allow" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Deprecated"],
			"ret-aes" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Deprecated"],
			"usehub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Deprecated"],
			"closeness" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "99", "comment" => "Deprecated"],
			"profile-id" => ["type" => "int unsigned", "comment" => "Deprecated"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_name" => ["uid", "name(190)"],
			"self_uid" => ["self", "uid"],
			"alias_uid" => ["alias(128)", "uid"],
			"pending_uid" => ["pending", "uid"],
			"blocked_uid" => ["blocked", "uid"],
			"uid_rel_network_poll" => ["uid", "rel", "network", "poll(64)", "archive"],
			"uid_network_batch" => ["uid", "network", "batch(64)"],
			"batch_contact-type" => ["batch(64)", "contact-type"],
			"addr_uid" => ["addr(128)", "uid"],
			"nurl_uid" => ["nurl(128)", "uid"],
			"nick_uid" => ["nick(128)", "uid"],
			"attag_uid" => ["attag(96)", "uid"],
			"network_uid_lastupdate" => ["network", "uid", "last-update"],
			"uid_network_self_lastupdate" => ["uid", "network", "self", "last-update"],
			"next-update" => ["next-update"],
			"local-data-next-update" => ["local-data", "next-update"],
			"uid_lastitem" => ["uid", "last-item"],
			"baseurl" => ["baseurl(64)"],
			"uid_contact-type" => ["uid", "contact-type"],
			"uid_self_contact-type" => ["uid", "self", "contact-type"],
			"self_network_uid" => ["self", "network", "uid"],
			"gsid_uid_failed" => ["gsid", "uid", "failed"],
			"uri-id" => ["uri-id"],
		]
	],
	"tag" => [
		"comment" => "tags and mentions",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"name" => ["type" => "varchar(96)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"type" => ["type" => "tinyint unsigned", "comment" => "Type of the tag (Unknown, General Collection, Follower Collection or Account)"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"type_name_url" => ["UNIQUE", "name", "url"],
			"url" => ["url"]
		]
	],
	"permissionset" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner id of this permission set"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed circles"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied circles"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_allow_cid_allow_gid_deny_cid_deny_gid" => ["uid", "allow_cid(50)", "allow_gid(30)", "deny_cid(50)", "deny_gid(30)"],
		]
	],
	"verb" => [
		"comment" => "Activity Verbs",
		"fields" => [
			"id" => ["type" => "smallint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"name" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"name" => ["name"]
		]
	],
	// Main tables
	"2fa_app_specific_password" => [
		"comment" => "Two-factor app-specific _password",
		"fields" => [
			"id" => ["type" => "mediumint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Password ID for revocation"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "User ID"],
			"description" => ["type" => "varchar(255)", "comment" => "Description of the usage of the password"],
			"hashed_password" => ["type" => "varchar(255)", "not null" => "1", "comment" => "Hashed password"],
			"generated" => ["type" => "datetime", "not null" => "1", "comment" => "Datetime the password was generated"],
			"last_used" => ["type" => "datetime", "comment" => "Datetime the password was last used"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_description" => ["uid", "description(190)"],
		]
	],
	"2fa_recovery_codes" => [
		"comment" => "Two-factor authentication recovery codes",
		"fields" => [
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "User ID"],
			"code" => ["type" => "varchar(50)", "not null" => "1", "primary" => "1", "comment" => "Recovery code string"],
			"generated" => ["type" => "datetime", "not null" => "1", "comment" => "Datetime the code was generated"],
			"used" => ["type" => "datetime", "comment" => "Datetime the code was used"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "code"]
		]
	],
	"2fa_trusted_browser" => [
		"comment" => "Two-factor authentication trusted browsers",
		"fields" => [
			"cookie_hash" => ["type" => "varchar(80)", "not null" => "1", "primary" => "1", "comment" => "Trusted cookie hash"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "User ID"],
			"user_agent" => ["type" => "text", "comment" => "User agent string"],
			"trusted" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "Whenever this browser should be trusted or not"],
			"created" => ["type" => "datetime", "not null" => "1", "comment" => "Datetime the trusted browser was recorded"],
			"last_used" => ["type" => "datetime", "comment" => "Datetime the trusted browser was last used"],
		],
		"indexes" => [
			"PRIMARY" => ["cookie_hash"],
			"uid" => ["uid"],
		]
	],
	"account-suggestion" => [
		"comment" => "Account suggestion",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the account url"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "User ID"],
			"level" => ["type" => "smallint unsigned", "comment" => "level of closeness"],
			"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "If set, this account will not be suggested again"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "uri-id"],
			"uri-id_uid" => ["uri-id", "uid"],
		]
	],
	"account-user" => [
		"comment" => "Remote and local accounts",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the account url"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "User ID"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-id_uid" => ["UNIQUE", "uri-id", "uid"],
			"uid_uri-id" => ["uid", "uri-id"],
		]
	],
	"apcontact" => [
		"comment" => "ActivityPub compatible contacts - used in the ActivityPub implementation",
		"fields" => [
			"url" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "URL of the contact"],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the apcontact url"],
			"uuid" => ["type" => "varbinary(255)", "comment" => ""],
			"type" => ["type" => "varchar(20)", "not null" => "1", "comment" => ""],
			"following" => ["type" => "varbinary(383)", "comment" => ""],
			"followers" => ["type" => "varbinary(383)", "comment" => ""],
			"inbox" => ["type" => "varbinary(383)", "not null" => "1", "comment" => ""],
			"outbox" => ["type" => "varbinary(383)", "comment" => ""],
			"sharedinbox" => ["type" => "varbinary(383)", "comment" => ""],
			"featured" => ["type" => "varbinary(383)", "comment" => "Address for the collection of featured posts"],
			"featured-tags" => ["type" => "varbinary(383)", "comment" => "Address for the collection of featured tags"],
			"manually-approve" => ["type" => "boolean", "comment" => ""],
			"discoverable" => ["type" => "boolean", "comment" => "Mastodon extension: true if profile is published in their directory"],
			"suspended" => ["type" => "boolean", "comment" => "Mastodon extension: true if profile is suspended"],
			"posting-restricted" => ["type" => "boolean", "comment" => "lemmy:postingRestrictedToMods"],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"name" => ["type" => "varchar(255)", "comment" => ""],
			"about" => ["type" => "text", "comment" => ""],
			"xmpp" => ["type" => "varchar(255)", "comment" => "XMPP address"],
			"matrix" => ["type" => "varchar(255)", "comment" => "Matrix address"],
			"photo" => ["type" => "varbinary(383)", "comment" => ""],
			"header" => ["type" => "varbinary(383)", "comment" => "Header picture"],
			"addr" => ["type" => "varchar(255)", "comment" => ""],
			"alias" => ["type" => "varbinary(383)", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => ""],
			"subscribe" => ["type" => "varbinary(383)", "comment" => ""],
			"baseurl" => ["type" => "varbinary(383)", "comment" => "baseurl of the ap contact"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Global Server ID"],
			"generator" => ["type" => "varchar(255)", "comment" => "Name of the contact's system"],
			"following_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of following contacts"],
			"followers_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of followers"],
			"statuses_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of posts"],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["url"],
			"addr" => ["addr(32)"],
			"alias" => ["alias(190)"],
			"followers" => ["followers(190)"],
			"baseurl" => ["baseurl(190)"],
			"sharedinbox" => ["sharedinbox(190)"],
			"gsid" => ["gsid"],
			"uri-id" => ["UNIQUE", "uri-id"],
		]
	],
	"application" => [
		"comment" => "OAuth application",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "generated index"],
			"client_id" => ["type" => "varchar(64)", "not null" => "1", "comment" => ""],
			"client_secret" => ["type" => "varchar(64)", "not null" => "1", "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "comment" => ""],
			"redirect_uri" => ["type" => "varbinary(383)", "not null" => "1", "comment" => ""],
			"website" => ["type" => "varbinary(383)", "comment" => ""],
			"scopes" => ["type" => "varchar(255)", "comment" => ""],
			"read" => ["type" => "boolean", "comment" => "Read scope"],
			"write" => ["type" => "boolean", "comment" => "Write scope"],
			"follow" => ["type" => "boolean", "comment" => "Follow scope"],
			"push" => ["type" => "boolean", "comment" => "Push scope"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"client_id" => ["UNIQUE", "client_id"]
		]
	],
	"application-marker" => [
		"comment" => "Timeline marker",
		"fields" => [
			"application-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["application" => "id"], "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"timeline" => ["type" => "varchar(64)", "not null" => "1", "primary" => "1", "comment" => "Marker (home, notifications)"],
			"last_read_id" => ["type" => "varbinary(383)", "comment" => "Marker id for the timeline"],
			"version" => ["type" => "smallint unsigned", "comment" => "Version number"],
			"updated_at" => ["type" => "datetime", "comment" => "creation time"],
		],
		"indexes" => [
			"PRIMARY" => ["application-id", "uid", "timeline"],
			"uid_id" => ["uid"],
		]
	],
	"application-token" => [
		"comment" => "OAuth user token",
		"fields" => [
			"application-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["application" => "id"], "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"code" => ["type" => "varchar(64)", "not null" => "1", "comment" => ""],
			"access_token" => ["type" => "varchar(64)", "not null" => "1", "comment" => ""],
			"created_at" => ["type" => "datetime", "not null" => "1", "comment" => "creation time"],
			"scopes" => ["type" => "varchar(255)", "comment" => ""],
			"read" => ["type" => "boolean", "comment" => "Read scope"],
			"write" => ["type" => "boolean", "comment" => "Write scope"],
			"follow" => ["type" => "boolean", "comment" => "Follow scope"],
			"push" => ["type" => "boolean", "comment" => "Push scope"],
		],
		"indexes" => [
			"PRIMARY" => ["application-id", "uid"],
			"uid_id" => ["uid", "application-id"],
		]
	],
	"attach" => [
		"comment" => "file attachments",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "generated index"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "hash"],
			"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "filename of original"],
			"filetype" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "mimetype"],
			"filesize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "size in bytes"],
			"data" => ["type" => "longblob", "not null" => "1", "comment" => "file data"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edit time"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed circles"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied circles"],
			"backend-class" => ["type" => "tinytext", "comment" => "Storage backend class"],
			"backend-ref" => ["type" => "text", "comment" => "Storage backend data reference"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"cache" => [
		"comment" => "Stores temporary data",
		"fields" => [
			"k" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "cache key"],
			"v" => ["type" => "mediumtext", "comment" => "cached serialized value"],
			"expires" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of cache expiration"],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of cache insertion"],
		],
		"indexes" => [
			"PRIMARY" => ["k"],
			"k_expires" => ["k", "expires"],
		]
	],
	"channel" => [
		"comment" => "User defined Channels",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"label" => ["type" => "varchar(64)", "not null" => "1", "comment" => "Channel label"],
			"description" => ["type" => "varchar(64)", "comment" => "Channel description"],
			"circle" => ["type" => "int", "comment" => "Circle or channel that this channel is based on"],
			"access-key" => ["type" => "varchar(1)", "comment" => "Access key"],
			"include-tags" => ["type" => "varchar(1023)", "comment" => "Comma separated list of tags that will be included in the channel"],
			"exclude-tags" => ["type" => "varchar(1023)", "comment" => "Comma separated list of tags that aren't allowed in the channel"],
			"min-size" => ["type" => "int unsigned", "comment" => "Minimum post size"],
			"max-size" => ["type" => "int unsigned", "comment" => "Maximum post size"],
			"full-text-search" => ["type" => "varchar(1023)", "comment" => "Full text search pattern, see https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode"],
			"media-type" => ["type" => "smallint unsigned", "comment" => "Filtered media types"],
			"languages" => ["type" => "mediumtext", "comment" => "Desired languages"],
			"publish" => ["type" => "boolean", "comment" => "publish channel content"],
			"valid" => ["type" => "boolean", "comment" => "Set, when the full-text-search is valid"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"config" => [
		"comment" => "main configuration storage",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"cat" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => "The category of the entry"],
			"k" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => "The key of the entry"],
			"v" => ["type" => "mediumtext", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"cat_k" => ["UNIQUE", "cat", "k"],
		]
	],
	"contact-relation" => [
		"comment" => "Contact relations",
		"fields" => [
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "primary" => "1", "comment" => "contact the related contact had interacted with"],
			"relation-cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "primary" => "1", "comment" => "related contact who had interacted with the contact"],
			"last-interaction" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last interaction by relation-cid on cid"],
			"follow-updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last update of the contact relationship"],
			"follows" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if true, relation-cid follows cid"],
			"score" => ["type" => "smallint unsigned", "comment" => "score for interactions of cid on relation-cid"],
			"relation-score" => ["type" => "smallint unsigned", "comment" => "score for interactions of relation-cid on cid"],
			"thread-score" => ["type" => "smallint unsigned", "comment" => "score for interactions of cid on threads of relation-cid"],
			"relation-thread-score" => ["type" => "smallint unsigned", "comment" => "score for interactions of relation-cid on threads of cid"],
			"post-score" => ["type" => "smallint unsigned", "comment" => "score for the amount of posts from cid that can be seen by relation-cid"],
		],
		"indexes" => [
			"PRIMARY" => ["cid", "relation-cid"],
			"relation-cid" => ["relation-cid"],
		]
	],
	"conv" => [
		"comment" => "private messages",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"guid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this conversation"],
			"recips" => ["type" => "text", "comment" => "sender_handle;recipient_handle"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"creator" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "handle of creator"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation timestamp"],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "edited timestamp"],
			"subject" => ["type" => "text", "comment" => "subject of initial message"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"workerqueue" => [
		"comment" => "Background tasks queue entries",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented worker task id"],
			"command" => ["type" => "varchar(100)", "comment" => "Task command"],
			"parameter" => ["type" => "mediumtext", "comment" => "Task parameter"],
			"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Task priority"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation date"],
			"pid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Process id of the worker"],
			"executed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Execution date"],
			"next_try" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Next retrial date"],
			"retrial" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Retrial counter"],
			"done" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marked 1 when the task was done - will be deleted later"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"command" => ["command"],
			"done_command_parameter" => ["done", "command", "parameter(64)"],
			"done_executed" => ["done", "executed"],
			"done_priority_retrial_created" => ["done", "priority", "retrial", "created"],
			"done_priority_next_try" => ["done", "priority", "next_try"],
			"done_pid_next_try" => ["done", "pid", "next_try"],
			"done_pid_retrial" => ["done", "pid", "retrial"],
			"done_pid_priority_created" => ["done", "pid", "priority", "created"]
		]
	],
	"delayed-post" => [
		"comment" => "Posts that are about to be distributed at a later time",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri" => ["type" => "varbinary(383)", "comment" => "URI of the post that will be distributed later"],
			"uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"delayed" => ["type" => "datetime", "comment" => "delay time"],
			"wid" => ["type" => "int unsigned", "foreign" => ["workerqueue" => "id"], "comment" => "Workerqueue id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_uri" => ["UNIQUE", "uid", "uri(190)"],
			"wid" => ["wid"],
		]
	],
	"delivery-queue" => [
		"comment" => "Delivery data for posts for the batch processing",
		"fields" => [
			"gsid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Target server"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Delivered post"],
			"created" => ["type" => "datetime", "comment" => ""],
			"command" => ["type" => "varbinary(32)", "comment" => ""],
			"cid" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Target contact"],
			"uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Delivering user"],
			"failed" => ["type" => "tinyint", "default" => 0, "comment" => "Number of times the delivery has failed"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "gsid"],
			"gsid_created" => ["gsid", "created"],
			"uid" => ["uid"],
			"cid" => ["cid"],
		]
	],
	"diaspora-contact" => [
		"comment" => "Diaspora compatible contacts - used in the Diaspora implementation",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the contact URL"],
			"addr" => ["type" => "varchar(255)", "comment" => ""],
			"alias" => ["type" => "varchar(255)", "comment" => ""],
			"nick" => ["type" => "varchar(255)", "comment" => ""],
			"name" => ["type" => "varchar(255)", "comment" => ""],
			"given-name" => ["type" => "varchar(255)", "comment" => ""],
			"family-name" => ["type" => "varchar(255)", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "comment" => ""],
			"photo-medium" => ["type" => "varchar(255)", "comment" => ""],
			"photo-small" => ["type" => "varchar(255)", "comment" => ""],
			"batch" => ["type" => "varchar(255)", "comment" => ""],
			"notify" => ["type" => "varchar(255)", "comment" => ""],
			"poll" => ["type" => "varchar(255)", "comment" => ""],
			"subscribe" => ["type" => "varchar(255)", "comment" => ""],
			"searchable" => ["type" => "boolean", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => ""],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Global Server ID"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"interacting_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of contacts this contact interacts with"],
			"interacted_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of contacts that interacted with this contact"],
			"post_count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of posts and comments"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"addr" => ["UNIQUE", "addr"],
			"alias" => ["alias"],
			"gsid" => ["gsid"],
		]
	],
	"diaspora-interaction" => [
		"comment" => "Signed Diaspora Interaction",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"interaction" => ["type" => "mediumtext", "comment" => "The Diaspora interaction"]
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"]
		]
	],
	"endpoint" => [
		"comment" => "ActivityPub endpoints - used in the ActivityPub implementation",
		"fields" => [
			"url" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "URL of the contact"],
			"type" => ["type" => "varchar(20)", "not null" => "1", "comment" => ""],
			"owner-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the apcontact url"],
		],
		"indexes" => [
			"PRIMARY" => ["url"],
			"owner-uri-id_type" => ["UNIQUE", "owner-uri-id", "type"],
		]
	],
	"event" => [
		"comment" => "Events",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"guid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "contact_id (ID of the contact in contact table)"],
			"uri" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the event uri"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edit time"],
			"start" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "event start time"],
			"finish" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "event end time"],
			"summary" => ["type" => "text", "comment" => "short description or title of the event"],
			"desc" => ["type" => "text", "comment" => "event description"],
			"location" => ["type" => "text", "comment" => "event location"],
			"type" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => "event or birthday"],
			"nofinish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if event does have no end this is 1"],
			"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "0 or 1"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed circles"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied circles"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_start" => ["uid", "start"],
			"cid" => ["cid"],
			"uri-id" => ["uri-id"],
		]
	],
	"fetch-entry" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"url" => ["type" => "varbinary(383)", "comment" => "url that awaiting to be fetched"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation date of the fetch request"],
			"wid" => ["type" => "int unsigned", "foreign" => ["workerqueue" => "id"], "comment" => "Workerqueue id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"url" => ["UNIQUE", "url"],
			"created" => ["created"],
			"wid" => ["wid"],
		]
	],
	"fsuggest" => [
		"comment" => "friend suggestion stuff",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"request" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"note" => ["type" => "text", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"cid" => ["cid"],
			"uid" => ["uid"],
		]
	],
	"group" => [
		"comment" => "privacy circles, circle info",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the member list is not private"],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the circle has been deleted"],
			"cid" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Contact id of group. When this field is filled then the members are synced automatically."],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "human readable name of circle"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
			"cid" => ["cid"],
		]
	],
	"group_member" => [
		"comment" => "privacy circles, member info",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"gid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["group" => "id"], "comment" => "group.id of the associated circle"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "contact.id of the member assigned to the associated circle"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"contactid" => ["contact-id"],
			"gid_contactid" => ["UNIQUE", "gid", "contact-id"],
		]
	],
	"gserver-tag" => [
		"comment" => "Tags that the server has subscribed",
		"fields" => [
			"gserver-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["gserver" => "id"], "primary" => "1", "comment" => "The id of the gserver"],
			"tag" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "primary" => "1", "comment" => "Tag that the server has subscribed"],
		],
		"indexes" => [
			"PRIMARY" => ["gserver-id", "tag"],
			"tag" => ["tag"],
		]
	],
	"hook" => [
		"comment" => "addon hook registry",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"hook" => ["type" => "varbinary(100)", "not null" => "1", "default" => "", "comment" => "name of hook"],
			"file" => ["type" => "varbinary(200)", "not null" => "1", "default" => "", "comment" => "relative filename of hook handler"],
			"function" => ["type" => "varbinary(200)", "not null" => "1", "default" => "", "comment" => "function name of hook handler"],
			"priority" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => "not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"priority" => ["priority"],
			"hook_file_function" => ["UNIQUE", "hook", "file", "function"],
		]
	],
	"inbox-entry" => [
		"comment" => "Incoming activity",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"activity-id" => ["type" => "varbinary(383)", "comment" => "id of the incoming activity"],
			"object-id" => ["type" => "varbinary(383)", "comment" => ""],
			"in-reply-to-id" => ["type" => "varbinary(383)", "comment" => ""],
			"context" => ["type" => "varbinary(383)", "comment" => ""],
			"conversation" => ["type" => "varbinary(383)", "comment" => ""],
			"type" => ["type" => "varchar(64)", "comment" => "Type of the activity"],
			"object-type" => ["type" => "varchar(64)", "comment" => "Type of the object activity"],
			"object-object-type" => ["type" => "varchar(64)", "comment" => "Type of the object's object activity"],
			"received" => ["type" => "datetime", "comment" => "Receiving date"],
			"activity" => ["type" => "mediumtext", "comment" => "The JSON activity"],
			"signer" => ["type" => "varchar(255)", "comment" => ""],
			"push" => ["type" => "boolean", "comment" => "Is the entry pushed or have pulled it?"],
			"trust" => ["type" => "boolean", "comment" => "Do we trust this entry?"],
			"wid" => ["type" => "int unsigned", "foreign" => ["workerqueue" => "id"], "comment" => "Workerqueue id"],
			"retrial" => ["type" => "tinyint unsigned", "default" => "0", "comment" => "Retrial counter"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"activity-id" => ["UNIQUE", "activity-id"],
			"object-id" => ["object-id"],
			"received" => ["received"],
			"wid" => ["wid"],
		]
	],
	"inbox-entry-receiver" => [
		"comment" => "Receiver for the incoming activity",
		"fields" => [
			"queue-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["inbox-entry" => "id"], "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["queue-id", "uid"],
			"uid" => ["uid"],
		]
	],
	"inbox-status" => [
		"comment" => "Status of ActivityPub inboxes",
		"fields" => [
			"url" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "URL of the inbox"],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of inbox url"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "ID of the related server"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation date of this entry"],
			"success" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last successful delivery"],
			"failure" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last failed delivery"],
			"previous" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Previous delivery date"],
			"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is the inbox archived?"],
			"shared" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is it a shared inbox?"]
		],
		"indexes" => [
			"PRIMARY" => ["url"],
			"uri-id" => ["uri-id"],
			"gsid" => ["gsid"],
		]
	],
	"intro" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"fid" => ["type" => "int unsigned", "comment" => "deprecated"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => ""],
			"suggest-cid" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Suggested contact"],
			"knowyou" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "deprecated"],
			"note" => ["type" => "text", "comment" => ""],
			"hash" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
			"datetime" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "deprecated"],
			"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"contact-id" => ["contact-id"],
			"suggest-cid" => ["suggest-cid"],
			"uid" => ["uid"],
		]
	],
	"key-value" => [
		"comment" => "A key value storage",
		"fields" => [
			"k" => ["type" => "varbinary(50)", "not null" => "1", "primary" => "1", "comment" => ""],
			"v" => ["type" => "mediumtext", "comment" => ""],
			"updated_at" => ["type" => "int unsigned", "not null" => "1", "comment" => "timestamp of the last update"],
		],
		"indexes" => [
			"PRIMARY" => ["k"],
		],
	],
	"locks" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"name" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""],
			"locked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Process ID"],
			"expires" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of cache expiration"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"name_expires" => ["name", "expires"]
		]
	],
	"mail" => [
		"comment" => "private messages",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"guid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this private message"],
			"from-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "name of the sender"],
			"from-photo" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "contact photo link of the sender"],
			"from-url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "profile link of the sender"],
			"contact-id" => ["type" => "varbinary(255)", "relation" => ["contact" => "id"], "comment" => "contact.id"],
			"author-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the author of the mail"],
			"convid" => ["type" => "int unsigned", "relation" => ["conv" => "id"], "comment" => "conv.id"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"body" => ["type" => "mediumtext", "comment" => ""],
			"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if message visited it is 1"],
			"reply" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"replied" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"unknown" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if sender not in the contact table this is 1"],
			"uri" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the related mail"],
			"parent-uri" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related mail"],
			"thr-parent" => ["type" => "varbinary(383)", "comment" => ""],
			"thr-parent-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the thread parent uri"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time of the private message"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_seen" => ["uid", "seen"],
			"convid" => ["convid"],
			"uri" => ["uri(64)"],
			"parent-uri" => ["parent-uri(64)"],
			"contactid" => ["contact-id(32)"],
			"author-id" => ["author-id"],
			"uri-id" => ["uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
			"thr-parent-id" => ["thr-parent-id"],
		]
	],
	"mailacct" => [
		"comment" => "Mail account data for fetching mails",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"server" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"port" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"ssltype" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
			"mailbox" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"user" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"pass" => ["type" => "text", "comment" => ""],
			"reply_to" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"action" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"movetofolder" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"last_check" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"manage" => [
		"comment" => "table of accounts that can manage each other",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"mid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_mid" => ["UNIQUE", "uid", "mid"],
			"mid" => ["mid"],
		]
	],
	"notification" => [
		"comment" => "notifications",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"vid" => ["type" => "smallint unsigned", "foreign" => ["verb" => "id", "on delete" => "restrict"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"type" => ["type" => "smallint unsigned", "comment" => ""],
			"actor-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Link to the contact table with uid=0 of the actor that caused the notification"],
			"target-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the related post"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related post"],
			"created" => ["type" => "datetime", "comment" => ""],
			"seen" => ["type" => "boolean", "default" => "0", "comment" => "Seen on the desktop"],
			"dismissed" => ["type" => "boolean", "default" => "0", "comment" => "Dismissed via the API"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_vid_type_actor-id_target-uri-id" => ["UNIQUE", "uid", "vid", "type", "actor-id", "target-uri-id"],
			"vid" => ["vid"],
			"actor-id" => ["actor-id"],
			"target-uri-id" => ["target-uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
			"seen_uid" => ["seen", "uid"],
			"uid_type_parent-uri-id_actor-id" => ["uid", "type", "parent-uri-id", "actor-id"],
		]
	],
	"notify" => [
		"comment" => "[Deprecated] User notifications",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"type" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"msg" => ["type" => "mediumtext", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"link" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"iid" => ["type" => "int unsigned", "comment" => ""],
			"parent" => ["type" => "int unsigned", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the related post"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related post"],
			"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"verb" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => ""],
			"otype" => ["type" => "varchar(10)", "not null" => "1", "default" => "", "comment" => ""],
			"name_cache" => ["type" => "tinytext", "comment" => "Cached bbcode parsing of name"],
			"msg_cache" => ["type" => "mediumtext", "comment" => "Cached bbcode parsing of msg"]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"seen_uid_date" => ["seen", "uid", "date"],
			"uid_date" => ["uid", "date"],
			"uid_type_link" => ["uid", "type", "link(190)"],
			"uri-id" => ["uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
		]
	],
	"notify-threads" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"notify-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["notify" => "id"], "comment" => ""],
			"master-parent-item" => ["type" => "int unsigned", "comment" => "Deprecated"],
			"master-parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related post"],
			"parent-item" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"receiver-uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"master-parent-uri-id" => ["master-parent-uri-id"],
			"receiver-uid" => ["receiver-uid"],
			"notify-id" => ["notify-id"],
		]
	],
	"oembed" => [
		"comment" => "cache for OEmbed queries",
		"fields" => [
			"url" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "page url"],
			"maxwidth" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "comment" => "Maximum width passed to Oembed"],
			"content" => ["type" => "mediumtext", "comment" => "OEmbed data of the page"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of creation"],
		],
		"indexes" => [
			"PRIMARY" => ["url", "maxwidth"],
			"created" => ["created"],
		]
	],
	"openwebauth-token" => [
		"comment" => "Store OpenWebAuth token to verify contacts",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id - currently unused"],
			"type" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Verify type"],
			"token" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A generated token"],
			"meta" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of creation"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"parsed_url" => [
		"comment" => "cache for 'parse_url' queries",
		"fields" => [
			"url_hash" => ["type" => "binary(64)", "not null" => "1", "primary" => "1", "comment" => "page url hash"],
			"guessing" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the 'guessing' mode active?"],
			"oembed" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the data the result of oembed?"],
			"url" => ["type" => "text", "not null" => "1", "comment" => "page url"],
			"content" => ["type" => "mediumtext", "comment" => "page data"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of creation"],
			"expires" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of expiration"],
		],
		"indexes" => [
			"PRIMARY" => ["url_hash", "guessing", "oembed"],
			"created" => ["created"],
			"expires" => ["expires"],
		]
	],
	"pconfig" => [
		"comment" => "personal (per user) configuration storage",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Primary key"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"cat" => ["type" => "varchar(50)", "not null" => "1", "default" => "", "comment" => "Category"],
			"k" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "Key"],
			"v" => ["type" => "mediumtext", "comment" => "Value"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_cat_k" => ["UNIQUE", "uid", "cat", "k"],
		]
	],
	"photo" => [
		"comment" => "photo storage",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid", "on delete" => "restrict"], "comment" => "Owner User id"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "contact.id"],
			"guid" => ["type" => "char(16)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this photo"],
			"resource-id" => ["type" => "char(32)", "not null" => "1", "default" => "", "comment" => ""],
			"hash" => ["type" => "char(32)", "comment" => "hash value of the photo"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation date"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edited date"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"desc" => ["type" => "text", "comment" => ""],
			"album" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "The name of the album to which the photo belongs"],
			"photo-type" => ["type" => "tinyint unsigned", "comment" => "User avatar, user banner, contact avatar, contact banner or default"],
			"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"type" => ["type" => "varchar(30)", "not null" => "1", "default" => "image/jpeg"],
			"height" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"width" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"datasize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"blurhash" => ["type" => "varbinary(255)", "comment" => "BlurHash representation of the photo"],
			"data" => ["type" => "mediumblob", "not null" => "1", "comment" => ""],
			"scale" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"profile" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed circles"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied circles"],
			"accessible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Make photo publicly accessible, ignoring permissions"],
			"backend-class" => ["type" => "tinytext", "comment" => "Storage backend class"],
			"backend-ref" => ["type" => "text", "comment" => "Storage backend data reference"],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"contactid" => ["contact-id"],
			"uid_contactid" => ["uid", "contact-id"],
			"uid_profile" => ["uid", "profile"],
			"uid_album_scale_created" => ["uid", "album(32)", "scale", "created"],
			"uid_album_resource-id_created" => ["uid", "album(32)", "resource-id", "created"],
			"resource-id" => ["resource-id"],
			"uid_photo-type" => ["uid", "photo-type"],
		]
	],
	"post" => [
		"comment" => "Structure for all posts",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the parent uri"],
			"thr-parent-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the thread parent uri"],
			"external-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the external uri"],
			"replies-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the endpoint for the replies collection"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation timestamp."],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last edit (default is created)"],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime"],
			"gravity" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network from where the item comes from"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the owner of this item"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the author of this item"],
			"causer-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the contact that caused the item creation"],
			"post-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Post type (personal note, image, article, ...)"],
			"vid" => ["type" => "smallint unsigned", "foreign" => ["verb" => "id", "on delete" => "restrict"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"private" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "0=public, 1=private, 2=unlisted"],
			"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been marked for deletion"]
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
			"thr-parent-id" => ["thr-parent-id"],
			"external-id" => ["external-id"],
			"replies-id" => ["replies-id"],
			"owner-id" => ["owner-id"],
			"author-id" => ["author-id"],
			"causer-id" => ["causer-id"],
			"vid" => ["vid"],
		]
	],
	"post-activity" => [
		"comment" => "Original remote activity",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"activity" => ["type" => "mediumtext", "comment" => "Original activity"],
			"received" => ["type" => "datetime", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
		]
	],
	"post-category" => [
		"comment" => "post relation to categories",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""],
			"tid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["tag" => "id", "on delete" => "restrict"], "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "uid", "type", "tid"],
			"tid" => ["tid"],
			"uid_uri-id" => ["uid", "uri-id"],
		]
	],
	"post-counts" => [
		"comment" => "Original remote activity",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"vid" => ["type" => "smallint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["verb" => "id", "on delete" => "restrict"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"reaction" => ["type" => "varchar(4)", "not null" => "1", "primary" => "1", "comment" => "Emoji Reaction"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the parent uri"],
			"count" => ["type" => "int unsigned", "default" => 0, "comment" => "Number of activities"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "vid", "reaction"],
			"vid" => ["vid"],
			"parent-uri-id" => ["parent-uri-id"],
		]
	],
	"post-collection" => [
		"comment" => "Collection of posts",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "0 - Featured"],
			"author-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Author of the featured post"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "type"],
			"type" => ["type"],
			"author-id" => ["author-id"],
		]
	],
	"post-content" => [
		"comment" => "Content for all posts",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "item title"],
			"content-warning" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"body" => ["type" => "mediumtext", "comment" => "item body content"],
			"raw-body" => ["type" => "mediumtext", "comment" => "Body without embedded media links"],
			"quote-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the quoted uri"],
			"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "text location where this item originated"],
			"coord" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "longitude/latitude pair representing location where this item originated"],
			"language" => ["type" => "text", "comment" => "Language information about this post"],
			"sensitive" => ["type" => "boolean", "comment" => "If true, this post contains sensitive content"],
			"app" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "application which generated this item"],
			"rendered-hash" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
			"rendered-html" => ["type" => "mediumtext", "comment" => "item.body converted to html"],
			"object-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams object type"],
			"object" => ["type" => "text", "comment" => "JSON encoded object structure unless it is an implied object (normal post)"],
			"target-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams target type if applicable (URI)"],
			"target" => ["type" => "text", "comment" => "JSON encoded target structure if used"],
			"resource-id" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type"],
			"plink" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "permalink or URL to a displayable copy of the message at its source"]
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"plink" => ["plink(191)"],
			"resource-id" => ["resource-id"],
			"quote-uri-id" => ["quote-uri-id"],
		]
	],
	"post-delivery" => [
		"comment" => "Delivery data for posts for the batch processing",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"inbox-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Item-uri id of inbox url"],
			"uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Delivering user"],
			"created" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"command" => ["type" => "varbinary(32)", "comment" => ""],
			"failed" => ["type" => "tinyint", "default" => 0, "comment" => "Number of times the delivery has failed"],
			"receivers" => ["type" => "mediumtext", "comment" => "JSON encoded array with the receiving contacts"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "inbox-id"],
			"inbox-id_created" => ["inbox-id", "created"],
			"uid" => ["uid"],
		]
	],
	"post-delivery-data" => [
		"comment" => "Delivery data for items",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"postopts" => ["type" => "text", "comment" => "External post connectors add their network name to this comma-separated string to identify that they should be delivered to these networks during delivery"],
			"inform" => ["type" => "mediumtext", "comment" => "Additional receivers of the linked item"],
			"queue_count" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Initial number of delivery recipients, used as item.delivery_queue_count"],
			"queue_done" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries, used as item.delivery_queue_done"],
			"queue_failed" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of unsuccessful deliveries, used as item.delivery_queue_failed"],
			"activitypub" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries via ActivityPub"],
			"dfrn" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries via DFRN"],
			"legacy_dfrn" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries via legacy DFRN"],
			"diaspora" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries via Diaspora"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
		]
	],
	"post-engagement" => [
		"comment" => "Engagement data per post",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "Item owner"],
			"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Person, organisation, news, community, relay"],
			"media-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Type of media in a bit array (1 = image, 2 = video, 4 = audio)"],
			"language" => ["type" => "char(2)", "comment" => "Language information about this post in the ISO 639-1 format"],
			"searchtext" => ["type" => "mediumtext", "comment" => "Simplified text for the full text search"],
			"size" => ["type" => "int unsigned", "comment" => "Body size"],
			"created" => ["type" => "datetime", "comment" => ""],
			"network" => ["type" => "char(4)", "comment" => ""],
			"restricted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "If true, this post is either unlisted or not from a federated network"],
			"comments" => ["type" => "mediumint unsigned", "comment" => "Number of comments"],
			"activities" => ["type" => "mediumint unsigned", "comment" => "Number of activities (like, dislike, ...)"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"owner-id" => ["owner-id"],
			"created" => ["created"],
			"searchtext" => ["FULLTEXT", "searchtext"],
		]
	],
	"post-history" => [
		"comment" => "Post history",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "primary" => "1", "comment" => "Date of edit"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "item title"],
			"content-warning" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"body" => ["type" => "mediumtext", "comment" => "item body content"],
			"raw-body" => ["type" => "mediumtext", "comment" => "Body without embedded media links"],
			"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "text location where this item originated"],
			"coord" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "longitude/latitude pair representing location where this item originated"],
			"language" => ["type" => "text", "comment" => "Language information about this post"],
			"app" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "application which generated this item"],
			"rendered-hash" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
			"rendered-html" => ["type" => "mediumtext", "comment" => "item.body converted to html"],
			"object-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams object type"],
			"object" => ["type" => "text", "comment" => "JSON encoded object structure unless it is an implied object (normal post)"],
			"target-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams target type if applicable (URI)"],
			"target" => ["type" => "text", "comment" => "JSON encoded target structure if used"],
			"resource-id" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type"],
			"plink" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => "permalink or URL to a displayable copy of the message at its source"]
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "edited"],
		]
	],
	"post-link" => [
		"comment" => "Post related external links",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"url" => ["type" => "varbinary(511)", "not null" => "1", "comment" => "External URL"],
			"mimetype" => ["type" => "varchar(60)", "comment" => ""],
			"height" => ["type" => "smallint unsigned", "comment" => "Height of the media"],
			"width" => ["type" => "smallint unsigned", "comment" => "Width of the media"],
			"blurhash" => ["type" => "varbinary(255)", "comment" => "BlurHash representation of the link"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-id-url" => ["UNIQUE", "uri-id", "url"],
		]
	],
	"post-media" => [
		"comment" => "Attached media",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"url" => ["type" => "varbinary(1024)", "not null" => "1", "comment" => "Media URL"],
			"media-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the activities uri-id"],
			"attach-id" => ["type" => "int unsigned", "foreign" => ["attach" => "id"], "comment" => "In case of a local attachment, this field is filled with the id in the attach table"],
			"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Media type"],
			"mimetype" => ["type" => "varchar(60)", "comment" => ""],
			"height" => ["type" => "smallint unsigned", "comment" => "Height of the media"],
			"width" => ["type" => "smallint unsigned", "comment" => "Width of the media"],
			"size" => ["type" => "bigint unsigned", "comment" => "Media size"],
			"blurhash" => ["type" => "varbinary(255)", "comment" => "BlurHash representation of the image"],
			"preview" => ["type" => "varbinary(512)", "comment" => "Preview URL"],
			"preview-height" => ["type" => "smallint unsigned", "comment" => "Height of the preview picture"],
			"preview-width" => ["type" => "smallint unsigned", "comment" => "Width of the preview picture"],
			"description" => ["type" => "text", "comment" => ""],
			"name" => ["type" => "varchar(255)", "comment" => "Name of the media"],
			"author-url" => ["type" => "varbinary(383)", "comment" => "URL of the author of the media"],
			"author-name" => ["type" => "varchar(255)", "comment" => "Name of the author of the media"],
			"author-image" => ["type" => "varbinary(383)", "comment" => "Image of the author of the media"],
			"publisher-url" => ["type" => "varbinary(383)", "comment" => "URL of the publisher of the media"],
			"publisher-name" => ["type" => "varchar(255)", "comment" => "Name of the publisher of the media"],
			"publisher-image" => ["type" => "varbinary(383)", "comment" => "Image of the publisher of the media"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-id-url" => ["UNIQUE", "uri-id", "url(512)"],
			"uri-id-id" => ["uri-id", "id"],
			"media-uri-id" => ["media-uri-id"],
			"attach-id" => ["attach-id"],
		]
	],
	"post-origin" => [
		"comment" => "Posts from local users",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the parent uri"],
			"thr-parent-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the thread parent uri"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation timestamp."],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime"],
			"gravity" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"vid" => ["type" => "smallint unsigned", "foreign" => ["verb" => "id", "on delete" => "restrict"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"private" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "0=public, 1=private, 2=unlisted"],
			"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "This item was posted to the wall of uid"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_uri-id" => ["UNIQUE", "uid", "uri-id"],
			"uri-id" => ["uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
			"thr-parent-id" => ["thr-parent-id"],
			"vid" => ["vid"],
			"parent-uri-id_uid" => ["parent-uri-id", "uid"],
			"uid_wall_received" => ["uid", "wall", "received"],
		],
	],
	"post-question" => [
		"comment" => "Question",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"multiple" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Multiple choice"],
			"voters" => ["type" => "int unsigned", "comment" => "Number of voters for this question"],
			"end-time" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Question end time"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-id" => ["UNIQUE", "uri-id"],
		]
	],
	"post-question-option" => [
		"comment" => "Question option",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => "Id of the question"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"name" => ["type" => "varchar(255)", "comment" => "Name of the option"],
			"replies" => ["type" => "int unsigned", "comment" => "Number of replies for this question option"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "id"],
		]
	],
	"post-searchindex" => [
		"comment" => "Content for all posts",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "Item owner"],
			"media-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Type of media in a bit array (1 = image, 2 = video, 4 = audio)"],
			"language" => ["type" => "char(2)", "comment" => "Language information about this post in the ISO 639-1 format"],
			"searchtext" => ["type" => "mediumtext", "comment" => "Simplified text for the full text search"],
			"size" => ["type" => "int unsigned", "comment" => "Body size"],
			"created" => ["type" => "datetime", "comment" => ""],
			"restricted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "If true, this post is either unlisted or not from a federated network"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"owner-id" => ["owner-id"],
			"created" => ["created"],
			"searchtext" => ["FULLTEXT", "searchtext"],
		]
	],
	"post-tag" => [
		"comment" => "post relation to tags",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""],
			"tid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["tag" => "id", "on delete" => "restrict"], "comment" => ""],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Contact id of the mentioned public contact"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "type", "tid", "cid"],
			"tid" => ["tid"],
			"cid" => ["cid"]
		]
	],
	"post-thread" => [
		"comment" => "Thread related data",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"context-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the endpoint for the context collection"],
			"conversation-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the conversation uri"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Item owner"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Item author"],
			"causer-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the contact that caused the item creation"],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"changed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date that something in the conversation changed, indicating clients should fetch the conversation again"],
			"commented" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
			"context-id" => ["context-id"],
			"conversation-id" => ["conversation-id"],
			"owner-id" => ["owner-id"],
			"author-id" => ["author-id"],
			"causer-id" => ["causer-id"],
			"received" => ["received"],
			"commented" => ["commented"],
		]
	],
	"post-user" => [
		"comment" => "User specific post data",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the parent uri"],
			"thr-parent-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the thread parent uri"],
			"external-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the external uri"],
			"replies-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the endpoint for the replies collection"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation timestamp."],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last edit (default is created)"],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime"],
			"gravity" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network from where the item comes from"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the owner of this item"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the author of this item"],
			"causer-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the contact that caused the item creation"],
			"post-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Post type (personal note, image, article, ...)"],
			"post-reason" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Reason why the post arrived at the user"],
			"vid" => ["type" => "smallint unsigned", "foreign" => ["verb" => "id", "on delete" => "restrict"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"private" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "0=public, 1=private, 2=unlisted"],
			"restrictions" => ["type" => "tinyint unsigned", "comment" => "Bit array of post restrictions (1 = Reply, 2 = Like, 4 = Announce)"],
			"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been marked for deletion"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
			"protocol" => ["type" => "tinyint unsigned", "comment" => "Protocol used to deliver the item for this user"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "contact.id"],
			"event-id" => ["type" => "int unsigned", "foreign" => ["event" => "id"], "comment" => "Used to link to the event.id"],
			"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "post has not been seen"],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marker to hide the post from the user"],
			"notification-type" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "This item was posted to the wall of uid"],
			"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item originated at this site"],
			"psid" => ["type" => "int unsigned", "foreign" => ["permissionset" => "id", "on delete" => "restrict"], "comment" => "ID of the permission set of this post"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_uri-id" => ["UNIQUE", "uid", "uri-id"],
			"uri-id_origin_deleted" => ["uri-id", "origin", "deleted"],
			"parent-uri-id" => ["parent-uri-id"],
			"thr-parent-id" => ["thr-parent-id"],
			"external-id" => ["external-id"],
			"replies-id" => ["replies-id"],
			"owner-id" => ["owner-id"],
			"author-id" => ["author-id"],
			"causer-id" => ["causer-id"],
			"vid" => ["vid"],
			"contact-id" => ["contact-id"],
			"event-id" => ["event-id"],
			"psid" => ["psid"],
			"author-id_uid" => ["author-id", "uid"],
			"author-id_created" => ["author-id", "created"],
			"owner-id_created" => ["owner-id", "created"],
			"parent-uri-id_uid" => ["parent-uri-id", "uid"],
			"uid_wall_received" => ["uid", "wall", "received"],
			"uid_contactid" => ["uid", "contact-id"],
			"uid_unseen_contactid" => ["uid", "unseen", "contact-id"],
			"uid_unseen" => ["uid", "unseen"],
			"uid_hidden_uri-id" => ["uid", "hidden", "uri-id"],
		],
	],
	"post-thread-user" => [
		"comment" => "Thread related data per user",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"context-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the endpoint for the context collection"],
			"conversation-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the conversation uri"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Item owner"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Item author"],
			"causer-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id", "on delete" => "restrict"], "comment" => "Link to the contact table with uid=0 of the contact that caused the item creation"],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"changed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date that something in the conversation changed, indicating clients should fetch the conversation again"],
			"commented" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
			"pinned" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "deprecated"],
			"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"ignored" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Ignore updates for this thread"],
			"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "This item was posted to the wall of uid"],
			"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"forum_mode" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Deprecated"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "foreign" => ["contact" => "id"], "comment" => "contact.id"],
			"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "post has not been seen"],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marker to hide the post from the user"],
			"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item originated at this site"],
			"psid" => ["type" => "int unsigned", "foreign" => ["permissionset" => "id", "on delete" => "restrict"], "comment" => "ID of the permission set of this post"],
			"post-user-id" => ["type" => "int unsigned", "foreign" => ["post-user" => "id"], "comment" => "Id of the post-user table"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "uri-id"],
			"uri-id" => ["uri-id"],
			"context-id" => ["context-id"],
			"conversation-id" => ["conversation-id"],
			"owner-id" => ["owner-id"],
			"author-id" => ["author-id"],
			"causer-id" => ["causer-id"],
			"uid" => ["uid"],
			"contact-id" => ["contact-id"],
			"psid" => ["psid"],
			"post-user-id" => ["post-user-id"],
			"commented" => ["commented"],
			"received" => ["received"],
			"author-id_created" => ["author-id", "created"],
			"owner-id_created" => ["owner-id", "created"],
			"uid_received" => ["uid", "received"],
			"uid_wall_received" => ["uid", "wall", "received"],
			"uid_commented" => ["uid", "commented"],
			"uid_created" => ["uid", "created"],
			"uid_starred" => ["uid", "starred"],
			"uid_mention" => ["uid", "mention"],
			"contact-id_commented" => ["contact-id", "commented"],
			"contact-id_received" => ["contact-id", "received"],
			"contact-id_created" => ["contact-id", "created"],
		]
	],
	"post-user-notification" => [
		"comment" => "User post notifications",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
			"notification-type" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "uri-id"],
			"uri-id" => ["uri-id"],
		],
	],
	"process" => [
		"comment" => "Currently running system processes",
		"fields" => [
			"pid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => "The ID of the process"],
			"hostname" => ["type" => "varchar(255)", "not null" => "1", "primary" => "1", "comment" => "The name of the host the process is ran on"],
			"command" => ["type" => "varbinary(32)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["pid", "hostname"],
			"command" => ["command"],
		]
	],
	"profile" => [
		"comment" => "user profiles data",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"profile-name" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"is-default" => ["type" => "boolean", "comment" => "Deprecated"],
			"hide-friends" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide friend list from viewers of this profile"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Unused in favor of user.username"],
			"pdesc" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"dob" => ["type" => "varchar(32)", "not null" => "1", "default" => "0000-00-00", "comment" => "Day of birth"],
			"address" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"locality" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"region" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"postal-code" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
			"country-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"hometown" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"gender" => ["type" => "varchar(32)", "comment" => "Deprecated"],
			"marital" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"with" => ["type" => "text", "comment" => "Deprecated"],
			"howlong" => ["type" => "datetime", "comment" => "Deprecated"],
			"sexual" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"politic" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"religion" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"pub_keywords" => ["type" => "text", "comment" => ""],
			"prv_keywords" => ["type" => "text", "comment" => ""],
			"likes" => ["type" => "text", "comment" => "Deprecated"],
			"dislikes" => ["type" => "text", "comment" => "Deprecated"],
			"about" => ["type" => "text", "comment" => "Profile description"],
			"summary" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"music" => ["type" => "text", "comment" => "Deprecated"],
			"book" => ["type" => "text", "comment" => "Deprecated"],
			"tv" => ["type" => "text", "comment" => "Deprecated"],
			"film" => ["type" => "text", "comment" => "Deprecated"],
			"interest" => ["type" => "text", "comment" => "Deprecated"],
			"romance" => ["type" => "text", "comment" => "Deprecated"],
			"work" => ["type" => "text", "comment" => "Deprecated"],
			"education" => ["type" => "text", "comment" => "Deprecated"],
			"contact" => ["type" => "text", "comment" => "Deprecated"],
			"homepage" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"homepage_verified" => ["type" => "boolean", "not null" => 1, "default" => "0", "comment" => "was the homepage verified by a rel-me link back to the profile"],
			"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "XMPP address"],
			"matrix" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Matrix address"],
			"photo" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"thumb" => ["type" => "varbinary(383)", "not null" => "1", "default" => "", "comment" => ""],
			"publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish default profile in local directory"],
			"net-publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish profile in global directory"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_is-default" => ["uid", "is-default"],
		]
	],
	"profile_field" => [
		"comment" => "Custom profile fields",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "Owner user id"],
			"order" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "1", "comment" => "Field ordering per user"],
			"psid" => ["type" => "int unsigned", "foreign" => ["permissionset" => "id", "on delete" => "restrict"], "comment" => "ID of the permission set of this profile field - 0 = public"],
			"label" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Label of the field"],
			"value" => ["type" => "text", "comment" => "Value of the field"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edit time"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
			"order" => ["order"],
			"psid" => ["psid"],
		]
	],
	"register" => [
		"comment" => "registrations requiring admin approval",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"hash" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"language" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
			"note" => ["type" => "text", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"report" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Reporting user"],
			"reporter-id" => ["type" => "int unsigned", "foreign" => ["contact" => "id"], "comment" => "Reporting contact"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["contact" => "id"], "comment" => "Reported contact"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id"], "comment" => "Reported contact server"],
			"comment" => ["type" => "text", "comment" => "Report"],
			"category-id" => ["type" => "int unsigned", "not null" => 1, "default" => \Friendica\Moderation\Entity\Report::CATEGORY_OTHER, "comment" => "Report category, one of Entity Report::CATEGORY_*"],
			"forward" => ["type" => "boolean", "comment" => "Forward the report to the remote server"],
			"public-remarks" => ["type" => "text", "comment" => "Remarks shared with the reporter"],
			"private-remarks" => ["type" => "text", "comment" => "Remarks shared with the moderation team"],
			"last-editor-uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Last editor user"],
			"assigned-uid" => ["type" => "mediumint unsigned", "foreign" => ["user" => "uid"], "comment" => "Assigned moderator user"],
			"status" => ["type" => "tinyint unsigned", "not null" => "1", "comment" => "Status of the report, one of Entity Report::STATUS_*"],
			"resolution" => ["type" => "tinyint unsigned", "comment" => "Resolution of the report, one of Entity Report::RESOLUTION_*"],
			"created" => ["type" => "datetime(6)", "not null" => "1", "default" => DBA::NULL_DATETIME6, "comment" => ""],
			"edited" => ["type" => "datetime(6)", "comment" => "Last time the report has been edited"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
			"cid" => ["cid"],
			"reporter-id" => ["reporter-id"],
			"gsid" => ["gsid"],
			"last-editor-uid" => ["last-editor-uid"],
			"assigned-uid" => ["assigned-uid"],
			"status-resolution" => ["status", "resolution"],
			"created" => ["created"],
			"edited" => ["edited"],
		]
	],
	"report-post" => [
		"comment" => "Individual posts attached to a moderation report",
		"fields" => [
			"rid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["report" => "id"], "comment" => "Report id"],
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["item-uri" => "id"], "comment" => "Uri-id of the reported post"],
			"status" => ["type" => "tinyint unsigned", "comment" => "Status of the reported post"],
		],
		"indexes" => [
			"PRIMARY" => ["rid", "uri-id"],
			"uri-id" => ["uri-id"],
		]
	],
	"report-rule" => [
		"comment" => "Terms of service rule lines relevant to a moderation report",
		"fields" => [
			"rid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "foreign" => ["report" => "id"], "comment" => "Report id"],
			"line-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => "Terms of service rule line number, may become invalid after a TOS change."],
			"text" => ["type" => "text", "not null" => "1", "comment" => "Terms of service rule text recorded at the time of the report"],
		],
		"indexes" => [
			"PRIMARY" => ["rid", "line-id"],
		]
	],
	"search" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"term" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_term" => ["uid", "term(64)"],
			"term" => ["term(64)"]
		]
	],
	"session" => [
		"comment" => "web session storage",
		"fields" => [
			"id" => ["type" => "bigint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"sid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
			"data" => ["type" => "text", "comment" => ""],
			"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"sid" => ["sid(64)"],
			"expire" => ["expire"],
		]
	],
	"storage" => [
		"comment" => "Data stored by Database storage backend",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented image data id"],
			"data" => ["type" => "longblob", "not null" => "1", "comment" => "file data"]
		],
		"indexes" => [
			"PRIMARY" => ["id"]
		]
	],
	"subscription" => [
		"comment" => "Push Subscription for the API",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented image data id"],
			"application-id" => ["type" => "int unsigned", "not null" => "1", "foreign" => ["application" => "id"], "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "foreign" => ["user" => "uid"], "comment" => "Owner User id"],
			"endpoint" => ["type" => "varchar(511)", "comment" => "Endpoint URL"],
			"pubkey" => ["type" => "varchar(127)", "comment" => "User agent public key"],
			"secret" => ["type" => "varchar(32)", "comment" => "Auth secret"],
			"follow" => ["type" => "boolean", "comment" => ""],
			"favourite" => ["type" => "boolean", "comment" => ""],
			"reblog" => ["type" => "boolean", "comment" => ""],
			"mention" => ["type" => "boolean", "comment" => ""],
			"poll" => ["type" => "boolean", "comment" => ""],
			"follow_request" => ["type" => "boolean", "comment" => ""],
			"status" => ["type" => "boolean", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"application-id_uid" => ["UNIQUE", "application-id", "uid"],
			"uid_application-id" => ["uid", "application-id"],
		]
	],
	"check-full-text-search" => [
		"comment" => "Check for a full text search match in user defined channels before storing the message in the system",
		"fields" => [
			"pid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => "The ID of the process"],
			"searchtext" => ["type" => "mediumtext", "comment" => "Simplified text for the full text search"],
		],
		"indexes" => [
			"PRIMARY" => ["pid"],
			"searchtext" => ["FULLTEXT", "searchtext"],
		],
	],
	"userd" => [
		"comment" => "Deleted usernames",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"username" => ["type" => "varchar(255)", "not null" => "1", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"username" => ["username(32)"],
		]
	],
	"user-contact" => [
		"comment" => "User specific public contact data",
		"fields" => [
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["contact" => "id"], "comment" => "Contact id of the linked public contact"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["user" => "uid"], "comment" => "User id"],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the contact url"],
			"blocked" => ["type" => "boolean", "comment" => "Contact is completely blocked for this user"],
			"ignored" => ["type" => "boolean", "comment" => "Posts from this contact are ignored"],
			"collapsed" => ["type" => "boolean", "comment" => "Posts from this contact are collapsed"],
			"hidden" => ["type" => "boolean", "comment" => "This contact is hidden from the others"],
			"channel-only" => ["type" => "boolean", "comment" => "This contact is displayed only in channels, but not in the network stream."],
			"is-blocked" => ["type" => "boolean", "comment" => "User is blocked by this contact"],
			"channel-frequency" => ["type" => "tinyint unsigned", "comment" => "Controls the frequency of the appearance of this contact in channels"],
			"pending" => ["type" => "boolean", "comment" => ""],
			"rel" => ["type" => "tinyint unsigned", "comment" => "The kind of the relation between the user and the contact"],
			"info" => ["type" => "mediumtext", "comment" => ""],
			"notify_new_posts" => ["type" => "boolean", "comment" => ""],
			"remote_self" => ["type" => "tinyint unsigned", "comment" => "0 => No mirroring, 1-2 => Mirror as own post, 3 => Mirror as reshare"],
			"fetch_further_information" => ["type" => "tinyint unsigned", "comment" => "0 => None, 1 => Fetch information, 3 => Fetch keywords, 2 => Fetch both"],
			"ffi_keyword_denylist" => ["type" => "text", "comment" => ""],
			"hub-verify" => ["type" => "varbinary(383)", "comment" => ""],
			"protocol" => ["type" => "char(4)", "comment" => "Protocol of the contact"],
			"rating" => ["type" => "tinyint", "comment" => "Automatically detected feed poll frequency"],
			"priority" => ["type" => "tinyint unsigned", "comment" => "Feed poll priority"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "cid"],
			"cid" => ["cid"],
			"uri-id_uid" => ["UNIQUE", "uri-id", "uid"],
		]
	],
	"arrived-activity" => [
		"comment" => "Id of arrived activities",
		"fields" => [
			"object-id" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "object id of the incoming activity"],
			"received" => ["type" => "datetime", "comment" => "Receiving date"],
		],
		"indexes" => [
			"PRIMARY" => ["object-id"],
		],
		"engine" => "MEMORY",
	],
	"fetched-activity" => [
		"comment" => "Id of fetched activities",
		"fields" => [
			"object-id" => ["type" => "varbinary(383)", "not null" => "1", "primary" => "1", "comment" => "object id of fetched activity"],
			"received" => ["type" => "datetime", "comment" => "Receiving date"],
		],
		"indexes" => [
			"PRIMARY" => ["object-id"],
		],
		"engine" => "MEMORY",
	],
	"worker-ipc" => [
		"comment" => "Inter process communication between the frontend and the worker",
		"fields" => [
			"key" => ["type" => "int", "not null" => "1", "primary" => "1", "comment" => ""],
			"jobs" => ["type" => "boolean", "comment" => "Flag for outstanding jobs"],
		],
		"indexes" => [
			"PRIMARY" => ["key"],
		],
		"engine" => "MEMORY",
	],
];

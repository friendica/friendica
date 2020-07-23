<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Main database structure configuration file.
 *
 * Here are described all the tables, fields and indexes Friendica needs to work.
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
 * "foreign" adds true foreign keys on the database level, while "relation" simulates this behaviour.
 *
 * If you need to make any change, make sure to increment the DB_UPDATE_VERSION constant value below.
 *
 */

use Friendica\Database\DBA;

if (!defined('DB_UPDATE_VERSION')) {
	define('DB_UPDATE_VERSION', 1358);
}

return [
	// Side tables
	"gserver" => [
		"comment" => "Global servers",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"version" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"site_name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"info" => ["type" => "text", "comment" => ""],
			"register_policy" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
			"registered-users" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Number of registered users"],
			"directory-type" => ["type" => "tinyint", "default" => "0", "comment" => "Type of directory service (Poco, Mastodon)"],
			"poco" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"noscrape" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"platform" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"relay-subscribe" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Has the server subscribed to the relay system"],
			"relay-scope" => ["type" => "varchar(10)", "not null" => "1", "default" => "", "comment" => "The scope of messages that the server wants to get"],
			"detection-method" => ["type" => "tinyint unsigned", "comment" => "Method that had been used to detect that server"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_poco_query" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_contact" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_failure" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"failed" => ["type" => "boolean", "comment" => "Connection failed"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"nurl" => ["UNIQUE", "nurl(190)"],
		]
	],
	"clients" => [
		"comment" => "OAuth usage",
		"fields" => [
			"client_id" => ["type" => "varchar(20)", "not null" => "1", "primary" => "1", "comment" => ""],
			"pw" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => ""],
			"redirect_uri" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
			"name" => ["type" => "text", "comment" => ""],
			"icon" => ["type" => "text", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["client_id"],
		]
	],
	"contact" => [
		"comment" => "contact table",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"updated" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Date of last contact update"],
			"self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 if the contact is the user him/her self"],
			"remote_self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"rel" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "The kind of the relation between the user and the contact"],
			"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network of the contact"],
			"protocol" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Protocol of the contact"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this contact is known by"],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Nick- and user name of the contact"],
			"location" => ["type" => "varchar(255)", "default" => "", "comment" => ""],
			"about" => ["type" => "text", "comment" => ""],
			"keywords" => ["type" => "text", "comment" => "public keywords (interests) of the contact"],
			"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Deprecated"],
			"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"attag" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"avatar" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo of the contact"],
			"thumb" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo (thumb size)"],
			"micro" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo (micro size)"],
			"site-pubkey" => ["type" => "text", "comment" => ""],
			"issued-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
			"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
			"batch" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"request" => ["type" => "varchar(255)", "comment" => ""],
			"notify" => ["type" => "varchar(255)", "comment" => ""],
			"poll" => ["type" => "varchar(255)", "comment" => ""],
			"confirm" => ["type" => "varchar(255)", "comment" => ""],
			"subscribe" => ["type" => "varchar(255)", "comment" => ""],
			"poco" => ["type" => "varchar(255)", "comment" => ""],
			"aes_allow" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"ret-aes" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"usehub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"subhub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"hub-verify" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"last-update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last try to update the contact info"],
			"success_update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last successful contact update"],
			"failure_update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last failed update"],
			"failed" => ["type" => "boolean", "comment" => "Connection failed"],
			"name-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"uri-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"avatar-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"term-date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last-item" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "date of the last post"],
			"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "Node-wide block status"],
			"block_reason" => ["type" => "text", "comment" => "Node-wide block reason"],
			"readonly" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "posts of the contact are readonly"],
			"writable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"forum" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a forum"],
			"prv" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a private group"],
			"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pending" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact has been deleted"],
			"rating" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
			"unsearchable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact prefers to not be searchable"],
			"sensitive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Contact posts sensitive content"],
			"baseurl" => ["type" => "varchar(255)", "default" => "", "comment" => "baseurl of the contact"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Global Server ID"],
			"reason" => ["type" => "text", "comment" => ""],
			"closeness" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "99", "comment" => ""],
			"info" => ["type" => "mediumtext", "comment" => ""],
			"profile-id" => ["type" => "int unsigned", "comment" => "Deprecated"],
			"bdyear" => ["type" => "varchar(4)", "not null" => "1", "default" => "", "comment" => ""],
			"bd" => ["type" => "date", "not null" => "1", "default" => DBA::NULL_DATE, "comment" => ""],
			"notify_new_posts" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"fetch_further_information" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"ffi_keyword_denylist" => ["type" => "text", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_name" => ["uid", "name(190)"],
			"self_uid" => ["self", "uid"],
			"alias_uid" => ["alias(32)", "uid"],
			"pending_uid" => ["pending", "uid"],
			"blocked_uid" => ["blocked", "uid"],
			"uid_rel_network_poll" => ["uid", "rel", "network", "poll(64)", "archive"],
			"uid_network_batch" => ["uid", "network", "batch(64)"],
			"addr_uid" => ["addr(32)", "uid"],
			"nurl_uid" => ["nurl(32)", "uid"],
			"nick_uid" => ["nick(32)", "uid"],
			"attag_uid" => ["attag(32)", "uid"],
			"dfrn-id" => ["dfrn-id(64)"],
			"issued-id" => ["issued-id(64)"],
			"network_uid_lastupdate" => ["network", "uid", "last-update"],
			"gsid" => ["gsid"]
		]
	],
	"item-uri" => [
		"comment" => "URI and GUID for items",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri" => ["type" => "varbinary(255)", "not null" => "1", "comment" => "URI of an item"],
			"guid" => ["type" => "varbinary(255)", "comment" => "A unique identifier for an item"]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri" => ["UNIQUE", "uri"],
			"guid" => ["guid"]
		]
	],
	"permissionset" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner id of this permission set"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_allow_cid_allow_gid_deny_cid_deny_gid" => ["allow_cid(50)", "allow_gid(30)", "deny_cid(50)", "deny_gid(30)"],
		]
	],
	"tag" => [
		"comment" => "tags and mentions",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"name" => ["type" => "varchar(96)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"type_name_url" => ["UNIQUE", "name", "url"],
			"url" => ["url"]
		]
	],
	// Main tables
	"2fa_app_specific_password" => [
		"comment" => "Two-factor app-specific _password",
		"fields" => [
			"id" => ["type" => "mediumint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Password ID for revocation"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "relation" => ["user" => "uid"], "comment" => "User ID"],
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
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "relation" => ["user" => "uid"], "comment" => "User ID"],
			"code" => ["type" => "varchar(50)", "not null" => "1", "primary" => "1", "comment" => "Recovery code string"],
			"generated" => ["type" => "datetime", "not null" => "1", "comment" => "Datetime the code was generated"],
			"used" => ["type" => "datetime", "comment" => "Datetime the code was used"],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "code"]
		]
	],
	"addon" => [
		"comment" => "registered addons",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"name" => ["type" => "varchar(50)", "not null" => "1", "default" => "", "comment" => "addon base (file)name"],
			"version" => ["type" => "varchar(50)", "not null" => "1", "default" => "", "comment" => "currently unused"],
			"installed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "currently always 1"],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "currently unused"],
			"timestamp" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "file timestamp to check for reloads"],
			"plugin_admin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = has admin config, 0 = has no admin config"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"name" => ["UNIQUE", "name"],
		]
	],
	"apcontact" => [
		"comment" => "ActivityPub compatible contacts - used in the ActivityPub implementation",
		"fields" => [
			"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "URL of the contact"],
			"uuid" => ["type" => "varchar(255)", "comment" => ""],
			"type" => ["type" => "varchar(20)", "not null" => "1", "comment" => ""],
			"following" => ["type" => "varchar(255)", "comment" => ""],
			"followers" => ["type" => "varchar(255)", "comment" => ""],
			"inbox" => ["type" => "varchar(255)", "not null" => "1", "comment" => ""],
			"outbox" => ["type" => "varchar(255)", "comment" => ""],
			"sharedinbox" => ["type" => "varchar(255)", "comment" => ""],
			"manually-approve" => ["type" => "boolean", "comment" => ""],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"name" => ["type" => "varchar(255)", "comment" => ""],
			"about" => ["type" => "text", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "comment" => ""],
			"addr" => ["type" => "varchar(255)", "comment" => ""],
			"alias" => ["type" => "varchar(255)", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => ""],
			"subscribe" => ["type" => "varchar(255)", "comment" => ""],
			"baseurl" => ["type" => "varchar(255)", "comment" => "baseurl of the ap contact"],
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
			"gsid" => ["gsid"]
		]
	],
	"attach" => [
		"comment" => "file attachments",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "generated index"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "hash"],
			"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "filename of original"],
			"filetype" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "mimetype"],
			"filesize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "size in bytes"],
			"data" => ["type" => "longblob", "not null" => "1", "comment" => "file data"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edit time"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
			"backend-class" => ["type" => "tinytext", "comment" => "Storage backend class"],
			"backend-ref" => ["type" => "text", "comment" => "Storage backend data reference"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"auth_codes" => [
		"comment" => "OAuth usage",
		"fields" => [
			"id" => ["type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""],
			"client_id" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "foreign" => ["clients" => "client_id"],
				"comment" => ""],
			"redirect_uri" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
			"expires" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
			"scope" => ["type" => "varchar(250)", "not null" => "1", "default" => "", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"client_id" => ["client_id"]
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
	"challenge" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"challenge" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"last_update" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"config" => [
		"comment" => "main configuration storage",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"cat" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
			"k" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
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
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "primary" => "1", "comment" => "contact the related contact had interacted with"],
			"relation-cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "primary" => "1", "comment" => "related contact who had interacted with the contact"],
			"last-interaction" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last interaction"],
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
			"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this conversation"],
			"recips" => ["type" => "text", "comment" => "sender_handle;recipient_handle"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
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
	"conversation" => [
		"comment" => "Raw data and structure information for messages",
		"fields" => [
			"item-uri" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "Original URI of the item - unrelated to the table with the same name"],
			"reply-to-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "URI to which this item is a reply"],
			"conversation-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "GNU Social conversation URI"],
			"conversation-href" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "GNU Social conversation link"],
			"protocol" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "255", "comment" => "The protocol of the item"],
			"direction" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "How the message arrived here: 1=push, 2=pull"],
			"source" => ["type" => "mediumtext", "comment" => "Original source"],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Receiving date"],
		],
		"indexes" => [
			"PRIMARY" => ["item-uri"],
			"conversation-uri" => ["conversation-uri"],
			"received" => ["received"],
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
	"event" => [
		"comment" => "Events",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact_id (ID of the contact in contact table)"],
			"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edit time"],
			"start" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "event start time"],
			"finish" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "event end time"],
			"summary" => ["type" => "text", "comment" => "short description or title of the event"],
			"desc" => ["type" => "text", "comment" => "event description"],
			"location" => ["type" => "text", "comment" => "event location"],
			"type" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => "event or birthday"],
			"nofinish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if event does have no end this is 1"],
			"adjust" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "adjust to timezone of the recipient (0 or 1)"],
			"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "0 or 1"],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_start" => ["uid", "start"],
		]
	],
	"fcontact" => [
		"comment" => "Diaspora compatible contacts - used in the Diaspora implementation",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "unique id"],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"request" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"batch" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"notify" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"poll" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"confirm" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"pubkey" => ["type" => "text", "comment" => ""],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"addr" => ["addr(32)"],
			"url" => ["UNIQUE", "url(190)"],
		]
	],
	"fsuggest" => [
		"comment" => "friend suggestion stuff",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"request" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"note" => ["type" => "text", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"gcign" => [
		"comment" => "contacts ignored by friend suggestions",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Local User id"],
			"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => "gcontact.id of ignored contact"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
			"gcid" => ["gcid"],
		]
	],
	"gcontact" => [
		"comment" => "global contacts",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this contact is known by"],
			"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Nick- and user name of the contact"],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Link to the contacts profile page"],
			"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Link to the profile photo"],
			"connect" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"updated" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_contact" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_failure" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"last_discovery" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last contact discovery"],
			"failed" => ["type" => "boolean", "comment" => "Connection failed"],
			"archive_date" => ["type" => "datetime", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"archived" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"about" => ["type" => "text", "comment" => ""],
			"keywords" => ["type" => "text", "comment" => "puplic keywords (interests)"],
			"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Deprecated"],
			"birthday" => ["type" => "varchar(32)", "not null" => "1", "default" => DBA::NULL_DATE, "comment" => ""],
			"community" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 if contact is forum account"],
			"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "-1", "comment" => ""],
			"hide" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = should be hidden from search"],
			"nsfw" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = contact posts nsfw content"],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "social network protocol"],
			"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"notify" => ["type" => "varchar(255)", "comment" => ""],
			"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"generation" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"server_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "baseurl of the contacts server"],
			"gsid" => ["type" => "int unsigned", "foreign" => ["gserver" => "id", "on delete" => "restrict"], "comment" => "Global Server ID"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"nurl" => ["UNIQUE", "nurl(190)"],
			"name" => ["name(64)"],
			"nick" => ["nick(32)"],
			"addr" => ["addr(64)"],
			"hide_network_updated" => ["hide", "network", "updated"],
			"updated" => ["updated"],
			"gsid" => ["gsid"]
		]
	],
	"gcontact-relation" => [
		"comment" => "Global contact relations",
		"fields" => [
			"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "primary" => "1", "comment" => "Global contact the related global contact had interacted with"],
			"relation-gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "primary" => "1", "comment" => "related global contact who had interacted with the global contact"],
			"last-interaction" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last interaction"],
		],
		"indexes" => [
			"PRIMARY" => ["gcid", "relation-gcid"],
			"relation-gcid" => ["relation-gcid"],
		]
	],
	"gfollower" => [
		"comment" => "Followers of global contacts",
		"fields" => [
			"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["gcontact" => "id"], "comment" => "global contact"],
			"follower-gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["gcontact" => "id"], "comment" => "global contact of the follower"],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates that the connection has been deleted"],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["gcid", "follower-gcid"],
			"follower-gcid" => ["follower-gcid"],
		]
	],
	"glink" => [
		"comment" => "'friends of friends' linkages derived from poco",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
			"zcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
			"updated" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"cid_uid_gcid_zcid" => ["UNIQUE", "cid", "uid", "gcid", "zcid"],
			"gcid" => ["gcid"],
		]
	],
	"group" => [
		"comment" => "privacy groups, group info",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the member list is not private"],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the group has been deleted"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "human readable name of group"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"group_member" => [
		"comment" => "privacy groups, member info",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"gid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["group" => "id"], "comment" => "groups.id of the associated group"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id of the member assigned to the associated group"],
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
			"gserver-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gserver" => "id"], "primary" => "1",
				"comment" => "The id of the gserver"],
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
			"hook_file_function" => ["UNIQUE", "hook", "file", "function"],
		]
	],
	"inbox-status" => [
		"comment" => "Status of ActivityPub inboxes",
		"fields" => [
			"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "URL of the inbox"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation date of this entry"],
			"success" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last successful delivery"],
			"failure" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of the last failed delivery"],
			"previous" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Previous delivery date"],
			"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is the inbox archived?"],
			"shared" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is it a shared inbox?"]
		],
		"indexes" => [
			"PRIMARY" => ["url"]
		]
	],
	"intro" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"fid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["fcontact" => "id"], "comment" => ""],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
			"knowyou" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"note" => ["type" => "text", "comment" => ""],
			"hash" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"datetime" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
			"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"item" => [
		"comment" => "Structure for all posts",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => ["thread" => "iid"]],
			"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this item"],
			"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uri-hash" => ["type" => "varchar(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-128 hash from uri"],
			"parent" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => "item.id of the parent to this item if it is a reply of some form; otherwise this must be set to the id of this item"],
			"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "uri of the parent to this item"],
			"parent-uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the parent uri"],
			"thr-parent" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "If the parent of this item is not the top-level item in the conversation, the uri of the immediate parent; otherwise set to parent-uri"],
			"thr-parent-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table that contains the thread parent uri"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Creation timestamp."],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last edit (default is created)"],
			"commented" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last comment/reply to this item"],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime"],
			"changed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date that something in the conversation changed, indicating clients should fetch the conversation again"],
			"gravity" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network from where the item comes from"],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Link to the contact table with uid=0 of the owner of this item"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Link to the contact table with uid=0 of the author of this item"],
			"icid" => ["type" => "int unsigned", "relation" => ["item-content" => "id"], "comment" => "Id of the item-content table entry that contains the whole item content"],
			"iaid" => ["type" => "int unsigned", "relation" => ["item-activity" => "id"], "comment" => "Id of the item-activity table entry that contains the activity data"],
			"vid" => ["type" => "smallint unsigned", "relation" => ["verb" => "id"], "comment" => "Id of the verb table entry that contains the activity verbs"],
			"extid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"post-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Post type (personal note, bookmark, ...)"],
			"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"private" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "0=public, 1=private, 2=unlisted"],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"moderated" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been deleted"],
			// User specific fields. Eventually they will move to user-item
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
			"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "This item was posted to the wall of uid"],
			"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item originated at this site"],
			"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been favourited"],
			"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "item has not been seen"],
			"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "The owner of this item was mentioned in it"],
			"forum_mode" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"psid" => ["type" => "int unsigned", "foreign" => ["permissionset" => "id", "on delete" => "restrict"], "comment" => "ID of the permission set of this post"],
			// It has to be decided whether these fields belong to the user or the structure
			"resource-id" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type"],
			"event-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["event" => "id"], "comment" => "Used to link to the event.id"],
			// Could possibly be replaced by the "attach" table?
			"attach" => ["type" => "mediumtext", "comment" => "JSON structure representing attachments to this item"],
			// Deprecated fields. Will be removed in upcoming versions
			"allow_cid" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"postopts" => ["type" => "text", "comment" => "Deprecated"],
			"inform" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"type" => ["type" => "varchar(20)", "comment" => "Deprecated"],
			"bookmark" => ["type" => "boolean", "comment" => "Deprecated"],
			"file" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"location" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"coord" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"tag" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"plink" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"title" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"content-warning" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"body" => ["type" => "mediumtext", "comment" => "Deprecated"],
			"app" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"verb" => ["type" => "varchar(100)", "comment" => "Deprecated"],
			"object-type" => ["type" => "varchar(100)", "comment" => "Deprecated"],
			"object" => ["type" => "text", "comment" => "Deprecated"],
			"target-type" => ["type" => "varchar(100)", "comment" => "Deprecated"],
			"target" => ["type" => "text", "comment" => "Deprecated"],
			"author-name" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"author-link" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"author-avatar" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"owner-name" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"owner-link" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"owner-avatar" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"rendered-hash" => ["type" => "varchar(32)", "comment" => "Deprecated"],
			"rendered-html" => ["type" => "mediumtext", "comment" => "Deprecated"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"guid" => ["guid(191)"],
			"uri" => ["uri(191)"],
			"parent" => ["parent"],
			"parent-uri" => ["parent-uri(191)"],
			"extid" => ["extid(191)"],
			"uid_id" => ["uid", "id"],
			"uid_contactid_id" => ["uid", "contact-id", "id"],
			"uid_received" => ["uid", "received"],
			"uid_commented" => ["uid", "commented"],
			"uid_unseen_contactid" => ["uid", "unseen", "contact-id"],
			"uid_network_received" => ["uid", "network", "received"],
			"uid_network_commented" => ["uid", "network", "commented"],
			"uid_thrparent" => ["uid", "thr-parent(190)"],
			"uid_parenturi" => ["uid", "parent-uri(190)"],
			"uid_contactid_received" => ["uid", "contact-id", "received"],
			"authorid_received" => ["author-id", "received"],
			"ownerid" => ["owner-id"],
			"contact-id" => ["contact-id"],
			"uid_uri" => ["uid", "uri(190)"],
			"resource-id" => ["resource-id"],
			"deleted_changed" => ["deleted", "changed"],
			"uid_wall_changed" => ["uid", "wall", "changed"],
			"mention_uid_id" => ["mention", "uid", "id"],
			"uid_eventid" => ["uid", "event-id"],
			"icid" => ["icid"],
			"iaid" => ["iaid"],
			"psid_wall" => ["psid", "wall"],
			"uri-id" => ["uri-id"],
			"parent-uri-id" => ["parent-uri-id"],
			"thr-parent-id" => ["thr-parent-id"],
		]
	],
	"item-activity" => [
		"comment" => "Activities for items",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri" => ["type" => "varchar(255)", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uri-hash" => ["type" => "varchar(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-128 hash from uri"],
			"activity" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-hash" => ["UNIQUE", "uri-hash"],
			"uri" => ["uri(191)"],
			"uri-id" => ["uri-id"]
		]
	],
	"item-content" => [
		"comment" => "Content for all posts",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"uri" => ["type" => "varchar(255)", "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uri-plink-hash" => ["type" => "varchar(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-128 hash from uri"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "item title"],
			"content-warning" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"body" => ["type" => "mediumtext", "comment" => "item body content"],
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
			"plink" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "permalink or URL to a displayable copy of the message at its source"],
			"verb" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams verb"]
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uri-plink-hash" => ["UNIQUE", "uri-plink-hash"],
			"uri" => ["uri(191)"],
			"plink" => ["plink(191)"],
			"uri-id" => ["uri-id"]
		]
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
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this private message"],
			"from-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "name of the sender"],
			"from-photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "contact photo link of the sender"],
			"from-url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "profile linke of the sender"],
			"contact-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "relation" => ["contact" => "id"], "comment" => "contact.id"],
			"convid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["conv" => "id"], "comment" => "conv.id"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"body" => ["type" => "mediumtext", "comment" => ""],
			"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if message visited it is 1"],
			"reply" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"replied" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"unknown" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if sender not in the contact table this is 1"],
			"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation time of the private message"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_seen" => ["uid", "seen"],
			"convid" => ["convid"],
			"uri" => ["uri(64)"],
			"parent-uri" => ["parent-uri(64)"],
			"contactid" => ["contact-id(32)"],
		]
	],
	"mailacct" => [
		"comment" => "Mail account data for fetching mails",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
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
		]
	],
	"manage" => [
		"comment" => "table of accounts that can manage each other",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"mid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_mid" => ["UNIQUE", "uid", "mid"],
		]
	],
	"notify" => [
		"comment" => "notifications",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"type" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"date" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"msg" => ["type" => "mediumtext", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"link" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => "item.id"],
			"parent" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
			"uri-id" => ["type" => "int unsigned", "relation" => ["item-uri" => "id"], "comment" => "Item-uri id of the related post"],
			"parent-uri-id" => ["type" => "int unsigned", "relation" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related post"],
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
		]
	],
	"notify-threads" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"notify-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["notify" => "id"], "comment" => ""],
			"master-parent-item" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
			"master-parent-uri-id" => ["type" => "int unsigned", "relation" => ["item-uri" => "id"], "comment" => "Item-uri id of the parent of the related post"],
			"parent-item" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"receiver-uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"],
				"comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"master-parent-uri-id" => ["master-parent-uri-id"],
		]
	],
	"oembed" => [
		"comment" => "cache for OEmbed queries",
		"fields" => [
			"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "page url"],
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
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"type" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Verify type"],
			"token" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A generated token"],
			"meta" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of creation"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"parsed_url" => [
		"comment" => "cache for 'parse_url' queries",
		"fields" => [
			"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "page url"],
			"guessing" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the 'guessing' mode active?"],
			"oembed" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the data the result of oembed?"],
			"content" => ["type" => "mediumtext", "comment" => "page data"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "datetime of creation"],
		],
		"indexes" => [
			"PRIMARY" => ["url", "guessing", "oembed"],
			"created" => ["created"],
		]
	],
	"participation" => [
		"comment" => "Storage for participation messages from Diaspora",
		"fields" => [
			"iid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "relation" => ["item" => "id"], "comment" => ""],
			"server" => ["type" => "varchar(60)", "not null" => "1", "primary" => "1", "comment" => ""],
			"cid" => ["type" => "int unsigned", "not null" => "1", "relation" => ["contact" => "id"], "comment" => ""],
			"fid" => ["type" => "int unsigned", "not null" => "1", "relation" => ["fcontact" => "id"], "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["iid", "server"],
			"cid" => ["cid"],
			"fid" => ["fid"]
		]
	],
	"pconfig" => [
		"comment" => "personal (per user) configuration storage",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"cat" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
			"k" => ["type" => "varbinary(100)", "not null" => "1", "default" => "", "comment" => ""],
			"v" => ["type" => "mediumtext", "comment" => ""],
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
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
			"guid" => ["type" => "char(16)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this photo"],
			"resource-id" => ["type" => "char(32)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "creation date"],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "last edited date"],
			"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"desc" => ["type" => "text", "comment" => ""],
			"album" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "The name of the album to which the photo belongs"],
			"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"type" => ["type" => "varchar(30)", "not null" => "1", "default" => "image/jpeg"],
			"height" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"width" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"datasize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"data" => ["type" => "mediumblob", "not null" => "1", "comment" => ""],
			"scale" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"profile" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
			"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
			"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
			"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
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
		]
	],
	"poll" => [
		"comment" => "Currently unused table for storing poll results",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"q0" => ["type" => "text", "comment" => ""],
			"q1" => ["type" => "text", "comment" => ""],
			"q2" => ["type" => "text", "comment" => ""],
			"q3" => ["type" => "text", "comment" => ""],
			"q4" => ["type" => "text", "comment" => ""],
			"q5" => ["type" => "text", "comment" => ""],
			"q6" => ["type" => "text", "comment" => ""],
			"q7" => ["type" => "text", "comment" => ""],
			"q8" => ["type" => "text", "comment" => ""],
			"q9" => ["type" => "text", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
		]
	],
	"poll_result" => [
		"comment" => "data for polls - currently unused",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"poll_id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["poll" => "id"]],
			"choice" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"poll_id" => ["poll_id"],
		]
	],
	"post-category" => [
		"comment" => "post relation to categories",
		"fields" => [
			"uri-id" => ["type" => "int unsigned", "not null" => "1", "primary" => "1",  "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["user" => "uid"], "comment" => "User id"],
			"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""],
			"tid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "foreign" => ["tag" => "id", "on delete" => "restrict"], "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id", "uid", "type", "tid"],
			"uri-id" => ["tid"]
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
			"ostatus" => ["type" => "mediumint", "not null" => "1", "default" => "0", "comment" => "Number of successful deliveries via OStatus"],
		],
		"indexes" => [
			"PRIMARY" => ["uri-id"],
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
	"process" => [
		"comment" => "Currently running system processes",
		"fields" => [
			"pid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => ""],
			"command" => ["type" => "varbinary(32)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["pid"],
			"command" => ["command"],
		]
	],
	"profile" => [
		"comment" => "user profiles data",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
			"profile-name" => ["type" => "varchar(255)", "comment" => "Deprecated"],
			"is-default" => ["type" => "boolean", "comment" => "Deprecated"],
			"hide-friends" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide friend list from viewers of this profile"],
			"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
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
			"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"thumb" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish default profile in local directory"],
			"net-publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish profile in global directory"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid_is-default" => ["uid", "is-default"],
			"pub_keywords" => ["FULLTEXT", "pub_keywords"],
		]
	],
	"profile_check" => [
		"comment" => "DFRN remote auth use",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
			"dfrn_id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"sec" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"profile_field" => [
		"comment" => "Custom profile fields",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner user id"],
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
	"push_subscriber" => [
		"comment" => "Used for OStatus: Contains feed subscribers",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"callback_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"topic" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"push" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Retrial counter"],
			"last_update" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last successful trial"],
			"next_try" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Next retrial date"],
			"renewed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => "Date of last subscription renewal"],
			"secret" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"next_try" => ["next_try"],
		]
	],
	"register" => [
		"comment" => "registrations requiring admin approval",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"hash" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
			"language" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
			"note" => ["type" => "text", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
		]
	],
	"search" => [
		"comment" => "",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"term" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"uid" => ["uid"],
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
	"thread" => [
		"comment" => "Thread related data",
		"fields" => [
			"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["item" => "id"],
				"comment" => "sequential ID"],
			"uri-id" => ["type" => "int unsigned", "foreign" => ["item-uri" => "id"], "comment" => "Id of the item-uri table entry that contains the item uri"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
			"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
			"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Item owner"],
			"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Item author"],
			"created" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"edited" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"commented" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"received" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"changed" => ["type" => "datetime", "not null" => "1", "default" => DBA::NULL_DATETIME, "comment" => ""],
			"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"private" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "0=public, 1=private, 2=unlisted"],
			"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"moderated" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"ignored" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"post-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Post type (personal note, bookmark, ...)"],
			"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
			"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"forum_mode" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
			"bookmark" => ["type" => "boolean", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["iid"],
			"uid_network_commented" => ["uid", "network", "commented"],
			"uid_network_received" => ["uid", "network", "received"],
			"uid_contactid_commented" => ["uid", "contact-id", "commented"],
			"uid_contactid_received" => ["uid", "contact-id", "received"],
			"contactid" => ["contact-id"],
			"ownerid" => ["owner-id"],
			"authorid" => ["author-id"],
			"uid_received" => ["uid", "received"],
			"uid_commented" => ["uid", "commented"],
			"uid_wall_received" => ["uid", "wall", "received"],
			"private_wall_origin_commented" => ["private", "wall", "origin", "commented"],
			"uri-id" => ["uri-id"],
		]
	],
	"tokens" => [
		"comment" => "OAuth usage",
		"fields" => [
			"id" => ["type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""],
			"secret" => ["type" => "text", "comment" => ""],
			"client_id" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "foreign" => ["clients" => "client_id"]],
			"expires" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
			"scope" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
		],
		"indexes" => [
			"PRIMARY" => ["id"],
			"client_id" => ["client_id"]
		]
	],
	"user" => [
		"comment" => "The local users",
		"fields" => [
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
			"parent-uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"],
				"comment" => "The parent user that has full control about this user"],
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
			"default-location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Default for item.location"],
			"allow_location" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 allows to display the location"],
			"theme" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "user theme preference"],
			"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
			"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
			"spubkey" => ["type" => "text", "comment" => ""],
			"sprvkey" => ["type" => "text", "comment" => ""],
			"verified" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "user is verified through email"],
			"blocked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 for user is blocked"],
			"blockwall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to post to the profile page of the user"],
			"hidewall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide profile details from unkown viewers"],
			"blocktags" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to tag the post of this user"],
			"unkmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Permit unknown people to send private mails to this user"],
			"cntunkmail" => ["type" => "int unsigned", "not null" => "1", "default" => "10", "comment" => ""],
			"notify-flags" => ["type" => "smallint unsigned", "not null" => "1", "default" => "65535", "comment" => "email notification options"],
			"page-flags" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "page/profile type"],
			"account-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
			"prvnets" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
			"pwdreset" => ["type" => "varchar(255)", "comment" => "Password reset request token"],
			"pwdreset_time" => ["type" => "datetime", "comment" => "Timestamp of the last password reset request"],
			"maxreq" => ["type" => "int unsigned", "not null" => "1", "default" => "10", "comment" => ""],
			"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
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
		]
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
			"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["contact" => "id"], "comment" => "Contact id of the linked public contact"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["user" => "uid"], "comment" => "User id"],
			"blocked" => ["type" => "boolean", "comment" => "Contact is completely blocked for this user"],
			"ignored" => ["type" => "boolean", "comment" => "Posts from this contact are ignored"],
			"collapsed" => ["type" => "boolean", "comment" => "Posts from this contact are collapsed"]
		],
		"indexes" => [
			"PRIMARY" => ["uid", "cid"]
		]
	],
	"user-item" => [
		"comment" => "User specific item data",
		"fields" => [
			"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["item" => "id"], "comment" => "Item id"],
			"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["user" => "uid"], "comment" => "User id"],
			"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marker to hide an item from the user"],
			"ignored" => ["type" => "boolean", "comment" => "Ignore this thread if set"],
			"pinned" => ["type" => "boolean", "comment" => "The item is pinned on the profile page"],
			"notification-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
		],
		"indexes" => [
			"PRIMARY" => ["uid", "iid"],
			"uid_pinned" => ["uid", "pinned"],
			"iid_uid" => ["iid", "uid"]
		]
	],
	"verb" => [
		"comment" => "Activity Verbs",
		"fields" => [
			"id" => ["type" => "smallint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"],
			"name" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => ""]
		],
		"indexes" => [
			"PRIMARY" => ["id"]
		]
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
	"workerqueue" => [
		"comment" => "Background tasks queue entries",
		"fields" => [
			"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented worker task id"],
			"parameter" => ["type" => "mediumtext", "comment" => "Task command"],
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
			"done_parameter" => ["done", "parameter(64)"],
			"done_executed" => ["done", "executed"],
			"done_priority_created" => ["done", "priority", "created"],
			"done_priority_next_try" => ["done", "priority", "next_try"],
			"done_pid_next_try" => ["done", "pid", "next_try"],
			"done_pid_priority_created" => ["done", "pid", "priority", "created"]
		]
	],
];

<?php

/* Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * This file declares the default values for the admin settings of Friendica.
 *
 * These values will be overridden by the admin settings page.
 *
 * Please don't edit this file directly as its content may change in the upcoming versions.
 *
 */

return [
	'config' => [
		// info (String)
		// Plaintext description of this node, used in the /friendica module.
		'info' => '',

		// register_policy (Constant)
		// Your choices are OPEN, APPROVE, or CLOSED.
		// Be certain to create your own personal account before setting CLOSED.
		// APPROVE requires you set system.admin_email to the email address of an
		// already registered person who can authorize and/or approve/deny the request.
		'register_policy' => \Friendica\Module\Register::CLOSED,

		// register_text (String)
		// Will be displayed prominently on the registration page.
		'register_text' => '',

		// sitename (String)
		// Displayed server name.
		'sitename' => 'Friendica Social Network',
	],
	'system' => [
		// account_abandon_days (Integer)
		// Will not waste system resources polling external sites for abandoned accounts.
		// Enter 0 for no time limit.
		'account_abandon_days' => 0,

		// adjust_poll_frequency (Boolean)
		// Automatically detect and set the best feed poll frequency.
		'adjust_poll_frequency' => true,

		// allow_relay_channels (Boolean)
		// Allow Users to set remote_self
		'allow_relay_channels' => true,

		// allowed_themes (Comma-separated list)
		// Themes users can change to in their settings.
		'allowed_themes' => 'frio,vier',

		// archival_days (Integer)
		// Number of days that we try to deliver content before we archive a contact.
		'archival_days' => 32,

		// banner (HTML string)
		// HTML snippet of the top navigation banner. Not supported by frio.
		'banner' => '<a href="https://friendi.ca"><img id="logo-img" width="32" height="32" src="images/friendica.svg" alt="logo" /></a><span id="logo-text"><a href="https://friendi.ca">Friendica</a></span>',

		// blocked_tags (String)
		// Comma separated list of hashtags that shouldn't be displayed in the trending tags
		'blocked_tags' => '',

		// cache_contact_avatar (Boolean)
		// Cache versions of the contact avatars. Uses a lot of storage space
		'cache_contact_avatar' => true,

		// cron_interval (Integer)
		// Minimal period in minutes between two calls of the "Cron" worker job.
		'cron_interval' => 5,

		// curl_timeout (Integer)
		// Value is in seconds. Set to 0 for unlimited (not recommended).
		'curl_timeout' => 60,

		// dbclean (Boolean)
		// Remove old remote items, orphaned database records and old content from some other helper tables.
		'dbclean' => false,

		// dbclean-expire-days (Integer)
		// When the database cleanup is enabled, this defines the days after which remote items will be deleted.
		// Own items, and marked or filed items are always kept. 0 disables this behaviour.
		'dbclean-expire-days' => 0,

		// dbclean-expire-unclaimed (Integer)
		// When the database cleanup is enabled, this defines the days after which unclaimed remote items
		// (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general
		// lifespan value of remote items if set to 0.
		'dbclean-expire-unclaimed' => 90,

		// dbclean_expire_conversation (Integer)
		// The conversation data is used for ActivityPub and OStatus, as well as for debug purposes.
		// It should be safe to remove it after 14 days, default is 90 days.
		'dbclean_expire_conversation' => 90,

		// debugging (boolean)
		// Enable/Disable Debugging (logging)
		'debugging' => false,

		// decoupled_receiver (Boolean)
		// Decouple incoming AP posts by doing the processing in the background.
		'decoupled_receiver' => false,

		// default_timezone (String)
		// Choose a default timezone. See https://secure.php.net/manual/en/timezones.php
		// It only applies to timestamps for anonymous viewers.
		'default_timezone' => 'UTC',

		// directory (String)
		// URL of the global directory.
		'directory' => 'https://dir.friendica.social',

		// explicit_content (Boolean)
		// Set this to announce that your node is used mostly for explicit content that might not be suited for minors.
		'explicit_content' => false,

		// forbidden_nicknames (Comma-separated list)
		// Prevents users from registering the specified nicknames on this node.
		// Default value comprises classic role names from RFC 2142.
		'forbidden_nicknames' => 'info, marketing, sales, support, abuse, noc, security, postmaster, hostmaster, usenet, news, webmaster, www, uucp, ftp, root, sysop',

		// compute_circle_counts (Boolean)
		// Compute contact circle level when counting unseen network posts.
		'compute_circle_counts' => true,

		// itemspage_network (Integer)
		// default number of items per page in stream pages (network, community, profile/contact statuses, search)
		'itemspage_network' => 40,

		// itemspage_network_mobile (Integer)
		// default number of items per page in stream pages (network, community, profile/contact statuses, search)
		// on detected mobile devices
		'itemspage_network_mobile' => 20,

		// jpeg_quality (Integer)
		// Sets the ImageMagick quality level for JPEG images. Values ranges from 50 (awful) to 100 (near perfect).
		'jpeg_quality' => 100,

		// language (String)
		// System default language, including admin-created user default language.
		// Two-letters ISO 639-1 code.
		'language' => 'en',

		// local_search (Boolean)
		// Blocks search for users who are not logged in to prevent crawlers from blocking your system.
		'local_search' => false,

		// logfile (String)
		// The logfile for storing logs.
		// Can be a full path or a relative path to the Friendica home directory
		'logfile' => 'log/friendica.log',

		// loglevel (String)
		// The loglevel for all logs.
		// Has to be one of these values: emergency, alert, critical, error, warning, notice, info, debug
		'loglevel' => 'notice',

		// max_author_posts_community_page (Integer)
		// The maximum number of posts on the local community page from a single author.
		'max_author_posts_community_page' => 0,

		// max_image_length (Integer)
		// An alternate way of limiting picture upload sizes.
		// Specify the maximum pixel  length that pictures are allowed to be (for non-square pictures, it will apply to the longest side).
		// Pictures longer than this length will be resized to be this length (on the longest side, the other side will be scaled appropriately).
		// If you don't want to set a maximum length, set to -1.
		'max_image_length' => -1,

		// max_server_posts_community_page (Integer)
		// The maximum number of posts on the global community page from a single server.
		'max_server_posts_community_page' => 0,

		// maximagesize (Integer)
		// Maximum size in bytes of an uploaded photo.
		'maximagesize' => 800000,

		// maxloadavg (Integer)
		// Maximum system load before delivery and poll processes are deferred.
		'maxloadavg' => 20,

		// min_memory (Integer)
		// Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).
		'min_memory' => 0,

		// min_poll_interval (Integer)
		// minimal distance in minutes between two polls for a contact. Reasonable values are between 1 and 59.
		'min_poll_interval' => 15,

		// no_regfullname (Boolean)
		// Allow users to register with a display name comporting fewer than two parts separated by spaces.
		'no_regfullname' => true,

		// no_oembed_rich_content (Boolean)
		// If enabled, allow OEmbed for all URLs. Disabled by default.
		'no_oembed_rich_content' => true,

		// optimize_tables (Boolean)
		// Periodically (once an hour) run an "optimize table" command for cache tables
		'optimize_tables' => false,

		// process_view (Boolean)
		// Process the "View" activity that is used by Peertube.
		'process_view' => false,

		// register_notification (Boolean)
		// Send a notification mail to the admin for each new registration.
		'register_notification' => true,

		// relay_deny_tags (String)
		// Comma separated list of tags that are rejected.
		'relay_deny_tags' => '',

		// relay_deny_undetected_language (Boolean)
		// Deny undetected languages
		'relay_deny_undetected_language' => false,

		// relay_languages (Integer)
		// Number of languages that are used per post to check for acceptable posts.
		'relay_languages' => 10,

		// relay_language_quality (Float)
		// Minimum value for the language detection quality for relay posts. The value must be between 0 and 1.
		'relay_language_quality' => 0,

		// relay_max_tags (Integer)
		// Maximum amount of tags in a post before it is rejected as spam.
		'relay_max_tags' => 20,

		// relay_directly (Boolean)
		// Directly transmit content to relay subscribers without using a relay server
		'relay_directly' => false,

		// relay_scope (Relay::SCOPE_NONE, Relay::SCOPE_TAGS or Relay::SCOPE_ALL)
		// Defines the scope of accepted posts from the relay servers
		'relay_scope' => '',

		// relay_server_tags (String)
		// Comma separated list of tags for the "tags" subscription.
		'relay_server_tags' => '',

		// relay_user_tags (Boolean)
		// If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".
		'relay_user_tags' => true,

		// temppath (String)
		// Custom temporary file directory
		'temppath' => '',

		// theme (String)
		// System theme name.
		'theme' => 'frio',

		// update_active_contacts (Boolean)
		// When activated, only public contacts will be activated regularly that are used for example in items or tags.
		'update_active_contacts' => false,

		// update_known_contacts (Boolean)
		// When activated, only public contacts will be activated regularly that are in a contact list of a local user.
		'update_known_contacts' => false,

		// url (String)
		// The fully-qualified URL of this Friendica node.
		// Used by the worker in a non-HTTP execution environment.
		'url' => '',

		// poco_discovery (Boolean)
		// If enabled, the system will check for the existance of other contacts and servers
		'poco_discovery' => false,

		// poco_requery_days (Integer)
		// Number of days after which a server is requeried for their contacts and servers it knows of.
		'poco_requery_days' => 30,

		// worker_defer_limit (Integer)
		// Per default the systems tries delivering for 15 times before dropping it.
		'worker_defer_limit' => 15,

		// worker_fetch_limit (Integer)
		// Number of worker tasks that are fetched in a single query.
		'worker_fetch_limit' => 1,

		// worker_load_cooldown (Integer)
		// Maximum load that causes a cooldown before each worker function call.
		'worker_load_cooldown' => 0,
	],

	'channel' => [
		// engagement_hours (Integer)
		// Maximum age of incoming posts for the engagement table, when the engagement post limit is 0 or hasn't been reached yet.
		'engagement_hours' => 24,

		// engagement_post_limit (Integer)
		// NUmber of posts that are held in the engagement table
		'engagement_post_limit' => 20000,

		// interaction_score_days (Integer)
		// Number of days that are used to calculate the interaction score.
		'interaction_score_days' => 30,

		// max_posts_per_author (Integer)
		// Maixmum number of posts per page by author
		'max_posts_per_author' => 2,

		// sharer_interaction_days (Integer)
		// Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.
		'sharer_interaction_days' => 90,
	],

	// Used in the admin settings to lock certain features
	'featurelock' => [
	],

	// Storage backend configuration
	'storage' => [
		// name (String)
		// The name of the current used backend (default is Database)
		'name' => 'Database',
	],
];

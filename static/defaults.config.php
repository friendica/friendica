<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
 * This file declares the default values for the base config of Friendica.
 *
 * These configuration values aren't accessible from the admin settings page and custom values must be set in config/local.config.php
 *
 * Please don't edit this file directly as its content may change in the upcoming versions.
 *
 */

return [
	'database' => [
		// host (String)
		// Hostname or IP address of the database server.
		// Can contain the port number with the syntax "hostname:port".
		'hostname' => '',

		// port (Integer)
		// Port of the database server.
		// Can be used instead of adding a port number to the hostname
		'port' => null,

		// socket (String)
		// Socket of the database server.
		// Can be used instead of adding a socket location to the hostname
		'socket' => '',

		// user (String)
		// Database username. Please don't use "root".
		'username' => '',

		// pass (String)
		// Database user password. Please don't use empty passwords.
		'password' => '',

		// base (String)
		// Database name.
		'database' => '',

		// charset (String)
		// Database connection charset. Changing this value will likely corrupt special characters.
		'charset' => 'utf8mb4',

		// pdo_emulate_prepares (Boolean)
		// If enabled, the builtin emulation for prepared statements is used.
		// This can be used as a workaround for the database error "Prepared statement needs to be re-prepared".
		'pdo_emulate_prepares' => true,

		// disable_pdo (Boolean)
		// PDO is used by default (if available). Otherwise, MySQLi will be used.
		'disable_pdo' => false,

		// persistent (Boolean)
		// This controls if the system should use persistent connections or not.
		// Persistent connections increase the performance.
		// On the other hand the number of open connections are higher,
		// this will most likely increase the system load.
		'persistent' => false,
	],
	'config' => [
		// admin_email (Comma-separated list)
		// In order to perform system administration via the admin panel,
		// this must precisely match the email address of the person logged in.
		'admin_email' => '',

		// admin_nickname (String)
		// Nickname of the main admin user, used if there are more than one admin user defined in config => admin_email.
		'admin_nickname' => '',

		// max_import_size (Integer)
		// Maximum body size of DFRN and Mail messages in characters. 0 is unlimited.
		'max_import_size' => 200000,

		// php_path (String)
		// Location of PHP command line processor.
		'php_path' => 'php',
	],
	'system' => [
		// adjust_poll_frequency (Boolean)
		// Automatically detect and set the best feed poll frequency.
		'adjust_poll_frequency' => false,

		// allowed_link_protocols (Array)
		// Allowed protocols in links URLs, add at your own risk. http(s) is always allowed.
		'allowed_link_protocols' => ['ftp://', 'ftps://', 'mailto:', 'cid:', 'gopher://'],

		// always_show_preview (Boolean)
		// Only show small preview pictures.
		'always_show_preview' => false,

		// ap_always_bcc (Boolean)
		// Adresses non-mentioned ActivityPub receivers by BCC instead of CC. Increases privacy, decreases performance.
		'ap_always_bcc' => false,

		// archival_days (Integer)
		// Number of days that we try to deliver content before we archive a contact.
		'archival_days' => 32,

		// auth_cookie_lifetime (Integer)
		// Number of days that should pass without any activity before a user who
		// chose "Remember me" when logging in is considered logged out.
		'auth_cookie_lifetime' => 7,

		// avatar_cache (Boolean)
		// Cache avatar pictures as files (experimental)
		'avatar_cache' => false,

		// avatar_cache_path (String)
		// File path to the avatar cache. Default is /(your basepath)/avatar/
		// The value has to be an absolute path and has to end with a "/"
		'avatar_cache_path' => '',

		// avatar_cache_url (String)
		// Base URL of the avatar cache. Default is http(s)://(your hostname)/avatar/
		// The value has to start with the scheme and end with a "/"
		'avatar_cache_url' => '',

		// big_emojis (Boolean)
		// Display "Emoji Only" posts in big.
		'big_emojis' => false,

		// bulk_delivery (Boolean)
		// Delivers AP messages in a bulk (experimental)
		'bulk_delivery' => false,

		// block_local_dir (Boolean)
		// Deny public access to the local user directory.
		'block_local_dir' => false,

		// blocked_tags (String)
		// Comma separated list of hashtags that shouldn't be displayed in the trending tags
		'blocked_tags' => '',

		// community_no_sharer (Boolean)
		// Don't display sharing accounts on the global community
		'community_no_sharer' => false,

		// contact_update_limit (Integer)
		// How many contacts should be checked at a time?
		'contact_update_limit' => 100,

		// cron_interval (Integer)
		// Minimal period in minutes between two calls of the "Cron" worker job.
		'cron_interval' => 5,

		// cache_driver (database|memcache|memcached|redis|apcu)
		// Whether to use Memcache, Memcached, Redis or APCu to store temporary cache.
		'cache_driver' => 'database',

		// decoupled_receiver (Boolean)
		// Decouple incoming AP posts by doing the processing in the background.
		'decoupled_receiver' => false,

		// distributed_cache_driver (database|memcache|memcached|redis)
		// Whether to use database, Memcache, Memcached or Redis as a distributed cache.
		'distributed_cache_driver' => 'database',

		// fetch_parents (Boolean)
		// Fetch missing parent posts
		'fetch_parents' => true,

		// config_adapter (jit|preload)
		// Allow to switch the configuration adapter to improve performances at the cost of memory consumption.
		'config_adapter' => 'jit',

		// curl_range_bytes (Integer)
		// Maximum number of bytes that should be fetched. Default is 0, which mean "no limit".
		'curl_range_bytes' => 0,

		// crawl_permit_period (Integer)
		// Period in seconds between allowed searches when the number of free searches is reached and "permit_crawling" is activated.
		'crawl_permit_period' => 60,

		// db_log (Path)
		// Name of a logfile to log slow database queries.
		'db_log' => '',

		// db_log_index (Path)
		// Name of a logfile to log queries with bad indexes.
		'db_log_index' => '',

		// db_log_index_watch (Comma-separated list)
		// Watchlist of indexes to watch.
		'db_log_index_watch' => '',

		// db_log_index_denylist (Comma-separated list)
		// Deny list of indexes that shouldn't be watched.
		'db_log_index_denylist' => '',

		// db_loglimit (Integer)
		// If a database call lasts longer than this value in seconds it is logged.
		// Inactive if system => db_log is empty.
		'db_loglimit' => 10,

		// db_loglimit_index (Integer)
		// Number of index rows needed to be logged for indexes on the watchlist. 0 to disable.
		'db_loglimit_index' => 0,

		// db_loglimit_index_high (Integer)
		// Number of index rows to be logged anyway (for any index). 0 to disable.
		'db_loglimit_index_high' => 0,

		// dbclean_expire_conversation (Integer)
		// When DBClean is enabled, any entry in the conversation table will be deleted after this many days.
		// This data is used for ActivityPub, so it shouldn't be lower than the average duration of a discussion.
		'dbclean_expire_conversation' => 90,

		// dbclean-expire-limit (Integer)
		// This defines the number of items that are to be deleted in a single call.
		// Reduce this value when you are getting lock issues.
		// A value of 0 disables the deletion process.
		'dbclean-expire-limit' => 1000,

		// daemon_watchdog (Boolean)
		// Enable regular checking if the daemon is running.
		// If it is not running and hadn't been terminated normally, it will be started automatically.
		'daemon_watchdog' => false,

		// delete_sleeping_processes (Boolean)
		// Periodically delete waiting database processes.
		'delete_sleeping_processes' => false,

		// dice_profiler_threshold (Float)
		// For profiling Dice class creation (0 = disabled, >0 = seconds threshold for profiling)
		'dice_profiler_threshold' => 0.5,

		// diaspora_test (Boolean)
		// For development only. Disables the message transfer.
		'diaspora_test' => false,

		// disable_email_validation (Boolean)
		// Disables the check if a mail address is in a valid format and can be resolved via DNS.
		'disable_email_validation' => false,

		// disable_implicit_mentions (Boolean) since 2019.03
		// Implicit mentions are mentions in the body of replies that are redundant in a thread-enabled system like Friendica.
		// This config key disables the gathering of implicit mentions in incoming and outgoing posts.
		// Also disables the default automatic removal of implicit mentions from the body of incoming posts.
		// Also disables the default automatic addition of implicit mentions in the body of outgoing posts.
		// Disabling implicit mentions also affects the "explicit_mentions" additional feature by limiting it
		// to the replied-to post author mention in the comment boxes.
		'disable_implicit_mentions' => false,

		// disable_url_validation (Boolean)
		// Disables the DNS lookup of a URL.
		'disable_url_validation' => false,

		// disable_password_exposed (Boolean)
		// Disable the exposition check against the remote haveibeenpwned API on password change.
		'disable_password_exposed' => false,

		// disable_polling (Boolean)
		// Disable the polling of DFRN and OStatus contacts through onepoll.php.
		'disable_polling' => false,

		// display_resharer (Boolean)
		// Display the first resharer as icon and text on a reshared item.
		'display_resharer' => false,

		// dlogfile (Path)
		// location of the developer log file.
		'dlogfile' => '',

		// dlogip (String)
		// restricts develop log writes to requests originating from this IP address.
		'dlogip' => '',

		// expire-notify-priority (integer)
		// Priority for the expirary notification
		'expire-notify-priority' => Friendica\Core\Worker::PRIORITY_LOW,

		// fetch_by_worker (Boolean)
		// Fetch missing posts via a background process
		'fetch_by_worker' => false,

		// fetch_featured_posts (Boolean)
		// Fetch featured posts from all contacts
		'fetch_featured_posts' => false,

		// free_crawls (Integer)
		// Number of "free" searches when system => permit_crawling is enabled.
		'free_crawls' => 10,

		// groupedit_image_limit (Integer)
		// Number of contacts at which the group editor should switch from display the profile pictures of the contacts to only display the names.
		// This can alternatively be set on a per-account basis in the pconfig table.
		'groupedit_image_limit' => 400,

		// gserver_update_limit (Integer)
		// How many servers should be checked at a time?
		'gserver_update_limit' => 100,

		// hsts (Boolean)
		// Enables the sending of HTTP Strict Transport Security headers.
		'hsts' => false,

		// ignore_cache (Boolean)
		// For development only. Disables the item cache.
		'ignore_cache' => false,

		// insecure_imap (Boolean)
		// If enabled, users are allowed to connect to their IMAP servers unencrypted.
		// For security reasons this is disabled by default.
		'insecure_imap' => false,

		// instances_social_key (String)
		// Key to the API of https://instances.social which retrieves data about mastodon servers.
		// See https://instances.social/api/token to get an API key.
		'instances_social_key' => '',

		// ipv4_resolve (Boolean)
		// Resolve IPV4 addresses only. Don't resolve to IPV6.
		'ipv4_resolve' => false,

		// invitation_only (Boolean)
		// If set true registration is only possible after a current member of the node has sent an invitation.
		'invitation_only' => false,

		// itemspage_network (Integer)
		// default number of items per page in stream pages (network, community, profile/contact statuses, search)
		'itemspage_network' => 40,

		// itemspage_network_mobile (Integer)
		// default number of items per page in stream pages (network, community, profile/contact statuses, search)
		// on detected mobile devices
		'itemspage_network_mobile' => 20,

		// jpeg_quality (Integer)
		//
		// Lower numbers save space at cost of image detail
		// where n is between 1 and 100, and with very poor results below about 50
		'jpeg_quality' => 100,

		// like_no_comment (Boolean)
		// Don't update the "commented" value of an item when it is liked.
		'like_no_comment' => false,

		// local_block (Boolean)
		// Used in conjunction with "block_public".
		'local_block' => false,

		// local_search (Boolean)
		// Blocks search for users who are not logged in to prevent crawlers from blocking your system.
		'local_search' => false,

		// local_tags (Boolean)
		// If activated, all hashtags will point to the local server.
		'local_tags' => false,

		// logger_config (String)
		// Sets the logging adapter of Friendica globally (monolog, syslog, stream)
		'logger_config' => 'stream',

		// syslog flags (Integer)
		// Sets the syslog flags in case 'logger_config' is set to 'syslog'
		'syslog_flags' => LOG_CONS | LOG_PID | LOG_ODELAY,

		// syslog flags (Integer)
		// Sets the syslog facility in case 'logger_config' is set to 'syslog'
		'syslog_facility' => LOG_USER,

		// maintenance_start (String)
		// Start of the window for the daily maintenance cron call.
		// The system timezone is used when no timezone is defined here.
		'maintenance_start' => '01:00 +00:00',

		// maintenance_end (String)
		// End of the window for the daily maintenance cron call
		// The system timezone is used when no timezone is defined here.
		'maintenance_end' => '03:00 +00:00',

		// max_batch_queue (Integer)
		// Maximum number of batched queue items for a single contact before subsequent messages are discarded.
		'max_batch_queue' => 1000,

		// max_connections (Integer)
		// The maximum number of database connections which can be in use before the worker process is deferred to its next interval.
		// When the system can't detect the maximum numbers of connection then this value can be used. Use 0 for auto-detection.
		'max_connections' => 0,

		// max_connections_level (Integer 0-100)
		// The maximum percentage of connections that are allowed to let the worker start.
		'max_connections_level' => 75,

		// max_contact_queue (Integer)
		// Maximum number of queue items for a single contact before subsequent messages are discarded.
		'max_contact_queue' => 500,

		// max_csv_file_size (Integer)
		// When uploading a CSV with account addresses to follow
		// in the user settings, this controls the maximum file
		// size of the upload file.
		'max_csv_file_size' => 30720,

		// max_feed_items (Integer)
		// Maximum number of feed items that are fetched and processed. For unlimited items set to 0.
		'max_feed_items' => 20,

		// max_image_length (Integer)
		// An alternate way of limiting picture upload sizes.
		// Specify the maximum pixel length that pictures are allowed to be (for non-square pictures, it will apply to the longest side).
		// Pictures longer than this length will be resized to be this length (on the longest side, the other side will be scaled appropriately).
		// If you don't want to set a maximum length, set to -1.
		'max_image_length' => -1,

		// max_likers (Integer)
		// Maximum number of "people who like (or don't like) this" that we will list by name
		'max_likers' => 75,

		// max_processes_backend (Integer)
		// Maximum number of concurrent database processes for background tasks.
		'max_processes_backend' => 5,

		// max_processes_frontend (Integer)
		// Maximum number of concurrent database processes for foreground tasks.
		'max_processes_frontend' => 20,

		// max_recursion_depth (Integer)
		// Maximum recursion depth when fetching posts until the job is delegated to a worker task or finished.
		'max_recursion_depth' => 50,

		// maximagesize (Integer)
		// Maximum size in bytes of an uploaded photo.
		'maximagesize' => 800000,

		// memcache_host (String)
		// Host name of the memcache daemon.
		'memcache_host' => '127.0.0.1',

		// memcache_port (Integer)
		// Port number of the memcache daemon.
		'memcache_port' => 11211,

		// memcached_hosts (Array)
		// Array of Memcached servers info [host, port(, weight)], see Memcached::addServers.
		'memcached_hosts' => [
			['127.0.0.1', '11211'],
		],

		// min_poll_interval (Integer)
		// minimal distance in minutes between two polls for a contact. Reasonable values are between 1 and 59.
		'min_poll_interval' => 15,

		// minimum_posting_interval (Integer)
		// Minimum interval between two feed posts per user
		'minimum_posting_interval' => 0,

		// no_count (Boolean)
		// Don't do count calculations (currently only when showing photo albums).
		'no_count' => false,

		// no_oembed (Boolean)
		// Don't use OEmbed to fetch more information about a link.
		'no_oembed' => false,

		// no_redirect_list (Array)
		// List of domains where HTTP redirects should be ignored.
		'no_redirect_list' => [],

		// no_smilies (Boolean)
		// Don't show smilies.
		'no_smilies' => false,

		// paranoia (Boolean)
		// Log out users if their IP address changed.
		'paranoia' => false,

		// permit_crawling (Boolean)
		// Restricts the search for not logged-in users to one search per minute.
		'permit_crawling' => false,

		// pidfile (Path)
		// Daemon pid file path. For example: pidfile = /path/to/daemon.pid
		'pidfile' => '',

		// png_quality (Integer)
		// Sets the ImageMagick compression level for PNG images. Values range from 0 (uncompressed) to 9 (most compressed).
		'png_quality' => 8,

		// profiler (Boolean)
		// Enable internal timings to help optimize code. Needed for "rendertime" addon.
		'profiler' => false,

		// pushpoll_frequency (Integer)
		// Frequency of contact poll for subhub contact using the DFRM or OStatus network.
		// Available values:
		// - 5 = every month
		// - 4 = every week
		// - 3 = every day
		// - 2 = twice a day
		// - 1 = every hour
		// - 0 = every minute
		'pushpoll_frequency' => 3,

		// redis_host (String)
		// Host name of the redis daemon.
		'redis_host' => '127.0.0.1',

		// redis_port (String)
		// Port number of the redis daemon.
		'redis_port' => 6379,

		// redis_db (Integer)
		// The sub-database of redis (0 - 15 possible sub-databases)
		'redis_db' => 0,

		// redis_password (String)
		// The authentication password for the redis database
		'redis_password' => null,

		// redistribute_activities (Boolean)
		// Redistribute incoming activities via ActivityPub
		'redistribute_activities' => true,

		// relay_deny_languages (Array)
		// Array of languages (two digit format) that are rejected.
		'relay_deny_languages' => [],

		// relay_deny_undetected_language (Boolean)
		// Deny undetected languages
		'relay_deny_undetected_language' => false,

		// session_handler (database|cache|native)
		// Whether to use Cache to store session data or to use PHP native session storage.
		'session_handler' => 'database',

		// remote_avatar_lookup (Boolean)
		// Perform an avatar lookup via the activated services for remote contacts
		'remote_avatar_lookup' => false,

		// remove_multiplicated_lines (Boolean)
		// If enabled, multiple linefeeds in items are stripped to a single one.
		'remove_multiplicated_lines' => false,

		// runtime_ignore (Array)
		// List of ignored commands for the runtime logging.
		'runtime_ignore' => [],

		// runtime_loglimit (Integer)
		// The runtime is logged, When the program execution time is higher than this value.
		'runtime_loglimit' => 0,

		// sendmail_params (Boolean)
		// Normal sendmail command parameters will be added when the PHP mail() function is called for sending e-mails.
		// This ensures the Sender Email address setting is applied to the message envelope rather than the host's default address.
		// Set to false if your non-sendmail agent is incompatible, or to restore old behavior of using the host address.
		'sendmail_params' => true,

		// set_creation_date (Boolean)
		// When enabled, the user can enter a creation date when composing a post.
		'set_creation_date' => false,

		// show_global_community_hint (Boolean)
		// When the global community page is enabled, use this option to display a hint above the stream, that this is a collection of all public top-level postings that arrive at your node.
		'show_global_community_hint' => false,

		// show_received (Boolean)
		// Show the received date along with the post creation date
		'show_received' => true,

		// show_received_seconds (Integer)
		// Display the received date when the difference between received and created is higher than this.
		'show_received_seconds' => 500,

		// show_unsupported_addons (Boolean)
		// Show all addons including the unsupported ones.
		'show_unsupported_addons' => false,

		// show_unsupported_themes (Boolean)
		// Show all themes including the unsupported ones.
		'show_unsupported_themes' => false,

		// throttle_limit_day (Integer)
		// Maximum number of posts that a user can send per day with the API. 0 to disable daily throttling.
		'throttle_limit_day' => 0,

		// throttle_limit_week (Integer)
		// Maximum number of posts that a user can send per week with the API. 0 to disable weekly throttling.
		'throttle_limit_week' => 0,

		// throttle_limit_month (Integer)
		// Maximum number of posts that a user can send per month with the API. 0 to disable monthly throttling.
		'throttle_limit_month' => 0,

		// transmit_pending_events (Boolean)
		// Transmit pending events upon accepted contact request for forums
		'transmit_pending_events' => false,

		// update_active_contacts (Boolean)
		// When activated, only public contacts will be activated regularly that are used for example in items or tags.
		'update_active_contacts' => false,

		// username_min_length (Integer)
		// The minimum character length a username can be.
		// This length is checked once the username has been trimmed and multiple spaces have been collapsed into one.
		// Minimum for this config value is 1. Maximum is 64 as the resulting profile URL mustn't be longer than 255 chars.
		'username_min_length' => 3,

		// username_max_length (Integer)
		// The maximum character length a username can be.
		// This length is checked once the username has been trimmed and multiple spaces have been collapsed into one.
		// Minimum for this config value is 1. Maximum is 64 as the resulting profile URL mustn't be longer than 255 chars.
		'username_max_length' => 48,

		// worker_cooldown (Float)
		// Cooldown period in seconds before each worker function call.
		'worker_cooldown' => 0,

		// worker_debug (Boolean)
		// If enabled, it prints out the number of running processes split by priority.
		'worker_debug' => false,

		// worker_fetch_limit (Integer)
		// Number of worker tasks that are fetched in a single query.
		'worker_fetch_limit' => 1,

		// worker_fork (Boolean)
		// Experimental setting. Use pcntl_fork to spawn a new worker process.
		// Does not work when "worker_multiple_fetch" is enabled (Needs more testing)
		'worker_fork' => false,

		// worker_jpm (Boolean)
		// If enabled, it prints out the jobs per minute.
		'worker_jpm' => false,

		// worker_jpm_range (String)
		// List of minutes for the jobs per minute (JPM) calculation
		'worker_jpm_range' => '1, 10, 60',

		// worker_load_cooldown (Integer)
		// Maximum load that causes a cooldown before each worker function call.
		'worker_load_cooldown' => 0,

		// worker_load_exponent (Integer)
		// Default 3, which allows only 25% of the maximum worker queues when server load reaches around 37% of maximum load.
		// For a linear response where 25% of worker queues are allowed at 75% of maximum load, set this to 1.
		// Setting 0 would allow maximum worker queues at all times, which is not recommended.
		'worker_load_exponent' => 3,

		// worker_max_duration (Array)
		// Maximum runtime per priority. Worker processes that exceed this runtime will be terminated.
		'worker_max_duration' => [
			Friendica\Core\Worker::PRIORITY_CRITICAL   => 720,
			Friendica\Core\Worker::PRIORITY_HIGH       => 10,
			Friendica\Core\Worker::PRIORITY_MEDIUM     => 60,
			Friendica\Core\Worker::PRIORITY_LOW        => 180,
			Friendica\Core\Worker::PRIORITY_NEGLIGIBLE => 720
		],

		// worker_processes_cooldown (Integer)
		// Maximum number per processes that causes a cooldown before each worker function call.
		'worker_processes_cooldown' => 0,

		// worker_multiple_fetch (Boolean)
		// When activated, the worker fetches jobs for multiple workers (not only for itself).
		// This is an experimental setting without knowing the performance impact.
		// Does not work when "worker_fork" is enabled (Needs more testing)
		'worker_multiple_fetch' => false,

		// worker_defer_limit (Integer)
		// Per default the systems tries delivering for 15 times before dropping it.
		'worker_defer_limit' => 15,

		// xrd_timeout (Integer)
		// Timeout in seconds for fetching the XRD links and other requests with an expected shorter timeout
		'xrd_timeout' => 20,
	],
	'proxy' => [
		// forwarded_for_headers (String)
		// A comma separated list of all allowed header values to retrieve the real client IP
		// The headers are evaluated in order.
		'forwarded_for_headers' => 'HTTP_X_FORWARDED_FOR',

		// trusted_proxies (String)
		// A comma separated list of all trusted proxies, which will get skipped during client IP retrieval
		// IP ranges and CIDR notations are allowed
		'trusted_proxies' => '',
	],
	'experimental' => [
		// exp_themes (Boolean)
		// Show experimental themes in user settings.
		'exp_themes' => false,
	],
	'theme' => [
		// hide_eventlist (Boolean)
		// Don't show the birthdays and events on the profile and network page.
		'hide_eventlist' => false,
	],
	'jabber' => [
		// debug (Boolean)
		// Enable debug level for the jabber account synchronisation.
		'debug' => false,
		// lockpath (Path)
		// Must be writable by the ejabberd process. if set then it will prevent the running of multiple processes.
		'lockpath' => '',
	],
	'debug' => [
		// ap_inbox_log (Boolean)
		// Logs every call to /inbox as a JSON file in Friendica's temporary directory
		'ap_inbox_log' => false,

		// ap_inbox_store_untrusted (Boolean)
		// Store untrusted content in the inbox entries
		'ap_inbox_store_untrusted' => false,

		// ap_log_unknown (Boolean)
		// Logs every unknown ActivityPub activity
		'ap_log_unknown' => false,

		// ap_log_failure (Boolean)
		// Logs every ActivityPub activity that couldn't be compacted
		'ap_log_failure' => false,

		// store_source (Boolean)
		// Store the source of any post that arrived
		'store_source' => false,
	],
	'smarty3' => [
		// config_dir (String)
		// Base working directory for the templating engine, must be writeable by the webserver user
		'config_dir' => 'view/smarty3',

		// use_sub_dirs (Boolean)
		// By default the template cache is stored in several subdirectories.
		'use_sub_dirs' => true,
	],
];

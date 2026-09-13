<?php

/* Copyright (C) 2010-2026, the Friendica project
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Main mapping table of environment variables to namespaced config values
 *
 */

return [
	'MYSQL_HOST'     => ['database', 'hostname'],
	'MYSQL_USERNAME' => ['database', 'username'],
	'MYSQL_USER'     => ['database', 'username'],
	'MYSQL_PORT'     => ['database', 'port'],
	'MYSQL_SOCKET'   => ['database', 'socket'],
	'MYSQL_PASSWORD' => ['database', 'password'],
	'MYSQL_DATABASE' => ['database', 'database'],

	// Core variables
	'FRIENDICA_ADMIN_MAIL' => ['config', 'admin_email'],
	'FRIENDICA_URL'        => ['system', 'url'],
	'FRIENDICA_TZ'         => ['config', 'timezone'],
	'FRIENDICA_LANG'       => ['config', 'language'],
	'FRIENDICA_SITENAME'   => ['config', 'sitename'],

	// Storage
	'FRIENDICA_DATA'     => ['storage', 'name'],
	'FRIENDICA_DATA_DIR' => ['storage', 'filesystem_path'],

	// Debugging/Profiling
	'FRIENDICA_DEBUGGING'       => ['system', 'debugging'],
	'FRIENDICA_LOGFILE'         => ['system', 'logfile'],
	'FRIENDICA_LOGLEVEL'        => ['system', 'loglevel'],
	'FRIENDICA_PROFILING'       => ['system', 'profiler'],
	'FRIENDICA_LOGGER'          => ['system', 'logger_config'],
	'FRIENDICA_SYSLOG_FLAGS'    => ['system', 'syslog_flags'],
	'FRIENDICA_SYSLOG_FACILITY' => ['system', 'syslog_facility'],

	// Caching
	'FRIENDICA_CACHE_DRIVER'             => ['system', 'cache_driver'],
	'FRIENDICA_SESSION_HANDLER'          => ['system', 'session_handler'],
	'FRIENDICA_DISTRIBUTED_CACHE_DRIVER' => ['system', 'distributed_cache_driver'],
	'FRIENDICA_LOCK_DRIVER'              => ['system', 'lock_driver'],

	// Redis Config
	'REDIS_HOST' => ['system', 'redis_host'],
	'REDIS_PORT' => ['system', 'redis_port'],
	'REDIS_PW'   => ['system', 'redis_password'],
	'REDIS_DB'   => ['system', 'redis_db'],

	// Proxy Config
	'FRIENDICA_FORWARDED_HEADERS' => ['proxy', 'forwarded_for_headers'],
	'FRIENDICA_TRUSTED_PROXIES'   => ['proxy', 'trusted_proxies'],

	// Outbound security
	'FRIENDICA_BLOCK_PRIVATE_ADDRESSES' => ['system', 'block_private_addresses'],
	'FRIENDICA_ALLOWED_INTERNAL_HOSTS'  => ['system', 'allowed_internal_hosts'],
];

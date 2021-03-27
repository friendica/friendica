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
 * Main mapping table of environment variables to namespaced config values
 *
 */

return [
	'MYSQL_HOST' => ['database', 'hostname'],
	'MYSQL_USERNAME' => ['database', 'username'],
	'MYSQL_USER' => ['database', 'username'],
	'MYSQL_PORT' => ['database', 'port'],
	'MYSQL_PASSWORD' => ['database', 'password'],
	'MYSQL_DATABASE' => ['database', 'database'],

	// Core variables
	'FRIENDICA_ADMIN_MAIL' => ['config', 'admin_email'],
	'FRIENDICA_URL' => ['system', 'url'],
	'FRIENDICA_TZ' => ['config', 'timezone'],
	'FRIENDICA_LANG' => ['config', 'language'],
	'FRIENDICA_SITENAME' => ['config', 'sitename'],
	'FRIENDICA_SENDER_MAIL' => ['config', 'sender_email'],

	// Storage
	'FRIENDICA_DATA' => ['storage', 'name'],
	'FRIENDICA_DATA_DIR' => ['storage', 'filesystem_path'],

	// Debugging/Profiling
	'FRIENDICA_DEBUGGING' => ['system', 'debugging'],
	'FRIENDICA_LOGFILE' => ['system', 'logfile'],
	'FRIENDICA_LOGLEVEL'=> ['system', 'loglevel'],
	'FRIENDICA_PROFILING' => ['system', 'profiler'],

	// Caching
	'FRIENDICA_CACHE_DRIVER' => ['system', 'cache_driver'],
	'FRIENDICA_SESSION_HANDLER' => ['system', 'session_handler'],
	'FRIENDICA_LOCK_DRIVER' => ['system', 'lock_driver'],

	// Redis Config
	'REDIS_HOST' => ['system', 'redis_host'],
	'REDIS_PORT' => ['system', 'redis_port'],
	'REDIS_PW' => ['system', 'redis_password'],
	'REDIS_DB' => ['system', 'redis_db'],
];
